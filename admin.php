<?

/**
 * Functionality for the admin screen in general
 *
 * LICENSE
 * This file is part of Zensor.
 *
 * Zensor is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package    Zensor
 * @author     Edward Dale <scompt@scompt.com>
 * @copyright  Copyright 2007 Edward Dale
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @version    $Id: admin.php 25 2007-06-21 13:07:25Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

/**
 * Common functions used all over the Zensor package
 */
require_once(dirname(__FILE__).'/common.php');

/**
 * A collection of static functions for the admin screen
 */
class Zensor_Admin
{
	
	/**
	 * Uninstalls all traces of the Zensor plugin
	 *
	 * Gets rid of the Zensor database table, Zensor options, and Zensor
	 * user role.
	 */
	function uninstall()
	{
		global $wpdb, $zensor_table;
		
		$query = "DROP TABLE $zensor_table";
		$wpdb->query( $query );

		$zensor_options = Zensor_Common::get_default_options();
		foreach( $zensor_options as $option=>$default_value ) {
			delete_option( $option );
		}
		delete_option( "zensor_db_version");

		// Remove Zensor from the active_plugins list
		$current = get_option('active_plugins');
		array_splice($current, array_search( "zensor/zensor.php", $current), 1 );
		update_option('active_plugins', $current);

        // Removed scheduled notifications
	    wp_clear_scheduled_hook('zensor_author_notification');
	    wp_clear_scheduled_hook('zensor_moderator_notification');
	
		remove_role( 'zensor_moderator' );
	}

	/**
	 * Creates everything Zensor needs to exist
	 *
	 * Creates the Zensor table if it doesn't exist.  Also adds all the Zensor
	 * options and the user role.  All posts/pages are added to the table
	 * with a default status of awaiting.  Called using the
	 * 'activate_zensor/zensor.php' action hook.
	 */
	function activate()
	{
		global $zensor_table, $zensor_db_version, $wpdb;

        // activate is called before init, so we need to load translations here too
    	load_plugin_textdomain('zensor', PLUGINDIR.'/zensor/gettext');

		// Must build $zensor_table here because init is not called for new plugins
		$zensor_table = $wpdb->prefix . $zensor_table;

		if($wpdb->get_var("show tables like '$zensor_table'") != $zensor_table) {

			$sql = "CREATE TABLE $zensor_table (
			  post_id bigint(20) default NULL,
			  moderation_status enum('".ZENSOR_AWAITING."','".ZENSOR_APPROVED."','".ZENSOR_REJECTED."') NOT NULL default '".ZENSOR_AWAITING."',
			  moderator_id bigint(20) default NULL,
			  last_updated timestamp NOT NULL default NOW() on update CURRENT_TIMESTAMP,
			  message mediumtext,
              notified enum('Y','N') NOT NULL default 'N',
			  UNIQUE KEY post_id (post_id)
			);";
		
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			dbDelta($sql);

			add_option("zensor_db_version", $zensor_db_version);

			$zensor_options = Zensor_Common::get_default_options();
			foreach( $zensor_options as $option=>$value ) {
				add_option( $option, $value);
			}
		
			add_role( 'zensor_moderator', "Zensor " . __('Moderator', 'zensor') , array('read'=>1, 'zensor_moderate'=>1) );
			$admin_role = get_role('administrator');
			$admin_role->add_cap('zensor_moderate');
		}
		
		$query = <<<done
    		INSERT INTO {$zensor_table} (post_id, moderation_status, message, notified)
                SELECT ID, 'awaiting', 'Activation', 'N' FROM {$wpdb->posts} 
                LEFT JOIN {$zensor_table} on {$wpdb->posts}.ID={$zensor_table}.post_id 
                WHERE ({$wpdb->posts}.post_type='post' OR {$wpdb->posts}.post_type='page') 
                AND {$zensor_table}.moderation_status IS NULL;
done;
        $wpdb->query( $query );
		
