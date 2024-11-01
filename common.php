<?php

/**
 * Common functions used all over the Zensor package
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
 * @version    $Id: common.php 21 2007-06-21 12:55:01Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

define('ZENSOR_AWAITING', 'awaiting');
define('ZENSOR_REJECTED', 'rejected');
define('ZENSOR_APPROVED', 'approved');

/**
 * A collection of static functions used throughout the plugin
 */
class Zensor_Common
{
	
	/**
	 * @return array All of Wordpress options used by Zensor
	 */
	function get_options()
	{
		return array(
			'zensor_awaiting_moderation_message',
			'zensor_rejected_moderation_footer',
			'zensor_awaiting_moderation_footer',

			'zensor_rejected_page_edit_message',
			'zensor_approved_page_edit_message',
			'zensor_awaiting_page_edit_message',
			'zensor_new_page_edit_message',

			'zensor_author_email_body',
			'zensor_moderator_email_body'
		);
	}
	
	/**
	 * @return array All of the Wordpress options used by Zensor, along with 
	 * their localized default values.
	 */
	function get_default_options()
	{
		return array(
			'zensor_awaiting_moderation_message' => __('<h3>Awaiting Moderation</h3>', 'zensor'),
			'zensor_rejected_moderation_footer'  => __('<p>This %PAGE/POST% has been rejected by <em>%MODERATOR_NAME%</em> for the reason below.  It is now awaiting re-publication by the original author.</p>\n<p><b>Reason:</b> %MESSAGE%</p>', 'zensor'),
			'zensor_awaiting_moderation_footer'  => __('<p>This %PAGE/POST% is awaiting <a href=\"%THE_LINK%\">moderation</a>, potentially by YOU!</p>', 'zensor'),

			'zensor_rejected_page_edit_message' => __('<h3>This %PAGE/POST% has been rejected by the moderator <em>%MODERATOR_NAME%</em> for the reason below.  Any changes you make here will be resubmitted for approval by a moderator before becoming visible to the public.<h3>\n<p><b>Reason:</b> %MESSAGE%</p>', 'zensor'),
			'zensor_approved_page_edit_message' => __('<h3>This %PAGE/POST% has already been approved by a moderator.  Any changes that you make will not be visible to the public until a moderator has approved them.</h3>', 'zensor'),
			'zensor_awaiting_page_edit_message' => __('<h3>This %PAGE/POST% is already awaiting moderation.  The content you see here is not visible to the public and any changes that are made will not be visible until a moderator has approved them.</h3>', 'zensor'),
			'zensor_new_page_edit_message'      => __('<h3>When published, this %PAGE/POST% will not be displayed to the public until a moderator has approved it.</h3>', 'zensor'),

			'zensor_author_email_body'    => __("Your %PAGE/POST% (%PERMALINK%) entitled %TITLE% has been %STATUS% by %MODERATOR_NAME%.  The following message was given:\n\n%MESSAGE%", 'zensor'),
			'zensor_moderator_email_body' => __("A %PAGE/POST% (%THE_LINK%) entitled %TITLE% has been posted by %AUTHOR_NAME%.  The following message was given:\n\n%MESSAGE%", 'zensor'),
			
			'zensor_author_notification_frequency'    => 'immediately',
			'zensor_moderator_notification_frequency' => 'immediately',
			'zensor_author_notifications'             => array()
		);
	}
	
	/**
	 * Joins the Zensor table to the SQL query used by Wordpress
	 *
	 * Triggered by the 'posts_join' filter hook.
	 *
	 * @param string $join The original SQL join clause
	 * @return string The SQL join clause with the Zensor table
 	 */
	function join( $join )
	{
		global $wpdb, $zensor_table;

		$join .= " LEFT JOIN $zensor_table ON {$wpdb->posts}.ID=$zensor_table.post_id ";

		return $join;
	}

