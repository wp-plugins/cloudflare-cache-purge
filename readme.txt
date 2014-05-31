=== CloudFlare(R) Cache Purge ===
Contributors: shanaver
Tags: CloudFlare, cache purge, cache clear, API
Requires at least: 3.0.1
Tested up to: 3.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author URI: http://www.fiftyandfifty.org/

Purge your entire CloudFlare cache from within Wordpress.

== Description ==

Purge your entire CloudFlare cache, or an any specific URL, manually - or automatically everytime a post has been updated!

This plugin was not built by CloudFlare, it was built by Fifty & Fifty - a humanitarian creative studio located in San Diego, California.

== Installation ==

1. Upload cloudflare-cache-purge folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a free CloudFlare account at http://www.cloudflare.com
1. Set up 'Page Rules' in CloudFlare to start caching your site pages
1. Enter your CloudFlare email address & API token on the plugin settings page
1. Enable 'Automatically Purge on Update' or click 'Purge' from the plugin settings page

== Frequently Asked Questions ==

= Do I need to have a CloudFlare(R) account to use this plugin? =

Yes, setting up a CloudFlare account is free and can take less than 5 minutes.

= Does it purge anything on page/post creation?

Yes, if you set 'Auto Purge on Update' in the admin it will fire on Wordpress' 'publish' hook which includes new pages/posts.  Typically the page/post url won't exist in you CloudFlare cache yet so just the homepage would get purged.

= If you set posts/pages to auto-purge on add/update, does the homepage URL get purged as well?

Yes, we assume that you have a blogroll that would need to be updated on the homepage.

= Are any other URLs purged automatically if I set 'Auto Purge on Update'?

No, just the page/post permalink & the homepage.  You would have to manually purge any other pages that need to get updated.

== Screenshots ==

1. Settings

== Changelog ==

= 1.0.0 =
* Initial Wordpress.com version

= 1.0.1 =
* Namespace css better

= 1.0.2
* Multisite domain mapping support - thanks Ed Cooper

= 1.0.3
* Small php notice fix

= 1.0.4
* Hide logs from public-facing pages

= 1.0.5
* Update CloudFlare(R) branding

= 1.0.6
* Add more FAQ items

= 1.0.7
* Fix ajax error on post update

== Upgrade Notice ==

= 1.0.0 =
Initial release

