# Stream Connector Tracking Code Manager

Tracks tracking-snippet activity — creation, edits, activation toggles, and deletion — plus changes to the plugin's two Settings-tab options, for [Tracking Code Manager](https://wordpress.org/plugins/tracking-code-manager/).

[![GitHub License](https://img.shields.io/github/license/itinerisltd/stream-connector-tracking-code-manager.svg?style=flat-square)](https://github.com/ItinerisLtd/stream-connector-tracking-code-manager/blob/main/LICENSE)
[![Hire Itineris](https://img.shields.io/badge/Hire-Itineris-ff69b4.svg?style=flat-square)](https://www.itineris.co.uk/contact/)
[![Twitter Follow @itineris_ltd](https://img.shields.io/twitter/follow/itineris_ltd?style=flat-square&color=1da1f2)](https://twitter.com/itineris_ltd)

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Minimum Requirements](#minimum-requirements)
- [Installation](#installation)
- [How it works](#how-it-works)
- [Credits](#credits)
- [License](#license)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Minimum Requirements

- PHP v8.4
- WordPress v6.1
- [Tracking Code Manager](https://wordpress.org/plugins/tracking-code-manager/)

## Installation

```bash
composer require itinerisltd/stream-connector-tracking-code-manager
```

## How it works

Tracking Code Manager does not fire any action or filter hooks around its tracking-snippet writes — `TCMP_Manager::put()` and `TCMP_Manager::remove()` write straight to individually-keyed `wp_options` rows (`TCM_Snippet_{id}`) with nothing to hook into.

This connector instead hooks WordPress core's generic `added_option`, `updated_option`, and `delete_option` actions, and identifies Tracking Code Manager's writes by option-name prefix (`TCM_`). This means it depends on Tracking Code Manager's internal option-naming convention, which is a private implementation detail rather than a public API. **If logging stops working after a Tracking Code Manager update, check whether the plugin renamed its options first.**

## Credits

[Stream Connector Tracking Code Manager](https://github.com/ItinerisLtd/stream-connector-tracking-code-manager) is a [Itineris Limited](https://www.itineris.co.uk/) project created by [Lee Hanbury-Pickett](https://github.com/codepuncher).

Full list of contributors can be found [here](https://github.com/ItinerisLtd/stream-connector-tracking-code-manager/graphs/contributors).

## License

[Stream Connector Tracking Code Manager](https://github.com/ItinerisLtd/stream-connector-tracking-code-manager) is released under the [MIT License](https://opensource.org/licenses/MIT).
