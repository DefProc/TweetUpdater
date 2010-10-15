TwitterUpdater
==============

WordPress plugin to update your Twitter status when you publish or update a post.
Author: Patrick Fenner

3.1 
---

* Added option to limit message sending by category or custom field - From [BjornW](http://burobjorn.nl/).
* Added tweet length checking, will trim title and force shortening of long urls to keep tweets under 140 characters.
* Added generic [YOURLS](http://yourls.org) shortener support - for both public and private installations. Can use timestamp-hashed secret keys (preferred) or usename/password combo (sent plaintext).
* Removed CURL dependency for short URL retreval, replaced with WP_Http (CURL is still required for sending updates to Twitter).

3.0.1
-----

* Rearranged URL selection to internal/external sources.
* Added Welsh language URL shortener - From Gareth Jones, [Stwnsh](http://stwnsh.com/).
* Added warning if Bit.ly is selected without API credentials.

3.0
---

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

2.11
----

[http://www.deferredprocrastination.co.uk](http://www.deferredprocrastination.co.uk)

* Added support for la_petite_url plugin
* Add #url# placeholder instead of "Link title to blog?" checkbox
* Added option to use full WordPress permalink as url

