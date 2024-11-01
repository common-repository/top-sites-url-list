=== Plugin Name ===
Contributors: w3b_designer
Tags: sidebar, widget, google, analytics, ga, plugin, list, statistics
Requires at least: 3.0.1
Tested up to: 4.8.1
Stable tag: 1.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get your most popular sites based on Google Analytics statistics to your sidebar.

== Description ==

With this plugin you can easily add your most visited sites list to your sidebar.

= Features =

* Plugin will ask you for authentication for your Google Analytics account, you will be able to choose one from your Google Analytics profiles to get data.
* You can select, how often do you want to check and connect to Google Analytics for new results
* You can select, which post type you want to display (posts, pages, custom post types, all..)
* You can exclude Homepage from the listing
* You can set number of days, which you want to get statistics for (default value is 7 days)
* You can set the number of lines to show in the list
* You can show the number of visits as a label
* You can choose between display number before or after the post title
* You can add custom text or characters before and after the number
* You can style the list with custom font sizes and font and background colors
* You can add your own CSS styles
* Added support for qTranslate-X plugin - if it's available and there is some link with language prefix, the post title will be show translated


== Installation ==

1. Upload top-sites-url-list to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the TOP Sites URL list Settings and Authenticate the plugin to get access to your Google Analytics data
4. Enter the Google Authentication Code in the input field and save the form
5. Wait for connecting to Google Api to retrieve your Google Analytics profiles
6. Select one from your profiles, time for first fetching and time to check new results


== Screenshots ==
1. Admin plugin settings
2. Basic widget setings
3. Basic widget example
4. Basic widget example with custom text
5. Full widget settings
6. Full widget example
7. Views counter in WP admin posts lists


== Changelog ==

= 1.7.1 =
* FIX: remove duplicated string from url on suburl multidomain instalations

= 1.6.2 =
* EDIT: remove commented code

= 1.6.1 =
* EDIT: remove unnecessary functions

= 1.6.0 =
* FIX: fix permalinks - fix double “/” on subdirectory blogs

= 1.5.1 =
* FIX: fix permalinks - call relative home_url (if somebody had https and non https url versions, url went to path, which was stored as site url)

= 1.5.0 =
* FIX: wrong permalinks, if blog is on subdirectory

= 1.4.0 =
* FIX: display only one url in listing, if there is specific sign at the end of url (#, ?)

= 1.3.1 =
* FIX: hide views counter in WP admin posts lists until the Google Analytics profile is set

= 1.3 =
* Possibility to display views counter in WP admin posts lists

= 1.2 =
* Resolve bug - counting number of lines to show in list

= 1.1 =
* Resolve bug - add homepage to list even if this is not a "PAGE" - just Wordpress index site
* Remove categories and tags from listing

= 1.0 =
* First release of this plugin
