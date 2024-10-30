<?php
/*
 * Plugin Name: Media2Layout
 * Plugin URI: http://pixelnix.com
 * Description: Select files from the Media Library to use for specified positions in your layout.
 * Version: 0.1.2
 * Author: Nick Eby
 * Author URI: http://pixelnix.com
 */

/*  Copyright 2009 Nick Eby (email:nick@pixelnix.com)

    This file is part of Media2Layout.

    Media2Layout is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Media2Layout is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Media2Layout.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__).'/Media2Layout.class.php';
require_once dirname(__FILE__).'/PositionAdmin.class.php';

// Installing the plugin
register_activation_hook(__FILE__, array('Media2Layout', 'install'));

// Adding an edit area to the main Page edit screen
add_action('edit_page_form', create_function('',
    "add_meta_box('pagemedia2layoutdiv', 'Media2Layout', array('Media2Layout', 'media_positioning_form'), 'page');"
));
add_action('save_post', array('Media2Layout', 'save'));

// Adding an admin page under Themes to edit the position labels
function _m2l_admin_menu() {
    // Add the menu link to our admin page
    $page = add_submenu_page('themes.php',
        'Media2Layout Positions',
        'Media2Layout Positions',
        9,
        __FILE__,
        array('Media2Layout_PositionAdmin', 'edit_positions'));
    // Add our javascript file to the admin scripts for the page we just created
    add_action('admin_print_scripts-'.$page, array('Media2Layout_PositionAdmin', 'admin_scripts'));
}
add_action('admin_menu', '_m2l_admin_menu');

// Add response hooks to ajax actions taken from our admin page/javascript
add_action('wp_ajax_m2l_add_position', array('Media2Layout_PositionAdmin', 'add_position'));
add_action('wp_ajax_m2l_del_position', array('Media2Layout_PositionAdmin', 'delete_position'));


