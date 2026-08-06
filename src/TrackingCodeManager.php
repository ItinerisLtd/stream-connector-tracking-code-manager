<?php

declare(strict_types=1);

namespace Itineris\StreamConnectorTrackingCodeManager;

use WP_Stream\Connector;

use function __;
use function defined;
use function esc_html__;
use function esc_url;
use function get_option;
use function in_array;
use function is_array;
use function str_starts_with;

class TrackingCodeManager extends Connector
{
    private const SNIPPET_OPTION_PREFIX = 'TCM_Snippet_';

    private const SETTINGS_OPTIONS = [
        'TCM_HookPriority',
        'TCM_MetaboxPostTypes',
    ];

    /**
     * Connector slug
     *
     * @var string
     */
    public $name = 'tracking-code-manager';

    /**
     * Actions registered for this connector
     *
     * @var array
     */
    public $actions = [
        'added_option',
        'updated_option',
        'delete_option',
    ];

    public function get_label(): string
    {
        return __('Tracking Code Manager', 'stream-connector-tracking-code-manager');
    }

    public function get_context_labels(): array
    {
        return [
            'tracking_code_manager' => __('Tracking Code Manager', 'stream-connector-tracking-code-manager'),
        ];
    }

    public function get_action_labels(): array
    {
        return [
            'created'          => __('Created', 'stream-connector-tracking-code-manager'),
            'updated'          => __('Updated', 'stream-connector-tracking-code-manager'),
            'activated'        => __('Activated', 'stream-connector-tracking-code-manager'),
            'deactivated'      => __('Deactivated', 'stream-connector-tracking-code-manager'),
            'deleted'          => __('Deleted', 'stream-connector-tracking-code-manager'),
            'settings_updated' => __('Settings Updated', 'stream-connector-tracking-code-manager'),
        ];
    }