	/**
	 * Modifies Wordpress's SQL where clause
	 *
	 * In the admin section, it modifies the where clause enabling the moderation
	 * status filtering provided by Zensor_Edit::restrict_manage_posts.  Outside
	 * the admin section, it prevents posts from being displayed in the non-single
	 * views (listings of posts). Triggered by the 'posts_where' filter hook.
	 *
	 * @param string $where The original SQL where clause
	 * @param string The SQL where clause with the Zensor table
	 */
	function where( $where )
	{
		global $zensor_table;

		if( is_admin() ) {
			if( isset( $_GET['zensor_status'] ) && !empty($_GET['zensor_status']) ) {
				$where .= " AND $zensor_table.moderation_status='{$_GET['zensor_status']}' ";
			}
		} else {
			if ( !current_user_can( 'zensor_moderate' ) && !(is_single() || is_page()) ) {
				$where .= " AND $zensor_table.moderation_status='".ZENSOR_APPROVED."' ";
			}
		}

		return $where;
	}

	/**
	 * Returns counts of the number of posts/pages in the Zensor system
	 *
	 * @param string $where Additional where clause statements
	 * @return array Associative array of Zensor moderation status to the 
	 * number of posts/pages in the system for that status.  Keys are 
	 * ZENSOR_APPROVED, ZENSOR_REJECTED, ZENSOR_AWAITING, 'total'.
	 */
	function get_counts($where='')
	{
		global $wpdb, $zensor_table;
	
		$query = "SELECT $zensor_table.moderation_status AS status, COUNT($zensor_table.post_id) AS count ".
		         "FROM $zensor_table JOIN {$wpdb->posts} ON $zensor_table.post_id={$wpdb->posts}.ID ".
		         "WHERE ({$wpdb->posts}.post_type='post' OR {$wpdb->posts}.post_type='page') ".
		         "AND {$wpdb->posts}.post_status='publish' $where ".
				 "GROUP BY $zensor_table.moderation_status";

		$results = $wpdb->get_results( $query );

		// Prime the array in case there are some empty statuses in the db
		$counts = array(ZENSOR_APPROVED=>0, ZENSOR_REJECTED=>0, ZENSOR_AWAITING=>0, 'total'=>0);
		foreach( $results as $row ) {
			$counts[$row->status] = intval($row->count);
			$counts['total'] += $row->count; // Accumulate the total
		}
	
		return $counts;
	}

	/**
	 * Emails the moderators that a post was modified and needs moderation
	 *
	 * The email is made up of the following two Wordpress options:
	 * 'zensor_moderator_email_subject' & 'zensor_moderator_email_body'
	 *
	 * @param int $post_id The ID of the post that was modified
	 * @param Zensor_Info $mod_info The Zensor_Info of the post that was modified
	 */
	function email_moderators( $post_id, $mod_info )
	{
	    $emails = Zensor_Common::get_moderator_emails();
		$email_subject = Zensor_Common::replace_tags(get_option( "zensor_moderator_email_subject" ), $mod_info);
		$email_body = Zensor_Common::replace_tags(get_option( "zensor_moderator_email_body" ), $mod_info);

		foreach( $emails as $mod_email ) {
			wp_mail( $mod_email, $email_subject, $email_body );
		}
	}

	/**
	 * Looks at the Wordpress users table to find the email of users who can moderate
	 *
	 * Uses the wp_cache to cache the emails found as this could be an
	 * expensive operation.  Finds users by checking if they have the
	 * 'zensor_moderate' capability.
	 *
	 * @return array The moderator emails
	 */
	function get_moderator_emails()
	{
		if ($emails = wp_cache_get('moderator_emails', 'zensor')) {
	        return $emails;
	    } else {
	        global $wpdb;
			$emails = array();
	        $ids = $wpdb->get_col('SELECT ID from ' . $wpdb->users);
			foreach( $ids as $userid ) {
				$tmp_user = new WP_User($userid);
				if( $tmp_user->has_cap( 'zensor_moderate') ) {
					$emails[] = $tmp_user->user_email;
				}
			}
			$emails = array_unique($emails); // trim out any dupes
	        wp_cache_set('moderator_emails', $emails, 'zensor');
	        return $emails;
	    }
	}

