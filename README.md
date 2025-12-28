# BCC Login Age Restriction

Restrict access to pages by visitor age using the birthdate claim provided by the BCC Login OIDC token.

This small plugin integrates with the BCC Login plugin to read the user's ID token claims and enforce a minimum/maximum age per page.

## Requirements

- WordPress 5.0+
- PHP 7.4+ (or the site minimum you target)
- The [BCC Login](https://github.com/bcc-code/bcc-wp) plugin must be installed and active (provides `BCC_Login_Token_Utility`).

## Installation

1. Install and activate the BCC Login plugin first.
2. Copy the `bcc-login-age-restriction` folder into `wp-content/plugins/`.
3. Activate **BCC Login Age Restriction** via the WordPress admin Plugins screen.

The plugin will prevent activation if the required BCC Login utility class is not available and will show an admin notice until the dependency is installed.

## Usage

- Enable age restriction for pages using the **Quick Edit** controls on the Pages list, or add the following post meta to a page:
	- `blar_age_restriction` = `1` to enable
	- `blar_min_age` = integer (minimum age, default 0)
	- `blar_max_age` = integer (maximum age, default 36)

- When a visitor is outside the configured age range the plugin will return an unauthorized message (HTTP 401) and stop the page load.

## Implementation notes

- The plugin reads the `oidc_token_id` cookie and looks for the transient `oidc_id_token_<id>` populated by the BCC Login plugin.
- Token claims are parsed via `BCC_Login_Token_Utility::get_token_claims()` — this plugin does not attempt to validate tokens itself.
- For performance the plugin caches token claims and the computed age for the duration of the request and reduces repeated meta reads when filtering menus.

## Changelog

- 0.1.0 — Initial release

## License

This plugin is released under the Apache License Version 2.0, January 2004
