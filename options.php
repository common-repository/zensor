<?php

/**
 * Functionality for the options screen
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
 * @version    $Id: options.php 23 2007-06-21 12:56:30Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

/**
 * Common functions used all over the Zensor package
 */
require_once(dirname(__FILE__).'/common.php');

/**
 * Functionality for the options screen
 */
class Zensor_Options
{
	
	/**
	 * Updates the options in the database from the user's submission
	 *
	 * @static
	 */
	function handle_update_options()
	{
		$zensor_options = Zensor_Common::get_options();

		if ( isset($_POST['zensor_update_options']) ) {
			check_admin_referer('zensor_update-options');
		
			foreach( $zensor_options as $option ) {
				if( !empty( $_POST[$option] ) ) {
                    update_option( $option, wp_filter_kses($_POST[$option]) );
				}
			}
		} else if( isset($_POST['zensor_reset_options'])) {
		    check_admin_referer('zensor_reset-options');

		    $zensor_options = Zensor_Common::get_default_options();
			foreach( $zensor_options as $option=>$value ) {
				update_option( $option, $value);
			}
		}
	}

    /**
     * Generates a dropdown select box to select the frequency for the provided option.
     *
     * @param string $name The option name
     */
    function frequency_dropdown($name) {
        $current_value = get_option($name);
        $freqs = array(__('Immediately','zensor')=>'immediately', __('Hourly','zensor')=>'hourly', __('Daily','zensor')=>'daily');
        echo '<select name="'.$name.'" id="'.$name.'">';
        foreach( $freqs as $freq_name=>$freq_tag ) {
            if( $freq_tag == $current_value ) {
    		    echo '<option selected="selected" value="'.$freq_tag.'">'.$freq_name.'</option>';
            } else {
    		    echo '<option value="'.$freq_tag.'">'.$freq_name.'</option>';
            }
        }
        echo "</select>\n";
    }

