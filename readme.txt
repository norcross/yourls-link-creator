=== YOURLS Link Creator ===
Contributors: norcross
Website Link: http://andrewnorcross.com/plugins/yourls-link-creator/
Donate link: https://andrewnorcross.com/donate
Tags: YOURLS, shortlink, custom URL
Requires at least: 3.6
Tested up to: 4.4
Stable tag: 2.1.1
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

Place the following code in your theme file (usually single.php) `<?php yourls_display_box(); ?>`

= The delete function doesn't remove the short URL from my YOURLS installation =

This is a limitation with the YOURLS API, as there is not a method yet to delete a link. The delete function has been added to the plugin to allow users to get the updated URL that they may have changed in the YOURLS admin panel

== Screenshots ==

1. Metabox to create YOURLS link with optional keyword field
2. Example of a post with a created link and click count
3. Post column displaying click count
4. Settings page



== Changelog ==

= 2.1.1 - 02/25/2016 =
* added `yourls_run_cron_jobs` filter to allow users to disable the cron job

= 2.1.0 - 08/06/2015 =
* added `yourls_after_url_save` action to post transitioning from future to publish (scheduled posts)

= 2.0.9 - 08/03/2015 =
* added `yourls_after_url_save` action to fire when the custom URL is generated and stored

= 2.0.8 - 06/19/2015 =
* added ability to set custom keyword prior to save
* added `yourls_user_cap` filter for modifying default user capability
* added `yourls_settings_cap` filter for modifying settings page capability
* added `yourls_keyword_filter` filter for changing default keyword sanitation
* added new legacy file to pick up old functions from Ozh's plugin
* added GitHub plugin updater support

= 2.0.7 - 02/14/2015 =
* fixed bug with post row actions missing on drafts
* updated cron job scheduling

= 2.0.6 - 02/13/2015 =
* added scheduled posts to available post status
* added `yourls_post_status` filter to modify these (although not sure why you'd want to)
* added function to create a link when a post is moved from 'scheduled' to 'publish' if one doesn't exist (requires setting update)
* added API status check sidebox to the settings page

= 2.0.5 - 02/10/2015 =
* fixed API URL construction to account for installs in a subfolder
* added filter `yourls_strip_urls` to allow trailing slash removal (default is false)

= 2.0.4 - 02/09/2015 =
* fixed API query args getting malformed before call
* fixed content title sanitation encoding
* forced POST method for API call and removed setting
* added bulk import function for existing data
* added `yourls_display_box` template tag to display front end box
* added `get_yourls_shortlink` to return or echo the shortlink
* added `wp_ozh_yourls_raw_url` to match previous plugin from Ozh

= 2.0.3 - 02/05/2015 =
* added API key field show / hide to (hopefully) account for Chrome being aggressive on the field
* removed 'future' from array of post types to create items on save

= 2.0.2 - 02/02/2015 =
* fixed bug with creating link on post save

= 2.0.1 - 02/01/2015 =
* added post title submission to API call
* added option for using POST method instead of GET method to address API permission issues
* nonce ALL THE THINGS

= 2.0.0 - 02/01/2015 =
* COMPLETELY REFACTORED
* updated UI to match current WP setup
* rewrote javascript for better security
* added post action row items for creating and updating counts
* added check for possible 404 return on YOURLS server
* a whole lot more

= 1.09 - 02/21/2013 =
* bugfixes related to certain hosting configurations
* minor JS cleanup

= 1.08 - 12/31/2012 =
* change to allow scheduled posts to process URL call. props @ethitter

= 1.07 - 12/24/2012 =
* better sanitizing of personal YOURLS URL
* code cleanup

= 1.06 - 12/18/2012 =
* included template tag for theme use. (See FAQs for usage)
* added a 'delete' button for single links (See FAQs for details)
* The YOURLS metabox will not appear until a post has been published.

= 1.05 - 12/10/2012 =
* added a conversion tool from Ozh's plugin to this one

= 1.04 - 12/09/2012 =
* refactoring the wp_shortlink functionality

= 1.03 - 12/04/2012 =
* Bugfix for post type checking

= 1.02 - 10/14/2012 =
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
