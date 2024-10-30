=== Plugin Name ===
Contributors: nickpdx
Donate link: 
Tags: page, cms, media
Requires at least: 2.7
Tested up to: 2.7
Stable tag: 0.1.1

This plugin facilitates the dynamic inclusion, on a per-Page basis, of media
library artwork into predefined places in your site's theme.

== Description ==

Media2Layout facilitates the dynamic inclusion of media library artwork into
predefined places in your site's theme.  It creates named positions (by
default "campaign" and "header") which can be assigned a media item per each
Page.

* Adds an admin menu item under Themes to edit your Position names
* Adds a dropdown control when editing Pages to select the images for each Position in that Page

To insert the images into your theme, just add a bit of PHP code to your
theme in the appropriate spot, for example:

`<?php Media2Layout::banner('foo');?>`

For the position named "foo" this would print an <img> tag for the media
assigned to the Foo position of the current page (or print nothing if no
media is assigned to that position).  Currently it wraps the <img> in a
<div> with class "media2layout_[position-name]".

== Installation ==

1. Extract Media2Layout archive to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the admin menu under Themes to create a named media position
1. Add that position to your template(s) by inserting `<?php Media2Layout::banner('position-name-here');?>`
1. Assign media to your media positions per each Page

== Frequently Asked Questions ==

== Screenshots ==
1. The Media2Layout position admin screen
2. Media2Layout annotation for position "campaign" in a custom theme
3. Assigning image from media library to "campaign" position for a Page