    /**
     * Add action links to Stream drop row in admin list screen
     *
     * @param array             $links  Previous links registered.
     * @param \WP_Stream\Record $record Stream record.
     *
     * @filter wp_stream_action_links_{connector}
     *
     * @return array Action links
     */
    public function action_links($links, $record): array
    {
        if ('deleted' === $record->action || ! $record->object_id) {
            return $links;
        }

        if (! defined('TCMP_TAB_EDITOR_URI')) {
            return $links;
        }

        $links[esc_html__('Edit Tracking Code', 'stream-connector-tracking-code-manager')] = esc_url(
            TCMP_TAB_EDITOR_URI . '&id=' . $record->object_id,
        );

        return $links;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint -- WordPress's added_option/updated_option hooks are unscoped and can pass any option value, including objects.
    public function callback_added_option(string $option, mixed $value): void
    {
        if (str_starts_with($option, self::SNIPPET_OPTION_PREFIX)) {
            $this->logSnippetCreated($option, $value);

            return;
        }

        if (in_array($option, self::SETTINGS_OPTIONS, true)) {
            $this->logSettingsUpdated($option, null, $value);
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint -- WordPress's added_option/updated_option hooks are unscoped and can pass any option value, including objects.
    public function callback_updated_option(string $option, mixed $oldValue, mixed $value): void
    {
        if (str_starts_with($option, self::SNIPPET_OPTION_PREFIX)) {
            $this->logSnippetUpdated($option, $oldValue, $value);

            return;
        }

        if (in_array($option, self::SETTINGS_OPTIONS, true)) {
            $this->logSettingsUpdated($option, $oldValue, $value);
        }
    }

    public function callback_delete_option(string $option): void
    {
        if (! str_starts_with($option, self::SNIPPET_OPTION_PREFIX)) {
            return;
        }

        $snippet = get_option($option);
        if (! is_array($snippet)) {
            return;
        }

        $this->logSnippetDeleted($option, $snippet);
    }

    private function snippetIdFromOption(string $option): int
    {
        return (int) substr($option, strlen(self::SNIPPET_OPTION_PREFIX));
    }

    /**
     * Determine which Stream action best describes a snippet option change.
     *
     * Reordering is deliberately untracked: TCM's own drag-to-reorder AJAX handler
     * gates its save behind current_user_can('edit_plugins'), a capability WordPress
     * core strips from every user (including administrators) whenever
     * DISALLOW_FILE_EDIT is enabled — standard practice on locked-down sites — so the
     * feature silently no-ops there. An order-only diff is treated as not meaningful.
     *
     * @param array $old Previous snippet option value.
     * @param array $new New snippet option value.
     *
     * @return string|null Stream action slug, or null if nothing meaningful changed.
     */
    private function determineSnippetAction(array $old, array $new): ?string
    {
        $changedKeys = $this->changedKeys($old, $new);

        if ([] === $changedKeys || ['order'] === $changedKeys) {
            return null;
        }

        if (['active'] === $changedKeys) {
            return ($new['active'] ?? null) ? 'activated' : 'deactivated';
        }

        return 'updated';
    }

    /**
     * Determine which top-level keys differ between two snippet option arrays.
     *
     * @param array $old Previous snippet option value.
     * @param array $new New snippet option value.
     *
     * @return array Sorted list of keys whose values differ.
     */
    private function changedKeys(array $old, array $new): array
    {
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $changed = array_filter($keys, static function (int|string $key) use ($old, $new): bool {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison,SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator -- intentional: TCM's own code writes the same field as both int and string depending on save path (Manager.php sanitize() vs editor.php's $_POST passthrough vs the reorder/toggle handlers), so loose comparison is required to avoid misclassifying no-op saves as real changes.
            return $oldValue != $newValue;
        });

        sort($changed);

        return array_values($changed);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint -- WordPress's added_option/updated_option hooks are unscoped and can pass any option value, including objects.
    private function logSnippetCreated(string $option, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        $name = (string) ($value['name'] ?? '');

        $this->log(
            /* translators: %s is the tracking code name */
            __('"%s" tracking code created', 'stream-connector-tracking-code-manager'),
            ['name' => $name],
            $this->snippetIdFromOption($option),
            'tracking_code_manager',
            'created',
        );
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint -- WordPress's added_option/updated_option hooks are unscoped and can pass any option value, including objects.
    private function logSnippetUpdated(string $option, mixed $oldValue, mixed $value): void
    {
        if (! is_array($oldValue) || ! is_array($value)) {
            return;
        }

        $action = $this->determineSnippetAction($oldValue, $value);
        if (null === $action) {
            return;
        }

        $name = (string) ($value['name'] ?? '');

        $messages = [
            /* translators: %s is the tracking code name */
            'activated'   => __('"%s" tracking code activated', 'stream-connector-tracking-code-manager'),
            /* translators: %s is the tracking code name */
            'deactivated' => __('"%s" tracking code deactivated', 'stream-connector-tracking-code-manager'),
            /* translators: %s is the tracking code name */
            'updated'     => __('"%s" tracking code updated', 'stream-connector-tracking-code-manager'),
        ];

        $this->log(
            $messages[$action],
            [
                'name'           => $name,
                'changed_fields' => implode(', ', $this->changedKeys($oldValue, $value)),
            ],
            $this->snippetIdFromOption($option),
            'tracking_code_manager',
            $action,
        );
    }

    private function logSnippetDeleted(string $option, array $snippet): void
    {
        $name = (string) ($snippet['name'] ?? '');

        $this->log(
            /* translators: %s is the tracking code name */
            __('"%s" tracking code deleted', 'stream-connector-tracking-code-manager'),
            ['name' => $name],
            $this->snippetIdFromOption($option),
            'tracking_code_manager',
            'deleted',
        );
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint -- WordPress's added_option/updated_option hooks are unscoped and can pass any option value, including objects.
    private function logSettingsUpdated(string $option, mixed $oldValue, mixed $value): void
    {
        $this->log(
            __('Tracking Code Manager settings updated', 'stream-connector-tracking-code-manager'),
            ['option' => $option],
            0,
            'tracking_code_manager',
            'settings_updated',
        );
    }
}
