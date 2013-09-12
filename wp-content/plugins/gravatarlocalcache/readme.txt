=== GravatarLocalCache ===
Contributors: cybio
Website link: http://blog.splash.de/
Author: Oliver Schaal
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/2010/01/04/gravatarlocalcache
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=C2RBCTVPU9QKJ&lc=DE&item_name=splash%2ede&item_number=WordPress%20Plugin%3a%20Floatbox%20Plus&cn=Mitteilung%20an%20den%20Entwickler&no_shipping=1&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: gravatar, image, cache, local, speed
License: GPL v3, see LICENSE
Requires at least: 3.3.0
Tested up to: 3.4.0
Stable tag: 1.1.2

Local cache for gravatar images (saves dnsqueries and let you control cache/proxysettings of the images).

== Description ==

With this plugin you can cache gravatar images on your server (and they will be delivered with the cache settings of your webserver).

Please report bugs and/or feature-request to our ticket-system: [Bugtracker/Wiki](http://trac.splash.de/glc).
For Support, please use the [forum](http://board.splash.de/forumdisplay.php?f=105).
Latest development news: [Twitter](http://twitter.com/cybiox9).

== Installation ==

1. Upload the 'GravatarLocalCache' folder to '/wp-content/plugins/'
2. Activate the plugin through the 'Plugins' menu in the WordPress admin
3. Adjust the caching time on the options page (default: 3 days)

== Frequently Asked Questions ==

= The loading time of my website is very high? =

This can happen, if there are many gravatar images that need to be updated...
Shoudn't be a problem since version 0.9.2.

= Gravatars aren't cached, whats the problem? =
GravatarLocalCache depends on the use of get_avatar() (a WordPress function).
If your plugin/theme doesn't use this function, GLC can't fetch the gravatar requests. Ask the author of the plugin/theme to use "get_avatar()".

= How can i change the cache/proxy-settings of the images? =
If you use apache as your webserver, you can set the options throught .htacces as described [here (german)](http://blog.splash.de/2010/01/29/cache-kontrolle-beim-apache-via-htaccess/) , [here](http://www.samaxes.com/2009/01/more-on-compressing-and-caching-your-site-with-htaccess/) or [here](http://www.realityloop.com/blog/2009/08/19/optimizing-page-load-times-using-moddeflate-modexpires-etag-apache2).
For other webservers you have to check the manual.

For any further questions, please use the [support forum](http://board.splash.de/forumdisplay.php?f=105).

== Changelog ==

= 1.1.2 =
* [FIX] save options

= 1.1.1 =
* [FIX] updated to latest WordPress-API-calls (WP >=3.3.0 required)

= 1.1.0 =
* [NEW] "auto"-delete files older than cache time/cachecleanup

= 1.0.2 =
* [FIX] open_basedir-restriction error (thx BenBE)

= 1.0.1 =
* [FIX] compatibility release for wp3.0

= 1.0.0 =
* [NEW] some more stats
* [NEW] dashboard widget with some stats
* [NEW] german translation
* [FIX] cache directory moved to wp-content/glc_cache
* [more information](http://blog.splash.de/2010/02/20/gravatarlocalcache-1-0-0-stable-release/)

= 0.9.5 (1.0.0 RC4) =
* [FIX] default gravatar
* [more information](http://blog.splash.de/2010/02/15/gravatarlocalcache-0-9-3-0-9-5/)

= 0.9.4 (1.0.0 RC3) =
* [FIX] default gravatar (mystery only)

= 0.9.3 (1.0.0 RC2) =
* [NEW] wordpress setting for the default gravatar is now used
* [FIX] if there is a problem with the cache directory, some debug information will be displayed
* [FIX] possible duplicate constant

= 0.9.2 (1.0.0 RC1) =
* [NEW] number of gravatars to be fetched is now limited (max 3 per page)
* [more information](http://blog.splash.de/2010/01/26/gravatarlocalcache-0-9-2-limits/)

= 0.9.1 =
* [FIX] path/url

= 0.9.0 =
* [NEW] initial release

== ToDo ==
* [NEW] cronjob to refresh cached images