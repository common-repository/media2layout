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

class Media2Layout_PositionAdmin
{
    /**
     * Called from hook to add an admin page for editing Positions
     *
     */
    public static function edit_positions() {
        global $wpdb;
        
        $positions = $wpdb->get_results(
            "SELECT position_id, position_label FROM " . $wpdb->prefix . Media2Layout::$TBL_POS_NAME . 
            " ORDER BY UPPER(position_label)");
        ?>
        <div class="wrap">
            <h2>Media2Layout Positions</h2>
            <form id="m2l_position_form" action="<?php bloginfo('wpurl') ?>/wp-admin/admin-ajax.php" 
             method="get" onsubmit="return add_m2lposition(this)">
                <?php wp_nonce_field('update-options') ?>
                <table id="m2l_position_list" class="widefat">
                <thead>
                    <th><?php _e('Position') ?></th>
                    <th><?php _e('Action') ?>
                </thead>
                <?php
                foreach ($positions as $pos) {
                    ?>
                    <tr id="m2l_position_tr_<?php print $pos->position_id ?>">
                        <td><?php print htmlspecialchars($pos->position_label); ?></td>
                        <td><a href="javascript:void(del_m2lposition(<?php print $pos->position_id ?>))">Delete</a></td>
                    </tr>
                    <?php
                }
                ?>
                <tr id="m2l_new_position_tr">
                    <td colspan="2">
                        <?php _e('Add new Position') ?>: <input type="text" size="30" maxlength="50" name="new_pos_lbl"/>
                        <input type="submit" class="button-primary" name="m2l_update" value="<?php _e('Add') ?>" />
                    </td>
                </tr>
                </table>
                
            </form>
        </div>
        <?php
    }
    
    /**
     * Called from hooks to add our admin page javascript
     *
     */
    public static function admin_scripts() {
        wp_enqueue_script('media2layout-admin', 
            path_join(WP_PLUGIN_URL, basename(dirname(__FILE__)).'/m2l-admin.js'));
    }
    
    /**
     * Called to respond to ajax request to add a new Position
     *
     */
    public static function add_position() {
        global $wpdb;
        $errors = array();
        
        if (isset($_POST['action']) && $_POST['action'] == 'm2l_add_position') {
            //print_r($_POST);
            $id = null;
            $lbl = filter_input(INPUT_POST, 'new_pos_lbl', FILTER_SANITIZE_STRING);
            $lbl = strtolower(trim($lbl));
            if (!empty($lbl)) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    'SELECT position_id FROM '.$wpdb->prefix . Media2Layout::$TBL_POS_NAME .
                    ' WHERE position_label=%s'
                    , $lbl
                ));
                if ($existing) {
                    $errors[] = 'The Position "'.$lbl.'" already exists.';
                }
                else {
                    $inserted = $wpdb->query($wpdb->prepare(
                        'INSERT INTO ' . $wpdb->prefix . Media2Layout::$TBL_POS_NAME . 
                        ' (position_label) VALUES (%s)'
                        , $lbl
                    ));
                    if (!$inserted) {
                        $errors[] = 'Failed to save new Position in database.';
                    }
                    else {
                        $id = $wpdb->get_var($wpdb->prepare(
                            'SELECT position_id FROM ' . $wpdb->prefix . Media2Layout::$TBL_POS_NAME . 
                            ' WHERE position_label=%s'
                            , $lbl
                        ));
                    }
                }
            }
            else {
                $errors[] = 'Position label cannot be empty.';
            }
            
            $out = array(
                'id' => $id,
                'label' => $lbl,
                'errors' => implode("\n", $errors)
            );
            
            print json_encode($out);
            exit;
        }
        
        exit;
    }
    
    /**
     * Called to respond to ajax request to delete a Position
     *
     */
    public static function delete_position() {
        global $wpdb;
        $errors = array();
        
        if (isset($_POST['action']) && $_POST['action'] == 'm2l_del_position') {
            $id = filter_input(INPUT_POST, 'position_id', FILTER_VALIDATE_INT);
            if (empty($id)) {
                $errors[] = 'Invalid request.';
            }
            else {
                // Before deleting, make sure no media is assigned this position
                $count = $wpdb->get_var($wpdb->prepare(
                    'SELECT COUNT(media2layout_id) FROM '. $wpdb->prefix . Media2Layout::$TBL_NAME .
                    ' WHERE position_id=%d'
                    , $id
                ));
                if ($count > 0) {
                    $errors[] = 'Cannot delete; that Position has assigned Media on '.$count.' pages.';
                }
                else {
                    // OK to delete
                    $deleted = $wpdb->query($wpdb->prepare(
                        'DELETE FROM '. $wpdb->prefix . Media2Layout::$TBL_POS_NAME .
                        ' WHERE position_id=%d'
                        , $id
                    ));
                }
            }
            
            $out = array('errors' => implode("\n", $errors));
            print json_encode($out);
            exit;
        }
        
        exit;
    }
}