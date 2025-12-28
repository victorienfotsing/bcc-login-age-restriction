=== BCC Login Age Restriction ===
Contributors: Victorien Fotsing
Tags: age, restriction, bcc-login
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: Apache License Version 2.0, January 2004
License URI: http://www.apache.org/licenses/LICENSE-2.0

== Description ==

Restrict access to pages by visitor age using the birthdate claim provided by the BCC Login OIDC token.

This plugin integrates with the BCC Login plugin and uses `BCC_Login_Token_Utility::get_token_claims()` to obtain the `birthdate` claim.

== Installation ==

1. Install and activate the BCC Login plugin first.
2. Upload the `bcc-login-age-restriction` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' screen in WordPress.

== Frequently Asked Questions ==

= What happens if BCC Login is not installed?

The plugin prevents activation and will show an admin notice until the dependency is installed and active.

= How do I enable age restriction for a page?

Use Quick Edit on the Pages list to check "Age restricted" and set the Min/Max age, or add the corresponding post meta (`blar_age_restriction`, `blar_min_age`, `blar_max_age`).

== Changelog ==

= 0.1.0

* Initial release

== Upgrade Notice ==

= 0.1.0

Initial release.

== Arbitrary section ==

This plugin is intended for sites using the BCC Login authentication solution. It does not perform token validation — it relies on BCC Login to manage authentication and token storage.
