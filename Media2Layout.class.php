<?php
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

class Media2Layout {

    public static $VERSION = '0.1.2';
    public static $DB_VERSION = '0.1.2c';

    public static $TBL_NAME = 'm2layout';
    public static $TBL_POS_NAME = 'm2layout_positions';

    /**
     * Install the Media2Layout database tables
     * @return null
     */
    public static function install() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

        $table_name = $wpdb->prefix . self::$TBL_POS_NAME;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = 'CREATE TABLE ' . $table_name . ' (
                        position_id mediumint(9) NOT NULL AUTO_INCREMENT,
                        position_label varchar(50) NOT NULL,
                        PRIMARY KEY  (position_id),
                        UNIQUE KEY position_label(position_label)
                    );';

            dbDelta($sql);

            $wpdb->query("INSERT INTO " . $table_name .
                " (position_label) VALUES ('campaign')");

            $wpdb->query("INSERT INTO " . $table_name .
                " (position_label) VALUES ('header')");


            $table_name = $wpdb->prefix . self::$TBL_NAME;
            $sql = 'CREATE TABLE ' . $table_name . ' (
                        media2layout_id mediumint(9) NOT NULL AUTO_INCREMENT,
                        post_id bigint(20) UNSIGNED NOT NULL,
                        media_post_id bigint(20) UNSIGNED NOT NULL,
                        position_id mediumint(9) NOT NULL,
                        url varchar(512),
                        PRIMARY KEY  (media2layout_id),
                        UNIQUE KEY post_id_position (post_id, position_id),
                        KEY post_id (post_id)
                    );';

            dbDelta($sql);

            add_option("media2layout_db_version", self::$DB_VERSION);
        }


        $installed_ver = get_option("media2layout_db_version");
        if ($installed_ver != self::$DB_VERSION) {
            $table_name = $wpdb->prefix . self::$TBL_NAME;
            $sql = 'CREATE TABLE ' . $table_name . ' (
                        media2layout_id mediumint(9) NOT NULL AUTO_INCREMENT,
                        post_id bigint(20) UNSIGNED NOT NULL,
                        media_post_id bigint(20) UNSIGNED NOT NULL,
                        position_id mediumint(9) NOT NULL,
                        url varchar(512),
                        onclick varchar(512),
                        analytics_tag varchar(512),
                        PRIMARY KEY  (media2layout_id),
                        UNIQUE KEY post_id_position (post_id, position_id),
                        KEY post_id (post_id)
                    );';

            dbDelta($sql);

            update_option("media2layout_db_version", self::$DB_VERSION);

            //TODO Future upgrades go here

        }

    }

    /**
     * Get the image tag for a banner image for specified Page
     *
     * @param string $pos_lbl
     * @param int $page_id
     * @return string
     */
    public static function get_banner_img_tag($pos_lbl, $page_id=null) {
        global $wpdb;

        if (empty($page_id)) {
            // Grab current page ID
            $page_id = get_the_ID();
            if (empty($page_id)) {
                if (!have_posts()) {
                    return null;
                }
                the_post();
                $page_id = get_the_ID();
                rewind_posts();
            }
        }

        // Look up media for $page_id
        $src = $wpdb->get_row($wpdb->prepare(
            'SELECT guid, url, onclick, analytics_tag FROM ' . $wpdb->prefix . self::$TBL_NAME .
            ' INNER JOIN ' . $wpdb->prefix . self::$TBL_POS_NAME . ' USING(position_id) ' .
            ' INNER JOIN ' . $wpdb->posts . ' ON ID = media_post_id' .
            ' WHERE post_id=%d AND LOWER(position_label)=%s'
            , $page_id, strtolower($pos_lbl)
        ));

        if (!empty($src)) {
            $tag = "<img src=\"{$src->guid}\" />";
            if (!empty($src->url) || !empty($src->onclick) || !empty($src->analytics_tag)) {
                $click = array();
                if (!empty($src->onclick))
                    $click[] = $src->onclick;
                if (!empty($src->analytics_tag))
                    $click[] = "pageTracker._trackPageview('{$src->analytics_tag}')";

                if (!empty($click))
                    $click = implode(';', $click);

                $tag = "<a onclick=\"{$click}\" href=\"{$src->url}\">{$tag}</a>";
            }
            return $tag;
        }

        return null;//'&lt;Image Tag Here for page '.$page_id.', position '.$pos_lbl.'/&gt;';
    }

    /**
     * Print the banner for this position and page in a <div>
     *
     * @param string $pos_lbl
     * @param int $page_id
     */
    public static function banner($pos_lbl, $page_id=null) {
        $img = self::get_banner_img_tag($pos_lbl, $page_id);
        $css_class = "media2layout_".preg_replace('[^a-zA-Z0-9]', '', $pos_lbl);
        if (!empty($img)) {
            ?>
            <div class="<?=$css_class?>">
                <?=$img ?>
            </div>
            <?php
        }
    }

    /**
     * Called from hook to add controls to the Page edit screen
     *
     * @param mixed $post
     */
    public static function media_positioning_form($post) {
        global $wpdb;

        $positions = $wpdb->get_results(
            "SELECT pos.position_id, position_label, media_post_id, m2l.url, m2l.onclick, m2l.analytics_tag ".
            " FROM " . $wpdb->prefix . self::$TBL_POS_NAME . " pos" .
            " LEFT OUTER JOIN " .  $wpdb->prefix . self::$TBL_NAME . " m2l" .
            "   ON pos.position_id=m2l.position_id AND m2l.post_id = ".$post->ID.
            " ORDER BY UPPER(position_label)");

        $media_images = $wpdb->get_results(
            "SELECT ID, post_title, guid FROM {$wpdb->posts} w
            where post_type='attachment'
            and post_mime_type like '%image%'");

        //print $post->ID;
        //print "<br/>";
        //print_r($positions);
        /*?>
        <table class="widefat">
        <thead>
            <th>Position</th>
            <th>Select Media</th>
            <th>Thumbnail</th>
        </thead>
        <?php*/
        foreach ($positions as $pos) {
            $thumb = '';
            /*$selected = $wpdb->get_row($wpdb->prepare(
                'SELECT media_post_id, url FROM '.$wpdb->prefix . self::$TBL_NAME .
                ' WHERE post_id=%d AND position_id=%d',
                $post->ID, $pos->position_id
            ));*/
            //print_r($selected);
            if ($pos->media_post_id) {
                $thumb = wp_get_attachment_image($pos->media_post_id, array(150, 60));
            }
            ?>
            <table class="widefat" id="media2layout_position_<?php print $pos->position_id; ?>">
            <thead>
                <tr>
                    <th colspan="3"><?php print ucfirst(strtolower($pos->position_label)); ?></th>
                </tr>
            </thead>
            <tr>
                <td><b>Select Media:</b></td>
                <td>
                    <select name="media2layout[<?php print $pos->position_id; ?>]">
                    <option value="">[no image]</option>
                    <?php
                    foreach ($media_images as $img) {
                        $opt_sel = ($img->ID == $pos->media_post_id ? 'selected' : '');
                        ?>
                        <option value="<?php print $img->ID; ?>" <?php print $opt_sel; ?>><?php print htmlspecialchars($img->post_title); ?></option>
                        <?php
                    }
                    ?>
                    </select>
                </td>
                <td id="media2layout_preview_<?php print $pos->position_id; ?>">
                    <?= $thumb ?>
                </td>
            </tr>
            <tr>
                <td>Link URL:</td>
                <td colspan="2">
                    <input type="text" name="media2layout_url[<?php print $pos->position_id; ?>]" size="50" maxlength="512" value="<?php print htmlspecialchars($pos->url) ?>" />
                </td>
            </tr>
            <tr>
                <td>OnClick Action:</td>
                <td colspan="2">
                    <input type="text" name="media2layout_onclick[<?php print $pos->position_id; ?>]" size="50" maxlength="512" value="<?php print htmlspecialchars($pos->onclick) ?>" />
                </td>
            </tr>
            <tr>
                <td>Analytics Tag:</td>
                <td colspan="2">
                    <input type="text" name="media2layout_analytics_tag[<?php print $pos->position_id; ?>]" size="50" maxlength="512" value="<?php print htmlspecialchars($pos->analytics_tag) ?>" />
                </td>
            </tr>
            </table>
            <?php
        }
        /*?>
        </table>
        <?php*/
    }

    /**
     * Called from hook to save changes to Page
     *
     * @param unknown_type $post_id
     */
    public static function save($post_id) {
        global $wpdb;

        if (false !== ($parent = wp_is_post_revision($post_id))) {
            $post_id = $parent;
        }

        if (!isset($_POST['media2layout'])) {
            return;
        }

        $mbarr = $_POST['media2layout'];
        if (!is_array($mbarr)) {
            //TODO generate a proper error here?  seems to get triggered on autosaves as well.
            return;
        }

        $_POST['media2layout_url'] = stripslashes_deep($_POST['media2layout_url']);
        $_POST['media2layout_onclick'] = stripslashes_deep($_POST['media2layout_onclick']);
        $_POST['media2layout_analytics_tag'] = stripslashes_deep($_POST['media2layout_analytics_tag']);

        //TODO generate proper error reports for failed queries below

        foreach ($mbarr as $position_id => $media_post_id) {

            $newURL = $_POST['media2layout_url'][$position_id];
            $newOnclick = $_POST['media2layout_onclick'][$position_id];
            $newAtag = $_POST['media2layout_analytics_tag'][$position_id];

            $existing = $wpdb->get_row($wpdb->prepare(
                'SELECT media_post_id, url, onclick, analytics_tag FROM '.$wpdb->prefix . self::$TBL_NAME .
                ' WHERE post_id=%d AND position_id=%d'
                , $post_id, $position_id
            ));

            // existing media ID is unchanged, update attributes if necessary
            if (!empty($existing) && ($media_post_id == $existing->media_post_id)) {
                if ($newURL != $existing->url ||
                    $newOnclick != $existing->onclick ||
                    $newAtag != $existing->analytics_tag) {
                    $wpdb->query($wpdb->prepare(
                        'UPDATE ' . $wpdb->prefix . self::$TBL_NAME .
                        ' SET url=%s, onclick=%s, analytics_tag=%s WHERE post_id=%d AND position_id=%d'
                        , $newURL, $newOnclick, $newAtag, $post_id, $position_id
                    ));
                }
                continue;
            }

            // existing media ID, delete it
            if ($existing) {
                $wpdb->query($wpdb->prepare(
                    'DELETE FROM '.$wpdb->prefix . self::$TBL_NAME .
                    ' WHERE post_id=%d AND position_id=%d'
                    , $post_id, $position_id
                ));
            }
            // insert new media ID
            if (!empty($media_post_id)) {
                $wpdb->query($wpdb->prepare(
                    'INSERT INTO '.$wpdb->prefix . self::$TBL_NAME .
                    ' (post_id, media_post_id, position_id, url, onclick, analytics_tag) VALUES (%d, %d, %d, %s, %s, %s)'
                    , $post_id, $media_post_id, $position_id, $newURL, $newOnclick, $newAtag
                ));
            }
        }
    }

}