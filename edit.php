<?php

/**
 * Functionality for when posts/pages are being edited
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
 * @version    $Id: edit.php 24 2007-06-21 13:06:24Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

/**
 * Common functions used all over the Zensor package
 */
require_once(dirname(__FILE__).'/common.php');

/**
 * Functionality for when posts/pages are being edited
 */
class Zensor_Edit
{
	
	/**
	 * Shows a dropdown box containing all the Zensor statuses with counts
	 *
	 * Works with Zensor_Common::where to allow the user to filter out posts
	 * based on Zensor status.  Displayed using the 'restrict_manage_posts'
	 * action hook at the top of the manage posts page.
	 */
	function restrict_manage_posts()
	{
		global $wpdb, $zensor_table;
	
		$counts = Zensor_Common::get_counts( " AND {$wpdb->posts}.post_type='post' ");
	
	?>
		<form name="zensor_statusform" id="zensor_statusform" action="" method="get">
			<fieldset>
			<legend>Zensor <?php _e('Status'); ?> &hellip;</legend>
			<select name='zensor_status' id='zensor_status' class='postform'>
			<option value=""><?php _e('All'); ?></option>
	<?php
		// Don't display the 'total' that comes from Zensor_Common::get_counts
		foreach( array_slice($counts,0,3) as $status => $count ) {
			$selected = $_GET['zensor_status'] == $status ? 'selected="selected"' : '';
			echo '<option value="' . $status . '" ' . $selected . '>' . __(ucfirst($status), 'zensor') . '&nbsp;&nbsp;(' . $count . ')</option>';
		}
	?>
			</select>
			<input type="submit" name="submit" value="<?php _e('Show', 'zensor') ?>" class="button" /> 
			</fieldset>
		</form>
	<?php
	}

	/**
	 * Show a dbx box with a single text area for a note/message
	 *
	 * Works with Zensor_Edit::publish to allow the user to store a note or 
	 * message that details what was done with the edit.  Displayed using the
	 * 'dbx_sidebar' action hook on the side of the edit pages/posts page.
	 */
	function dbx_sidebar()
	{
		global $post;
	?>
		<fieldset id="zensor-div" class="dbx-box">
		<h3 class="dbx-handle"><?php _e('Zensor Note', 'zensor'); ?></h3>
		<div class="dbx-content">
			<textarea name="zensor_message" style="width: 90%;" rows="3" cols="50"><?php
				if($post && $post->ID) {
					$mod_info = new Zensor_Info($post->ID);
					if($mod_info->message) echo $mod_info->message;
				}
			?></textarea>
		</div>
		</fieldset>
	<?php
	}

	/**
	 * Adds a column to the manage posts page for Zensor
	 *
	 * Works with Zensor_Edit::manage_posts_custom_column to show a column
	 * that displays the Zensor status for posts.  Displayed using the
	 * 'manage_posts_columns' filter hook.
	 *
	 * @param array $columns The existing columns for manage posts page
	 * @return array The previous columns, plus a column for Zensor status
	 */
	function manage_posts_columns($columns)
	{
		$columns['zensor'] = __("Moderation", "zensor");
		return $columns;
	}

	/**
	 * Displays the Zensor status for the custom column on the manage posts page
	 *
	 * Works with Zensor_Edit::manage_posts_columns to display the Zensor
	 * status of the post.  Displayed using the 'manage_posts_custom_column'
	 * action hook.  Status is displayed in a color-coded div.
	 *
	 * @param string $column_name The name of the custom column.  All we care
	 * about is 'zensor'.
	 * @param int $id The id of the post being displayed for this row.
	 */
	function manage_posts_custom_column($column_name, $id)
	{
		if( $column_name == 'zensor' ) {
			$mod_info = new Zensor_Info($id);
			if( $mod_info->is_rejected() ) {
				echo '<div style="color:#c00;">'. __("Rejected", "zensor") .'</div>';
			} else if( $mod_info->is_awaiting() ) {
				echo '<div style="color:#cc0;">'. __("Awaiting", "zensor") .'</div>';
			} else {
				echo '<div style="color:#0c0;">'. __("Approved", "zensor") .'</div>';
			}
		}
	}

	/**
	 * Displays a message beneath the edit form on the posts and pages edit page
	 *
	 * Displayed using the 'edit_form_message' action hook.  The message details
	 * what will happen when the post/page is published depending on its current
	 * status.  Drafts and new posts/pages will get a particular message and
	 * all others get a message depending on status.
	 */
	function edit_form_message()
	{
		global $post;
	
		if( $post->ID && $post->post_status != 'draft' ) {
			$mod_info = new Zensor_Info( $post->ID );
			$status = $mod_info->get_status();
			$message = get_option( "zensor_{$status}_page_edit_message" );
			$message = Zensor_Common::replace_tags($message, $mod_info);
			echo $message;
			
		} else {
			$message = get_option( "zensor_new_page_edit_message" );
			$message = Zensor_Common::replace_tags($message);
			echo $message;
		}
	}

	/**
	 * Creates a record in the Zensor table and emails the moderators
	 *
	 * Gets the note from Zensor_Edit::dbx_sidebar and inserts it, along
	 * with the post_id into the Zensor table.  The moderation status of the
	 * new post/page is always 'awaiting'.
	 *
	 * @param int $post_id The id of the brand new post
	 */
	function publish( $post_id )
	{
		// Create a Zensor_Info object to insert a record in the database
		$message = empty($_POST['zensor_message']) ? __('Empty message', 'zensor') : $_POST['zensor_message'];
		$mod_info = new Zensor_Info( $post_id, $message );

        // XXX: Send email to moderators
	}

	/**
	 * When a post is deleted, make sure it gets deleted from zensor also.
	 * 
	 * Called using the 'delete_post' action.
	 * 
	 * @param int $post_id The id of the post to be deleted
	 */
	function delete_post( $post_id ) {
		global $zensor_table, $wpdb;

	    $wpdb->query( "DELETE FROM $zensor_table WHERE post_id=$post_id" );
	}
}

/*
 * Hook into the init action to initialize things at the right time.
 */
if( function_exists('add_filter') && function_exists('add_action') ) 
{

    // Delete posts from zensor when they're deleted from wordpress
    add_action('delete_post', array('Zensor_Edit', 'delete_post') );
    
	// When things become published, run the publish function
	// TODO: Should this be save_post?
	add_action('publish_post', array('Zensor_Edit', 'publish') );
	add_action('publish_page', array('Zensor_Edit', 'publish') );

	// Add a box for notes to the sidebar of the post/page editing page
	add_action('dbx_post_sidebar', array('Zensor_Edit', 'dbx_sidebar') );
	add_action('dbx_page_sidebar', array('Zensor_Edit', 'dbx_sidebar') );

	// Add the message underneath the edit form of the editing page
	add_action('edit_form_advanced', array('Zensor_Edit', 'edit_form_message') );
	add_action('edit_page_form', array('Zensor_Edit', 'edit_form_message') );

	// Add the Zensor status column to the manage posts/pages screens
	add_filter('manage_posts_columns', array('Zensor_Edit', 'manage_posts_columns') );
	add_action('manage_posts_custom_column', array('Zensor_Edit', 'manage_posts_custom_column'), 10, 2);
	add_filter('manage_pages_columns', array('Zensor_Edit', 'manage_posts_columns') );
	add_action('manage_pages_custom_column', array('Zensor_Edit', 'manage_posts_custom_column'), 10, 2);

	// Allow the posts to be filtered based on Zensor status
	add_action('restrict_manage_posts', array('Zensor_Edit', 'restrict_manage_posts') );
}

?>