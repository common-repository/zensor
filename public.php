<?php

/**
 * Functionality for public pages that the user interacts with
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
 * @version    $Id: public.php 5 2007-06-12 12:19:07Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

/**
 * Common functions used all over the Zensor package
 */
require_once(dirname(__FILE__).'/common.php');

/**
 * A collection of static functions for public pages.
 */
class Zensor_Public
{

	/**
	 * Censors the content passed into the function depending on status of user
	 *
	 * Modifies the content of the post according to the following algorithm:
	 * 
	 * Previews always get the full content.
	 * Approved posts always get the full content.
	 * Moderators always see the full content plus a footer displaying the
	 *   moderation status.
	 * The owner of the post will always see the full post, along with a 
	 *   message saying if the post is awaiting moderation or already rejected.
	 * Normal users get a message saying the post is awaiting moderation.
	 *
	 * @param string $content the content to be censored
	 * @return string the censored content
	 * @static
	 */
	function censor_content( $content )
	{
		global $post, $page, $user_ID;
	
		if( is_preview() ) return $content;

             if( $post && $post->ID ) $post_id = $post->ID;
        else if( $page && $page->ID ) $post_id = $page->ID;
        else return '';

		$mod_info = new Zensor_Info( $post_id );

		if( $mod_info->is_approved() ) return $content;
		
		if ( current_user_can( 'zensor_moderate' ) ) {
			// User is a moderator

			if( $mod_info->is_awaiting() ) {
				$message = get_option('zensor_awaiting_moderation_footer');
				$message = Zensor_Common::replace_tags( $message, $mod_info );
				return "$content\n\n$message";

			} else if( $mod_info->is_rejected() ) {
				$message = get_option( 'zensor_rejected_moderation_footer');
				$message = Zensor_Common::replace_tags( $message, $mod_info );
				return "$content\n\n$message";

			}
		} else if( $post->post_author == $user_ID ) {
			// User is the post author
			
			// TODO: And if the post has already been rejected?
			$message = get_option( 'zensor_awaiting_moderation_message');
			$message = Zensor_Common::replace_tags( $message, $mod_info );
			return "$content\n\n$message";

		} else {
			// User is just a user
			
			$message = get_option( 'zensor_awaiting_moderation_message');
			return Zensor_Common::replace_tags( $message, $mod_info );
		}
	}
}

/*
 * Hook into the content filters to censor the content at the right time.
 */
if( function_exists('add_filter') ) 
{
	if( !is_admin() )
	{
		add_filter('the_content', array('Zensor_Public', 'censor_content'));
		add_filter('the_content_rss', array('Zensor_Public', 'censor_content'));
	}	
}

?>