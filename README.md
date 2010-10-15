TwitterUpdater
==============

WordPress plugin to update your Twitter status when you publish or update a post.
Author: Patrick Fenner

3.1 
---

* Added "limit by category or custom field" (LimitCategory Branch) - From [BjornW](http://burobjorn.nl/)
* Added tweet length checking, will trim title and 

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