	/**
	 * Display the options page for the Zensor plugin
	 *
	 * Pulls all the options out of the database and shows them in the form.
	 * Also shows a status message if the options were just updated.
	 *
	 * @static
	 */
	function admin_options_page()
	{
		$zensor_options = Zensor_Common::get_options();
		$messages = array();
		foreach( $zensor_options as $option ) {
			$messages[$option] = stripslashes(get_option($option));
		}
	
	    // TODO: it'd be nice if this message was sent from the handle_update_options
	    // method instead of having another if here.
		if( isset($_POST['zensor_update_options']) ) {
			echo '<div id="message" class="updated fade"><p>';
			_e("Options updated!", "zensor");
			echo '</p></div>';
		} else if( isset($_POST['zensor_reset_options']) ) {
			echo '<div id="message" class="updated fade"><p>';
			_e('Default options reloaded.', 'zensor');
			echo '</p></div>';
		}
    	

	?>
		<div class="wrap"> 
			<form method="post">
			<?php wp_nonce_field('zensor_update-options'); ?>
			<h2><?php _e("Zensor Options", "zensor"); ?></h2> 
	
			<fieldset class="options">
			<legend><?php _e("Notification Frequency", "zensor"); ?></legend>

			<p><?php _e("Notifications to moderators and authors can either be sent immediately or batched together and sent at regular intervals.", "zensor"); ?></p>

			<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
    			<tr>
        			<th scope="row"><?php _e("Author Frequency", "zensor"); ?>:</th>
        			<td><?php Zensor_Options::frequency_dropdown('zensor_author_notification_frequency') ?></td>
    			</tr><tr>
        			<th scope="row"><?php _e("Moderator Frequency", "zensor"); ?>:</th>
        			<td><?php Zensor_Options::frequency_dropdown('zensor_moderator_notification_frequency') ?></td>
    			</tr>
			</table>
			</fieldset>
	<?php if( !empty($_POST['zensor_advance_options'] ) ): ?>
			<p><?php _e("The following fields all contain messages that are displayed or emailed to users.  The tags below can be used to add more context to the messages.", 'zensor'); ?></p>
			<dl>
				<dt>%THE_LINK%</dt>
				<dd><?php _e("A link to the moderation pages for a particular page/post.", 'zensor'); ?></dd>
				<dt>%MODERATOR_NAME%</dt>
				<dd><?php _e("The name of the moderator who last moderated a page/post.", 'zensor'); ?></dd>
				<dt>%MESSAGE%</dt>
				<dd><?php _e("The last message recorded for the page/post.  Can be either a moderator's message or an author's message.", 'zensor'); ?></dd>
				<dt>%PAGE/POST%</dt>
				<dd><?php _e("Prints either 'page' or 'post' depending on whether the object is a page or a post.", 'zensor'); ?></dd>
    			<dt>%TITLE%</dt>
    			<dd><?php _e("Title of the current post", 'zensor'); ?></dd>
    			<dt>%PERMALINK%</dt>
    			<dd><?php _e("Permalink of the post", 'zensor'); ?></dd>
    			<dt>%AUTHOR_NAME%</dt>
    			<dd><?php _e("Author of the post", 'zensor'); ?></dd>
    			<dt>%STATUS%</dt>
    			<dd><?php _e("Zensor status of the post", 'zensor'); ?></dd>
			</dl>
		
			<fieldset class="options">
			<legend><?php _e("Moderator Email", "zensor"); ?></legend>

			<p><?php _e("When a post is published, Zensor notifies the moderators with the following email message. (No HTML)", "zensor"); ?></p>
            <textarea name="zensor_moderator_email_body" 
		              id="zensor_moderator_email_body" 
		              style="width: 98%;" 
		              rows="3" 
		              cols="50"><?php echo $messages['zensor_moderator_email_body']; ?></textarea>
			</fieldset>
	
			<fieldset class="options">
    			<legend><?php _e("Author Email", "zensor"); ?></legend>

    			<p><?php _e("When a post is moderated, the original author is notified with the following email message. (No HTML)", "zensor"); ?></p>
                <textarea name="zensor_author_email_body" 
			              id="zensor_author_email_body" 
			              style="width: 98%;" 
			              rows="3" 
			              cols="50"><?php echo $messages['zensor_author_email_body']; ?></textarea>
			</fieldset>

			<fieldset class="options">
    			<legend><?php _e("Messages on page/post edit form.", "zensor"); ?></legend>

    			<p><?php _e("When an approved page/post is being edited, the following message will be displayed beneath the edit form. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_approved_page_edit_message" 
    			          id="zensor_approvaed_page_edit_message" 
    			          style="width: 98%;" 
    			          rows="3" 
    			          cols="50"><?php echo $messages['zensor_approved_page_edit_message']; ?></textarea>

    			<p><?php _e("When a rejected page/post is being edited, the following message will be displayed beneath the edit form. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_rejected_page_edit_message" 
                	      id="zensor_rejected_page_edit_message" 
                	      style="width: 98%;" 
                	      rows="3" 
                	      cols="50"><?php echo $messages['zensor_rejected_page_edit_message']; ?></textarea>

    			<p><?php _e("When a page/post awaiting moderation is being edited, the following message will be displayed beneath the edit form. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_awaiting_page_edit_message" 
    			          id="zensor_awaiting_page_edit_message" 
    			          style="width: 98%;" 
    			          rows="3" 
    			          cols="50"><?php echo $messages['zensor_awaiting_page_edit_message']; ?></textarea>

    			<p><?php _e("When a new page/post is being edited, the following message will be displayed beneath the edit form. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_new_page_edit_message" 
    			          id="zensor_new_page_edit_message" 
    			          style="width: 98%;" 
    			          rows="3" 
    			          cols="50"><?php echo $messages['zensor_new_page_edit_message']; ?></textarea>
			</fieldset>
		
			<fieldset class="options">
    			<legend><?php _e("Moderation Messages", "zensor"); ?></legend>

    			<p><?php _e("When a page/post awaiting moderation is visited, the following message will be displayed to non-moderators. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_awaiting_moderation_message" 
                          id="zensor_awaiting_moderation_message" 
                          style="width: 98%;" 
                          rows="3" 
                          cols="50"><?php echo $messages['zensor_awaiting_moderation_message']; ?></textarea>

    			<p><?php _e("When a page/post awaiting moderation is visited, the following message will be displayed to moderators beneath the page/post. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_awaiting_moderation_footer" 
    			          id="zensor_awaiting_moderation_footer" 
    			          style="width: 98%;" 
    			          rows="3" 
    			          cols="50"><?php echo $messages['zensor_awaiting_moderation_footer']; ?></textarea>

    			<p><?php _e("When a rejected page/post is visited, the following message will be displayed to moderators beneath the page/post. (HTML)", "zensor"); ?></p>
    			<textarea name="zensor_rejected_moderation_footer" 
    			          id="zensor_rejected_moderation_footer" 
    			          style="width: 98%;" 
    			          rows="3" 
    			          cols="50"><?php echo $messages['zensor_rejected_moderation_footer']; ?></textarea>
			</fieldset>
		<?php endif; ?>
			<p class="submit">
    			<?php if( empty($_POST['zensor_advance_options'])): ?>
    			    <input type="submit" name="zensor_advance_options" value="<?php _e("Advanced Options", "zensor"); ?> &raquo;"/>
    			<?php endif; ?>
    			<input type="submit" name="zensor_update_options" value="<?php _e("Update", "zensor"); ?> &raquo;"/>
			</p>
		</form>
			
		<?php if( !empty($_POST['zensor_advance_options'])): ?>
		<form method="post">
			<?php wp_nonce_field('zensor_reset-options'); ?>
			<p class="submit">
				<input name="zensor_reset_options" class="button" type="submit" value="<?php _e("Reload Defaults", "zensor"); ?>" onclick="if ( confirm('<?php _e('This will reload the default options/messages that ship with Zensor, losing all of your customizations. Are you sure you want to do this?', 'zensor'); ?>') ) { return true;}return false;" />
			</p>
		</form>

	<?php if( current_user_can( 'edit_plugins' ) ): ?>
		<form method="post">
			<?php wp_nonce_field('zensor_uninstall'); ?>
			<p class="submit">
				<input name="zensor_uninstall" class="button delete" type="submit" value="<?php _e("Uninstall", "zensor"); ?>" onclick="if ( confirm('<?php _e("This will deactivate this plugin and remove all traces of it from your database.  Do you REALLY want to do this?", "zensor"); ?>') ) { return true;}return false;" />
			</p>
		</form>
	<?php
		endif;
		endif;
		echo "</div>\n";
	}
}

/*
 * Hook into the init action to process POSTs
 */
if( function_exists('add_action') ) 
{
	add_action('init', array('Zensor_Options', 'handle_update_options') );
}

?>