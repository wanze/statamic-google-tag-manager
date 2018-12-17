# Google Tag Manager

[![Build Status](https://travis-ci.org/wanze/statamic-google-tag-manager.svg?branch=master)](https://travis-ci.org/wanze/statamic-google-tag-manager)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Integrates Google Tag Manager (GTM) in [Statamic 2](https://statamic.com). The GTM javascript is rendered via tags.

## Installation

1. Download the addon and rename the folder to `GoogleTagManager` 
2. Move the `GoogleTagManager` folder to `site/addons`

## Configuration

The addon offers the following settings:

* **`Container ID`** The ID assigned by GTM for this website container.
* **`Data Layer`** The name of the data layer. Default value is `dataLayer`. In most cases, use the default.
* **`Excluded paths`** Define paths where the GTM javascript should be omitted. You may use `*` as wildcard, 
e.g. `/about*` will exclude every page under `/about`.
* **`Exclude for authenticated users`** Toggle to exclude GTM javascript for all authenticated users. If set to false,
you might exclude for specific roles or groups.
* **`Exclude for user roles`** Exclude GTM javascript for some user roles only, enter role names.
* **`Exclude for user groups`** Exclude GTM javascript for some user groups only, enter group names.

## Tags

Render the GTM javascript in your `layout` template with the provided tags: 

* Add the `{{ google_tag_manager:head }}` tag somewhere in your `<head>`.
* Add the `{{ google_tag_manager:body }}` tag right after the opening `<body>` tag.

That's it! 🎉 Your website should now be connected to Google Tag Manager.

## Testing

You might easily test if the GTM tags are firing:

1. Log into the GTM container at [tagmanager.google.com](https://tagmanager.google.com).
2. Enable _Preview_ mode.
3. Visit your website within the same browser. You should now see the GTM debug console on the bottom of the page.

Use the GTM debug console to check if all tags are firing properly.

> If you do not see the debug console, make sure that you visit your website as anonymous user, if you exclude the
GTM javascript for authenticated users.
