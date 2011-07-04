=== TweetUpdater ===
Contributors: DefProc
Version: 3.1.1
Stable tag: 3.1.1
Tags: twitter, message, tweet, status, update, OAuth, shorturl, short_url, shortlink, short url, la petite url, publish, update, YOURLS, bit.ly, Stwnsh, TinyURL, ZZ.GD
Requires at least: 3.0
Tested up to: 3.2

Send messages to Twitter when a post is published or updated; uses OAuth. Will add a short URL from a plugin, or from an external service.

== Description ==

TweetUpdater will send a message to your Twitter account, from you WordPress installation, whenever a new post is published, or when a previously published 
post is updated; as you choose. 

The tweet format is flexible, with placeholders for title and URL. URLs can be included from a variety of sources. In addition to full 
post permalinks (standard or friendly urls as selected in your site setup) the following short url services are supported:

Internal services:

* [la petite url](http://wordpress.org/extend/plugins/le-petite-url/) plugin
* WordPress Permalink

Generic shorteners

* YOURLS

External services:

* Bit.ly
* Stwnsh
* TinyURL

Posts tweeting can be ommited based on post category, or custom field, or custom field and value pair.

Derived from the original TwitterUpdater, version 3 has been fully rewritten to include OAuth authorisation with Twitter; because basic
authentication (username & password) has now been deactivated for applications using the Twitter API. 

The TwitterUpdater plugin has a great tradition of being updated and improved by anyone who needed it to do something different, and 
as such TweetUpdater is one of a handful of TwitterUpdater forks. 

If the short url service you like to use is not included, please see the FAQ on how to add it. If you add a service, let me know and 
I'll add the code to the next release.

* Support & Bug reports at [github.com/DefProc/TweetUpdater/issues](http://github.com/DefProc/TweetUpdater/issues)


== Installation ==

This section describes how to install the plugin and get it working.

1. Download and extract the plugin from the zip file
1. Copy the plugin folder into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Link the plugin with your Twitter account by following the instructions on the new `TweetUpdater Settings` page.

To Upgrade:

Either upgrade automatically from your admin pages. Or if upgrading manually: deactivate TweetUpdater *before* copying 
the new files across; then enable when completed.

* You will keep your settings when you upgrade from version 3.x and above
* TweetUpdater will remain linked to your Twitter account when you upgrade from version 3.x or above. 

Requirements:

* php cURL

TweetUpdater uses the php cURL module to retrieve external short URLs and to contact Twitter. Your server will need to have cURL installed and 
activated for TweetUpdater to function. For most hosting providers, it will be installed already. Check `<?php phpinfo() ?>` if you're 
not sure if cURL is activated.

== Frequently Asked Questions ==

= Can I use [my favourite short url service] with TweetUpdater? =

Sure, add it yourself! ;D

Short URL retrieval is handled by the `tu_get_shorturl()` function in `tweetupdater.php`. If you'd like to use another service, 
taking a look at this function will give you an idea of how to add one. 

Some URL shorteners return a link as the only content from an API call, but others, like bit.ly give other information that 
needs to be parsed before it can be used. Take a look at the `tu_make_bitly_url()` function to get an idea of how to deal with 
that. Help files for the service should tell you the format of the returned request, including any error messages.

For short URLs from WordPress plugins, you'll need to find the function that outputs the generated URL from just a $post_ID 
input variable. You can't use the same one as you use to show the link on the page, because TweetUpdater doesn't run when a 
page is processed to view; it runs when a page gets published/updated.

= Will you be adding any more URL services/placeholders/other functions? =

Because the plugin does everything I need it too, I'm not looking to add any more URL services at the moment. The main work 
for me was including [la petite url](http://wordpress.org/extend/plugins/le-petite-url/) (which I use) and adding the OAuth 
login for Twitter so it would keep working after basic auth was swithched off.

I use this plugin and will keep it updated, but if there's something specific you want to see in TweetUpdater, the quickest 
way to get it is probably to poke around and figure out how to add it yourself. 

= If I add a function, will you include it in TweetUpdater? =

Yes. If you have a URL service or placeholder that you've added, let me know and I'll add it to a new release. 

Source code is available for you to tinker with or fork at [Github](http://github.com/DefProc/TweetUpdater).

= What's next? =

TweetUpdater needs some better error handling, and to cache and retry requests that fail if Twitter is down.

= TweetUpdater doesn't do what I want. =

If TweetUpdater doesn't do what you want, and you don't want to get your hands dirty with code, there are a number of nice 
looking Twitter Status updaters about, [WordTwit](http://wordpress.org/extend/plugins/wordtwit/) looks very fancy, but I
haven't tried it. 

== Upgrade Notice ==

= 3.1.1 = 

Bug fix for Twitter reset process.

= 3.1 = 

Added option to limit twitter message sending to selected categories or custom field/value.

Added tweet length checking, will trim title and auto-shorten long urls to keep tweets under 140 characters.

Added generic [YOURLS](http://yourls.org) shortener support - for both public and private installations.

= 3.0.1 =

Added (Stwnsh)[http://stwnsh.com/] Welsh language shortener

= 3.0 =

Stable release for WP 3.0.1

== Changelog ==

= 3.1 = 

* Added "limit by category or custom field" (LimitCategory Branch) - From [BjornW](http://burobjorn.nl/).
* Added tweet length checking, will trim the title and force shortening of long urls to keep tweets under 140 characters.
* Added generic [YOURLS](http://yourls.org) shortener support - for both public and private installations. Can use timestamp-hashed secret keys (preferred) or usename/password combo (sent in plaintext).
* Removed CURL dependency for short URL retreval, replaced with WP_Http (CURL is still required for sending updates to Twitter).
* Removed zz.gd as a shortener option (service has closed).

= 3.0.1 = 

* Rearranged URL selection to internal/external sources.
* Added Welsh language URL shortener - From Gareth Jones, [Stwnsh](http://stwnsh.com/).
* Added warning if Bit.ly is selected without API credentials.

= 3.0 =

* Changed name to *TweetUpdater*
* Uses OAuth as the Twitter account authentication method. (Uses the TwitterAuth php library from [Abraham](http://github.com/abraham/twitteroauth))
* Updated the settings page to better fit in with the WordPress styles.
* Reduced the number of entries in the `wp-options` table to 2 by using arrays.
* Renamed functions and DB entries to reduce the chance of collision with other plugins.
* Complete code revision.
* Revised documentation.
* Set cURL as standard retrieval method for short URLs
* Added Settings link on plugins admin page
* Added to the [WordPress Plugins Directory](http://wordpress.org/extend/plugins/tweetupdater/).
* Updated functions for WP 3.0.1

*Previous to version 2.11, this plugin is the same as [TwitterUpdater](http://www.twitterupdater.com).*

= 2.11 =

[http://www.deferredprocrastination.co.uk](http://www.deferredprocrastination.co.uk)

* Added support for la_petite_url plugin
* Add #url# placeholder instead of "Link title to blog?" checkbox
* Added option to use full WordPress permalink as url

= 2.10 =

Jordan Trask "[@comm](http://twitter.com/comm)"

* Added support for Bit.ly

= 2.09 =

Jordan Trask "[@comm](http://twitter.com/comm)" - [http://geektank.net/](http://geektank.net/)

* ZZ.gd broke with 403 forbidden error, fixed with setting different user agent.
* Move menu to "Settings".
* Added option under configuration to choose ZZ.gd or TinyURL. Added 1 function.
* Re-worked the code for url shortening services, change in one place instead of three. Created a function instead.
  which makes it easier to add more services if necessary. Bit.ly support in the future?

= 2.08 =

Eric Austin Lee - [http://www.ericaustinlee.com](http://www.ericaustinlee.com)

* Corrected to fit new needs of Wordpress 2.7

= 2.07.1 =

Ingo "Ingoal" Hildebrandt (v2.07.1)

* corrected tinyurl-api-url...

= 2.07 =

Marco Luthe - [http://www.saphod.net ](http://www.saphod.net)

- Changed "save_post" hook to "publish_post"
- Added "future_to_publish" hook and vc_twit2

= 2.06 =

Ingo "Ingoal" Hildebrandt

- added cascading short-url generation (if zz.gd is down, it'll cascade to tinyurl.com)

= 2.05 =
Ingo "Ingoal" Hildebrandt

- added alternative method to get shorturl (using curl instead of fopen)

= 2.04 =

Ingo "Ingoal" Hildebrandt

- fixed character escaping in post-title ( ' and & ) 
- fixed empty post-title when scheduled post appears

= 2.03 =

Ingo "Ingoal" Hildebrandt

- fixed the multi-tweet issue in WP2.6 (due to post revisions the same post was twittered multiple times before)
- streamlined the options...only new posts and edited old-posts from now on out...

= 2.02 =

Ingo "Ingoal" Hildebrandt

- added twitter source parameter to replace "from web"

= 2.01 =

Ingo "Ingoal" Hildebrandt - [http://www.ingoal.info/archives/2008/07/08/twitter-updater/](http://www.ingoal.info/archives/2008/07/08/twitter-updater/)

* replaced tinyurl-support with zz.gd-support even shorter short-url

= 2.0 =
 
Jonathan Dingman (v2.0) - [http://www.firesidemedia.net/dev/software/wordpress/twitter-updater/](http://www.firesidemedia.net/dev/software/wordpress/twitter-updater/)

* added tinyurl-support

= 1.0 =

Based on Version 1.0 TwitterUpdater by Victoria Chan - [http://blog.victoriac.net/ja/geek/twitter-updater](http://blog.victoriac.net/ja/geek/twitter-updater)