	/**
	 * Replaces some tags that can be used to add more context to messages
	 *
	 * The following tags are replaced:
	 *       %THE_LINK% -> A link to the moderation page of the current post 
	 *      %PAGE/POST% -> 'page' or 'post' depending on what the current thing is
	 * %MODERATOR_NAME% -> The name of the moderator who moderated the current post
	 *        %MESSAGE% -> The message given by the post author or moderator
	 *          %TITLE% -> Title of the current post
	 *      %PERMALINK% -> Permalink to the post
	 *    %AUTHOR_NAME% -> Author of the post
	 *         %STATUS% -> Zensor status of the post
	 *
	 * @param string $message The message containing tags to be replaced.
	 * @param Zensor_Info $mod_info The Zensor_Info of the current post, if available
	 * @param array $replacements Replacements that should take precedence over others
	 * @return string The message with all tags replaced with valid values
	 */
	function replace_tags( $message, $mod_info=NULL, $replacements=array() )
	{
		global $post;
		
		if( $post ) {
		    $the_post = $post;
		} else if( $mod_info ) {
		    $the_post = get_post($mod_info->post_id);
		}
	
		$message = stripslashes( $message );
	
	    $context_replacements = array();
		if( $the_post ) {
		    $author = get_userdata($the_post->post_author);

		    $context_replacements['%THE_LINK%']    = Zensor_Common::moderation_page_link( $post->ID );
		    $context_replacements['%PAGE/POST%']   = $the_post->post_type == 'page' ? __('page', 'zensor') : __('post', 'zensor');
		    $context_replacements['%TITLE%']       = $the_post->post_title;
		    $context_replacements['%AUTHOR_NAME%'] = $author->display_name;
		}
		if( $mod_info ) {
		    $context_replacements['%MODERATOR_NAME%'] = $mod_info->moderator_name;
		    $context_replacements['%MESSAGE%']        = $mod_info->message;
		    $context_replacements['%THE_LINK%']       = Zensor_Common::moderation_page_link( $mod_info->post_id );
		    $context_replacements['%PERMALINK%']      = get_permalink($mod_info->post_id);
		    $context_replacements['%STATUS%']         = __($mod_info->get_status(), 'zensor');
		}
		
		$default_replacements = array("%TITLE%"          => __('Untitled', 'zensor'),
		                              "%PAGE/POST%"      => __('page', 'zensor'),
                  		              "%MODERATOR_NAME%" => __('A moderator', 'zensor'),
		                              "%MESSAGE%"        => __('No message.', 'zensor'),
		                              "%THE_LINK%"       => Zensor_Common::uri());
		                              
		$replacements = array_merge($default_replacements, $context_replacements, $replacements);
        $message = str_replace(array_keys($replacements), array_values($replacements), $message);

		return $message;
	}

	/**
	 * Returns a direct link to the moderation page for a particular post
	 *
	 * @param int $post_id The ID of the post to link to
	 * @return string A link to the moderation page for a particular post
	 */
	function moderation_page_link( $post_id )
	{
		return Zensor_Common::uri() . "&id=$post_id#zensor-$post_id";
	}

	/**
	 * @return string The uri of the main Zensor management page
	 */
	function uri()
	{
	    return get_settings('siteurl') . '/wp-admin/admin.php?page=zensor_admin_manage_page';
	}
	
}

/**
 * A class that captures the information managed by Zensor and allows its modification
 */
class Zensor_Info {

	/**
	 * The name of the last user who moderated this post, or NULL
	 * @var string
	 */
	var $moderator_name = NULL;
	
	/**
	 * The message left by the post owner or moderator, or NULL
	 * @var string
	 */
	var $message = NULL;
	
	/**
	 * The id of the post/page 
	 * @var int
	 */
	var $post_id = NULL;
	
	/**
	 * The Zensor moderation status of the post
	 * Either ZENSOR_AWAITING, ZENSOR_REJECTED, or ZENSOR_APPROVED
	 * @var string
	 */
	var $moderation_status = NULL;