		Zensor_Admin::schedule_notifications();
	}
	
	/**
	 * Schedules the 'zensor_daily_mail' hook for midnight of the current day
	 *
	 * If the hook isn't already scheduled, schedule it to occur daily 
	 * at midnight.
	 */
	function schedule_notifications()
	{
        foreach( array('author', 'moderator') as $notification_type) {
    	    wp_clear_scheduled_hook("zensor_{$notification_type}_notification");
    	    $freq = get_option( "zensor_{$notification_type}_notification_frequency" );

            if( in_array( $freq, array('daily', 'hourly') ) ) {
                    wp_schedule_event( time(), $freq,  "zensor_{$notification_type}_notification" );
                    remove_action('shutdown', array('Zensor_Admin', "do_{$notification_type}_notifications"));
            }
        }
	}
	
	/**
	 * Looks at the zensor_author_notifications option and notifies the authors
	 * of all of the posts stored there.
	 */
	function do_author_notifications() {
	    global $wpdb, $zensor_table;
	    
        $notifications = get_option('zensor_author_notifications');
        
        $default_body = get_option('zensor_author_email_body');
        $subject = '['.get_option('blogname').'] '. __('Zensor Notification', 'zensor');
        $default_headers = "MIME-Version: 1.0\n" .
			"From: " . apply_filters('wp_mail_from', "wordpress@" . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']))) . "\n" . 
			"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
		
        foreach( array_unique($notifications) as $post_id ) {
            // Gather data
            $author_email = $wpdb->get_var("SELECT user_email from {$wpdb->users} JOIN {$wpdb->posts} ON {$wpdb->users}.ID={$wpdb->posts}.post_author WHERE {$wpdb->posts}.ID=$post_id");
            $mod_email = $wpdb->get_var("SELECT user_email from {$wpdb->users} JOIN $zensor_table ON {$wpdb->users}.ID=$zensor_table.moderator_id WHERE $zensor_table.post_id=$post_id");
            $mod_info = new Zensor_Info($post_id);
            
            // Build up the email body
            $body = Zensor_Common::replace_tags($default_body, $mod_info);
            $subject = '['.get_option('blogname').'] '. __('Zensor Notification', 'zensor');

            $headers = $default_headers .  "Reply-To: $mod_email\n";
            @wp_mail( $author_email, $subject, $body, $headers );
        }
        
        update_option('zensor_author_notifications', array());
	}

	/**
	 * Sends an email to the moderaters if there are posts in the queue
	 */
	function do_moderator_notifications()
	{
	    global $wpdb, $zensor_table;
	    
	    $query = <<<done
SELECT COUNT(*) FROM $zensor_table 
JOIN {$wpdb->posts} ON $zensor_table.post_id={$wpdb->posts}.ID 
WHERE $zensor_table.moderation_status='awaiting' 
AND $zensor_table.notified='N' 
AND {$wpdb->posts}.post_status='publish'
done;
	    $unnotified_count = $wpdb->get_var($query);
	    if($unnotified_count > 0) {
	        $notified = array();
            $query = <<<done
SELECT $zensor_table.post_id FROM $zensor_table 
JOIN {$wpdb->posts} ON $zensor_table.post_id={$wpdb->posts}.ID 
WHERE $zensor_table.moderation_status='awaiting' 
AND {$wpdb->posts}.post_status='publish'
done;
    		$post_ids = $wpdb->get_col($query);
    		$default_body = get_option('zensor_moderator_email_body');

            foreach( $post_ids as $post_id ) {
                $notified []= $post_id;
                $mod_info = new Zensor_Info($post_id);

                // Build up the email body
                $body = Zensor_Common::replace_tags($default_body, $mod_info);

                $moderator_emails []= $body;
            }
        
            if( !empty($moderator_emails) ) {
                $subject = '['.get_option('blogname').'] '. __('Zensor Notification', 'zensor');
                $body = implode("\n------------------------------\n", $moderator_emails);
                foreach( Zensor_Common::get_moderator_emails() as $email ) {
                    @wp_mail( $email, $subject, $body );
                }
            }
            
            $notified_ids = implode(',', $notified);
            $wpdb->query( "UPDATE $zensor_table SET notified='Y' WHERE post_id IN ($notified_ids)" );
        }        
	}

	/**
	 * Plugs Zensor into the admin menu structure
	 *
	 * Called by the 'admin_menu' action hook.
	 */
	function admin_menu()
	{
		global $zensor_table, $wpdb;

		// Get the number of posts in the moderation system so it can be part of the menu
		$counts = Zensor_Common::get_counts();
		$count = $counts[ZENSOR_AWAITING] + $counts[ZENSOR_REJECTED];
	
		// Add a Zensor menu underneath the options and management page
	    add_options_page('Zensor', 'Zensor', 'manage_options', 'zensor_admin_options_page', array('Zensor_Options', 'admin_options_page') );
	    $page = add_management_page('Zensor', "Zensor (<span id=\"zensor-count\">$count</span>)", 'zensor_moderate', 'zensor_admin_manage_page', array('Zensor_Manage', 'admin_manage_page') );

		// Add some scripts and stylesheets to the admin section
		add_action("admin_print_scripts-$page", array('Zensor_Admin', 'scripts') );
		add_action("admin_head", array('Zensor_Admin', 'styles') );
	}

	/**
	 * Prints a stylesheet line
	 * 
	 * Called by the 'admin_head' action hook.
	 */
	function styles()
	{
		echo '<style type="text/css">@import url('.trailingslashit(get_option('siteurl')).PLUGINDIR.'/zensor/zensor.css);</style>';
	}

	/**
	 * Enqueues a script for the admin screen
	 *
	 * Called by the 'admin_print_scripts' action hook.
	 */
	function scripts()
	{
	    ?>
    	    <script type="text/javascript">
    	        var zensor_preview_on="<?php _e('Preview On', 'zensor') ?>";
    	        var zensor_preview_off="<?php _e('Preview Off', 'zensor') ?>";
            </script>
	    <?php
	    
		wp_enqueue_script( 'prototype');
		wp_enqueue_script( 'zensor', trailingslashit(get_option('siteurl')).PLUGINDIR.'/zensor/zensor.js');
	}

	/**
	 * Handles POSTs from the options screen that do admin-type things
	 * 
	 * Such admin-type things include:
	 * - Uninstalling the script
	 * - Making sure the daily email is scheduled
	 */
	function handle_posts()
	{
		if( isset( $_POST['zensor_uninstall'] ) ) {
			check_admin_referer('zensor_uninstall');
			if( current_user_can( 'edit_plugins' ) ) {
				Zensor_Admin::uninstall();
				wp_redirect('plugins.php?deactivate=true');
			} else {
				wp_die(__('You are not allowed to edit plugins.', 'zensor'));
			}
		} else if( isset($_POST['zensor_reset_options'])) {
		    check_admin_referer('zensor_reset-options');
		    Zensor_Admin::schedule_notifications();
		} else	if ( isset($_POST['zensor_update_options']) ) {
			check_admin_referer('zensor_update-options');
            update_option( 'zensor_author_notification_frequency', $_POST['zensor_author_notification_frequency'] );
            update_option( 'zensor_moderator_notification_frequency', $_POST['zensor_moderator_notification_frequency'] );
		    Zensor_Admin::schedule_notifications();
		}
	}
	
	/**
	 * Checks if Zensor has just been upgrade.  If so, upgrades the tables.
	 */
	function check_upgrade()
	{
	    global $zensor_db_version, $wpdb, $zensor_table;
	    
        if( get_option('zensor_db_version') != $zensor_db_version ) {
			$sql = "CREATE TABLE $zensor_table (
			  post_id bigint(20) default NULL,
			  moderation_status enum('".ZENSOR_AWAITING."','".ZENSOR_APPROVED."','".ZENSOR_REJECTED."') NOT NULL default '".ZENSOR_AWAITING."',
			  moderator_id bigint(20) default NULL,
			  last_updated timestamp NOT NULL default NOW() on update CURRENT_TIMESTAMP,
			  message mediumtext,
              notified enum('Y','N') NOT NULL default 'N',
			  UNIQUE KEY post_id (post_id)
			);";
		
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			dbDelta($sql);

            switch( get_option('zensor_db_version') ) {
                case "0.5":
        			wp_clear_scheduled_hook('zensor_daily_email');

                    $defaults = Zensor_Common::get_default_options();
    			    add_option('zensor_author_notification_frequency',    $defaults['zensor_author_notification_frequency']);
        			add_option('zensor_moderator_notification_frequency', $defaults['zensor_moderator_notification_frequency']);
        			add_option('zensor_author_notifications',             $defaults['zensor_author_notifications'] );
        			add_option('zensor_author_email_body',                $defaults['zensor_author_email_body'] );
        			update_option('zensor_moderator_email_body',          $defaults['zensor_moderator_email_body'] );
        			
        			foreach( array('zensor_approval_email_subject','zensor_approval_email_body',
        			               'zensor_moderator_email_body','zensor_rejection_email_subject',
        			               'zensor_rejection_email_body') as $option ) {
            			delete_option($options);
        			}
        			
        			// Get rid of any orphaned entries.  These are taken care of in this version.
        			$query = "DELETE FROM $zensor_table WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})";
        			$wpdb->query($query);

        			// assume any posts that are not awaiting moderation have already been notified
        			$query = "UPDATE $zensor_table SET notified='Y' WHERE moderation_status!='".ZENSOR_AWAITING."'";
        			$wpdb->query($query);
        			break;
            }
			update_option("zensor_db_version", $zensor_db_version);	    
		}
	}
}

/*
 * Hook into the init action to initialize things at the right time.
 */
if( function_exists('add_action' ) )
{
	add_action('init', array('Zensor_Admin', 'check_upgrade'), 2);
	add_action('init', array('Zensor_Admin', 'handle_posts'), 2);
	add_action('admin_menu', array('Zensor_Admin', 'admin_menu'));
	add_action('activate_zensor/zensor.php', array('Zensor_Admin', 'activate'));
	add_action('zensor_author_notification', array('Zensor_Admin', 'do_author_notifications'));
	add_action('zensor_moderator_notification', array('Zensor_Admin', 'do_moderator_notifications'));
    if( get_option('zensor_author_notification_frequency') == 'immediately' )
	    add_action('shutdown', array('Zensor_Admin', 'do_author_notifications'));
    if( get_option('zensor_moderator_notification_frequency') == 'immediately' )
	    add_action('shutdown', array('Zensor_Admin', 'do_moderator_notifications'));
	// TODO: Maybe schedule an event for 5 minutes in the future to catch any other moderations/posts somebody may be doing
}

?>