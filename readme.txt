=== YOURLS Link Creator ===
Contributors: norcross
Website Link: http://andrewnorcross.com/plugins/yourls-link-creator/
Donate link: https://andrewnorcross.com/donate
Tags: YOURLS, shortlink, custom URL
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.06
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates a custom short URL when saving posts. Requires your own YOURLS install.

== Description ==

Creates a YOURLS generated shortlink on demand or when saving posts.

Features:

*   Optional custom keyword for link creation.
*   Will retrieve existing URL if one has already been created.
*   Click count appears on post menu
*   Available for standard posts and custom post types.
*   Optional filter for wp_shortlink
*   Built in cron job will fetch updated click counts every hour.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `yourls-link-creator` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the "YOURLS Settings" option in the Settings Menu.
4. Enter your YOURLS custom URL and API key
5. Enjoy!

== Frequently Asked Questions ==


= What's this all about? =

This plugin creates a shortlink (stored in the post meta table) for each post that can be used in sharing buttons, etc.

= What is YOURLS? =

YOURLS is a self-hosted PHP based application that allows you to make your own custom shortlinks, similar to bit.ly and j.mp. [Learn more about it here](http://yourls.org/ "YOURLS download")

= How do I use the template tag? =

Place the following code in your theme file (usually single.php) `do_action('yourls_display');`

= The delete function doesn't remove the short URL from my YOURLS installation =

This is a limitation with the YOURLS API, as there is not a method yet to delete a link. The delete function has been added to the plugin to allow users to get the updated URL that they may have changed in the YOURLS admin panel

== Screenshots ==

1. Metabox to create YOURLS link with optional keyword field
2. Example of a post with a created link and click count
3. Post column displaying click count
4. Settings page



== Changelog ==

= 1.06 =
* included template tag for theme use. (See FAQs for usage)
* added a 'delete' button for single links (See FAQs for details)
* The YOURLS metabox will not appear until a post has been published.

= 1.05 =
* added a conversion tool from Ozh's plugin to this one

= 1.04 =
* refactoring the wp_shortlink functionality

= 1.03 =
* Bugfix for post type checking

= 1.02 =
* Option for adding to specific post types
* delay link creation until status is published
* internationalization support

= 1.01 =
* Added option to create link on post save
* code cleanup

= 1.0 =
* First release!


== Upgrade Notice ==

= 1.06 =
* The YOURLS metabox will not appear until a post has been published. This is to prevent empty or otherwise incorrect URLs from getting created.

= 1.0 =
* First release!
