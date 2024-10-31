phototools
Contributors: Gerhard Hoogterp
Tags: phototools, dashboard
Requires at least: 3.0.1
Tested up to: 5.2
Requires PHP: 5.6
Stable tag: 1.7
Donate link: https://gerhardhoogterp.nl/plugins/&utm_source=readme&utm_campaign=phototools
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Phototools replaces the default activity widget on the dashboard by three separate widgets with thumbnails.

== Description ==

Phototools replaces the default activity widget on the dashboard by three separate widgets with thumbnails.
It's mend to use with the other "phototools" plugins like geo2wp, exifwidget etc. 
The "published recent" and "publishing soon" also have links too view the post and to the edit screen. 
The "Recent comments" shows the thumbnail of the parent post. 
the number of items in the list is settable between 1 and 30. 

Besides this it implements a few extra's:

* turn on/off support for shortcodes in widgets, supporting the shortcodes in the other phototools plugins
* turn on/off a general taxonomy "photogroup" to group photo's in any groups you like
* Fuzzy dates on/off. Makes the postdates in the activity widgets more "humanlike".
* Rich photo info https://www.schemaapp.com/tools/jsonld-schema-generator/Photograph/)
* <domain>/latests option to redirect to the latest posts on your blog. For example: [Example: https://gerhardhoogterp.nl/latest](https://gerhardhoogterp.nl/latest "example") will show your the latest photo I posted.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. go to the media list, filter on "unattached" and check out the menu and the bulk pulldown. 

== Frequently Asked Questions ==

== Screenshots ==

1. "published recent" and "publishing soon"
2. "Recent comments"
3. The settings screen.


== Changelog ==

= 1.0 =

* First release

= 1.1 =

* Fixed timezone issue

= 1.2 = 

* Stupid mistake, forgot to change a hard link to my site to a get_site_url() so it works for other too.. 
* added rel="”noopenener noreferrer" to external links with _blank
* added a "photogroup" taxonomy to group photos together for "purposes". I have some ideas to make use of this.
* Implemented "fuzzy dates" incl. and on/off toggle in the settings. If on, dates postdates are presented like "in 4 weeks" or
  "a minute ago". The close the postdate comes, the more accurate it will be. "In a moment" is less than a minute. 
  The mouseover always has the exact date and time.
* added rich photoinfo to the page. 

= 1.3 =

* added "latest" option. you can turn it on/off in the settings and define your "latest" string which will be used in the url.

= 1.4 =

* Fixed a minor compatability issue

= 1.5 =

* Added a widget with the latest posts and an icon sized thumbnail form the image. Users with edit privileges can click the edit icon.

= 1.6 =

* fixed a stupid mistake

= 1.7 = 

* Screwed up the difference between dates in days. 
* some minor adjustments

== Upgrade Notice ==

Nothing yet.