	/**
	 * Creates a Zensor_Info object 
	 *
	 * Looks up the information from the database.  If a Zensor record doesn't
	 * exist for this post, then one is created with the status of 
	 * ZENSOR_AWAITING and optionally a message.  If a message is given then
	 * the Zensor record is updated with this message and the status is set to
	 * ZENSOR_AWAITING.
	 *
	 * @param int $post_id
	 * @param string $message The message to initialize with. Needs to already be escaped.
	 */
	function Zensor_Info($post_id, $message = '')
	{
		global $zensor_table, $wpdb;

		$query = "SELECT $zensor_table.post_id, $zensor_table.message, {$wpdb->users}.display_name, $zensor_table.moderation_status " .
				 "FROM $zensor_table LEFT JOIN {$wpdb->users} ON $zensor_table.moderator_id={$wpdb->users}.ID WHERE post_id = $post_id";
		$ret = $wpdb->get_row( $query );

		if( $ret ) {
			$this->post_id = $ret->post_id;
			$this->moderator_name = $ret->display_name;
            if( empty($message) ) {
                // No message means all we're doing is getting the record
    			$this->message = $ret->message;
    			$this->moderation_status = $ret->moderation_status;
            } else {
                // A message was given, so update the database
                $query = "UPDATE $zensor_table SET ".
                         "moderation_status='".ZENSOR_AWAITING."', ".
                         "notified='N', ".
                         "message='$message' ".
                         "WHERE $zensor_table.post_id=$post_id";
                $ret = $wpdb->query($query);
    			$this->message = $message;
    			$this->moderation_status = ZENSOR_AWAITING;
            }
		} else {
		    // No record in database, so we've got to create one
			$this->post_id = $post_id;
			$this->moderation_status = ZENSOR_AWAITING;
			$this->message = $wpdb->escape($message);

			$query = "INSERT INTO $zensor_table (post_id, moderation_status, notified, message) ".
			         "VALUES ($post_id, '".ZENSOR_AWAITING."', 'N', '{$this->message}')";
			$ret = $wpdb->get_row( $query );
		}
	}

	/**
	 * Changes the status of the post to ZENSOR_APPROVED in the object and databse
	 *
	 * @param int $moderator_id The ID of the moderator who changed the status
	 * @param string $moderator_name The name of the moderator who changed the status
	 * @param string $comment The optional comment/reason given by moderator
	 */
	function approve($moderator_id, $moderator_name, $comment='')
	{
		global $zensor_table, $wpdb;

		$this->moderation_status = ZENSOR_APPROVED;
		$this->moderator_name = $moderator_name;
		$this->message = $wpdb->escape( $comment );
		$query = "UPDATE $zensor_table SET moderator_id=$moderator_id, ".
		         "moderation_status='".ZENSOR_APPROVED."', message='$this->message' ".
				 "WHERE post_id = $this->post_id";
		return @$wpdb->query( $query );
	}

	/**
	 * Changes the status of the post to ZENSOR_REJECTED in the object and databse
	 *
	 * @param int $moderator_id The ID of the moderator who changed the status
	 * @param string $moderator_name The name of the moderator who changed the status
	 * @param string $reason The optional comment/reason given by moderator
	 */
	function reject($moderator_id, $moderator_name, $reason='')
	{
		global $zensor_table, $wpdb;

		$this->moderation_status = ZENSOR_REJECTED;
		$this->moderator_name = $moderator_name;
		$this->message = $wpdb->escape( $reason );
		$query = "UPDATE $zensor_table SET moderator_id=$moderator_id, ".
		         "moderation_status='".ZENSOR_REJECTED."', message='$this->message' ".
		         "WHERE post_id = $this->post_id";
		return @$wpdb->query( $query );
	}

	/**
	 * @return bool True iff moderator_status == ZENSOR_AWAITING
	 */
	function is_awaiting()
	{
		return $this->moderation_status == ZENSOR_AWAITING;
	}

	/**
	 * @return bool True iff moderator_status == ZENSOR_REJECTED
	 */
	function is_rejected()
	{
		return $this->moderation_status == ZENSOR_REJECTED;
	}

	/**
	 * @return bool True iff moderator_status == ZENSOR_APPROVED
	 */
	function is_approved()
	{
		return $this->moderation_status == ZENSOR_APPROVED;
	}
	
	/**
	 * @return string The status of the post.  Either ZENSOR_AWAITING, 
	 * ZENSOR_REJECTED, or ZENSOR_APPROVED.
	 */
	function get_status()
	{
		return $this->moderation_status;
	}
	
}

/*
 * Hook into the join & where clauses
 */
if( function_exists('add_filter') ) 
{
	add_filter( 'posts_join',  array('Zensor_Common', 'join') );
	add_filter( 'posts_where', array('Zensor_Common', 'where') );
}

?>