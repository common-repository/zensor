<?php

/**
 * Functionality for the management screen where posts/pages are listed
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
 * @version    $Id: manage.php 29 2007-07-06 15:12:02Z scompt $
 * @link       http://www.scompt.com/projects/zensor
 * @since      0.5
 */

/**
 * Common functions used all over the Zensor package
 */
require_once(dirname(__FILE__).'/common.php');

/**
 * A collection of static functions for the management screen
 */
class Zensor_Manage
{
	
	/**
	 * Prints the management screen for Zensor
	 *
	 * Called by the add_management_page call setup in Zensor_Admin::admin_menu.
	 * Made up of two subpages (awaiting & rejected pages).
	 */
	function admin_manage_page()
	{
		global $zensor_table, $wpdb;
		
		// Handle paging and subpage
		$limit = 10;
		$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
		$which = (isset( $_GET['sub'] ) && $_GET['sub'] == ZENSOR_REJECTED ) ? ZENSOR_REJECTED : ZENSOR_AWAITING;	
	
		// Do the query.  isset($_GET['id'] is used to guarantee that a preloaded
		// page is included in the query
		$query = "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_type, {$wpdb->posts}.post_title, ".
				 "{$wpdb->users}.display_name, UNIX_TIMESTAMP($zensor_table.last_updated) as last_updated, $zensor_table.message " .
				 (isset($_GET['id']) ? ", {$wpdb->posts}.ID=".intval($_GET['id'])." AS preload " : "").
				 "FROM {$wpdb->posts} LEFT JOIN $zensor_table ON {$wpdb->posts}.ID=$zensor_table.post_id ".
				 "JOIN {$wpdb->users} ON post_author={$wpdb->users}.ID " .
				 "WHERE ({$wpdb->posts}.post_type='post' OR {$wpdb->posts}.post_type='page') AND $zensor_table.moderation_status='$which' ".
				 "AND {$wpdb->posts}.post_status='publish' ".
				 (isset($_GET['id']) ? "ORDER BY preload DESC " : "").
				 "LIMIT $offset,$limit";

		$posts = $wpdb->get_results( $query );

		$counts = Zensor_Common::get_counts();

		?>
			<ul id="subsubmenu">
				<li>
					<a <?php if ($which == ZENSOR_AWAITING) echo 'class="current"'; ?>href="<?php echo Zensor_Common::uri(); ?>&sub=<?php echo ZENSOR_AWAITING; ?>">
						<?php _e('Awaiting', 'zensor'); ?> (<span id="zensor-awaiting-count"><?php echo $counts[ZENSOR_AWAITING]; ?></span>)
					</a>
				</li><li>
					<a <?php if ($which == ZENSOR_REJECTED) echo 'class="current"'; ?>href="<?php echo Zensor_Common::uri(); ?>&sub=<?php echo ZENSOR_REJECTED; ?>">
						<?php _e('Rejected', 'zensor'); ?> (<span id="zensor-rejected-count"><?php echo $counts[ZENSOR_REJECTED]; ?></span>)
					</a>
				</li>
			</ul>
		
			<div class="wrap" id="main_page">
		<?php
			if( $which == ZENSOR_AWAITING ) {
				echo '<h2>' . __("Pages/Posts Awaiting Moderation", "zensor") . '</h2>';
				echo '<p>' . __("The pages/posts below have recently been edited or published by authors on your site.  They are now awaiting moderation.  To moderate a page, click 'Preview On'.  You then have the option to approve or reject the page as it is.  The reason you give will be sent to the original author.  Approved pages/posts will be immediately available to site visitors.  Rejected pages/posts will need to be edited by the original author and then re-moderated.", "zensor") . '</p>';
			} else {
				echo '<h2>' . __("Rejected Pages/Posts", "zensor") . '</h2>';
				echo '<p>' . __("The pages/posts below have recently been rejected by a moderator on your site.  They are now awaiting editing by the original author, but can also be re-moderated now.  To moderate a page, click 'Preview On'.  You then have the option to approve or reject the page as it is.  The reason you give will be sent to the original author.  Approved pages/posts will be immediately available to site visitors.  Rejected pages/posts will need to be edited by the original author and then re-moderated.", "zensor") . '</p>';
			}
		?>
			<table class="widefat" id="zensor_table"> 
				<thead><tr>
					<th scope="col"><?php _e('Type'); ?></th>
				    <th scope="col"><?php _e('Title'); ?></th>
				    <th scope="col"><?php _e('Owner'); ?></th>
					<th scope="col"><?php _e('When'); ?></th>
					<th scope="col"><?php ($which == ZENSOR_AWAITING ? _e('Author Message', 'zensor') : _e('Moderation Message', 'zensor')); ?></th>
					<th scope="col">&nbsp;</th>
				</tr></thead>
				<tbody id="zensor_tbody">

				<?php
					if( $posts ) {
						$class = "";
						foreach($posts as $post) {
							Zensor_Manage::row( $post, $class );
						}
					} else {
						echo '<tr colspan="8"><td>';
						if( $which == ZENSOR_AWAITING ) {
							_e('No pages/posts awaiting moderation!', 'zensor');
						} else {
							_e('No rejected pages/posts!', 'zensor');
						}
						echo "</td></tr>\n";
					}
					if( !isset( $_GET['id'] ) )	Zensor_Manage::preview_row();
				?>
				</tbody>
			</table>
			<?php if( $posts ):
				// Do the paging
				if( $counts[$which] > $limit ) {
					echo '<div class="navigation">';
					if( $counts[$which] > $offset+$limit ) {
						?><div class="alignleft">
							<a href="<?php echo Zensor_Common::uri(); ?>&offset=<?php echo $offset+$limit; ?>">
								<?php _e('&laquo; Previous'); ?>
							</a>
						</div><?php
					}
					if( $offset > 0 ) {
						?><div class="alignright">
							<a href="<?php echo Zensor_Common::uri(); ?>&offset=<?php echo $offset<$limit ? 0 : $offset-$limit; ?>">
								<?php _e('Next &raquo;'); ?>
							</a>
						</div><?php
					}
					echo '</div>';
				}
			?>

			<p class="submit">
			<form method="post" id="zensor_bulk-buttons">
				<input type="hidden" name="zensor_bulk_action" value="<?php echo $which; ?>" />
				<?php if( $which == ZENSOR_AWAITING ): ?>
					<input type="submit" 
					       class="button delete" 
					       name="zensor_bulk-approve" 
					       value="<?php _e("Bulk Approve", "zensor"); ?>" 
					       onclick="if ( confirm('<? _e("This will approve ALL posts/pages that are awaiting moderation.  Do you REALLY want to do this?", "zensor"); ?>') ) { return true;}return false;"/>
					<input type="submit" 
					       class="button delete" 
					       name="zensor_bulk-reject" 
					       value="<?php _e("Bulk Reject", "zensor"); ?>" 
					       onclick="if ( confirm('<? _e("This will reject ALL posts/pages that are awaiting moderation.  Do you REALLY want to do this?", "zensor"); ?>') ) { return true;}return false;"/>
				<?php else: ?>
					<input type="submit" 
					       class="button delete" 
					       name="zensor_bulk-approve" 
					       value="<?php _e("Bulk Approve", "zensor"); ?>" 
					       onclick="if ( confirm('<? _e("This will approve ALL rejected posts/pages.  Do you REALLY want to do this?", "zensor"); ?>') ) { return true;}return false;"/>
					<input type="submit" 
						   class="button delete" 
						   name="zensor_bulk-reject" 
						   value="<?php _e("Bulk Reject", "zensor"); ?>" 
						   onclick="if ( confirm('<? _e("This will reject ALL rejected posts/pages.  Do you REALLY want to do this?", "zensor"); ?>') ) { return true;}return false;"/>
				<?php endif ?>		
				<?php wp_nonce_field('zensor_bulk-action'); ?>
			</form></p>
		</div>
	<?php
	endif;
	}

	/**
	 * Prints the table row that displays a preview of a page/post
	 *
	 * This function will be called exactly once for the management page.  The
	 * row will be either be visible if the page is preloaded with a certain 
	 * page/post or invisible if the row should just be added to prime the
	 * javascript for the first preview.
	 *
	 * @param boolean $visible Whether the row should be initially visible
	 * @param string $iframe_link
	 */
	function preview_row($visible=false, $iframe_link='', $id='')
	{
		// TODO: Need to show the zensor_message?
	?>
		<tr id="zensor_preview" style="<?php echo $visible ? '' : 'display:none;'; ?>">
			<td colspan="5">
				<iframe id="zensor_preview_iframe" width="100%" height="600" src="<?php echo $iframe_link; ?>"></iframe>
			</td>
			<td valign="top" class="centered">
				<form method="post" action="<?php echo Zensor_Common::uri(); ?>">
					<p><?php _e('Reason for decision:', 'zensor'); ?></p>
					<textarea style="width:99%" name="zensor_message"></textarea>
					<input type="hidden" id="zensor_post_id" name="zensor_post_id" value="<?php echo $id; ?>" />
					<input type="hidden" name="zensor_moderate" value="zensor_moderate" />
					<?php wp_nonce_field('zensor_moderate'); ?>
					<input type="submit" class="button zensor_approve" name="zensor_approve" value="<?php _e("Approve", "zensor"); ?>" />
					<input type="submit" class="button zensor_reject" name="zensor_reject" value="<?php _e("Reject", "zensor"); ?>" />
				</form>
			</td>
		</tr>
	<?php
	}

	/**
	 * Prints a row of the table displaying details about one page/post
	 *
	 * @param object $post
	 * @param string &$class Either 'alternate' or ''.  Used for alternating rows.
	 */
	function row($post, &$class)
	{
		$class = ('alternate' == $class ) ? '' : 'alternate';
		$iframe_link = attribute_escape(apply_filters('preview_page_link', add_query_arg('preview', 'true', get_permalink($post->ID))));
		$preload = isset($_GET['id']) && $_GET['id'] == $post->ID;
		$preview_text = $preload ? __('Preview Off', 'zensor') : __('Preview On', 'zensor');

		?>
			<tr class="<?php echo $class; ?>" id="zensor-<?php echo $post->ID;?>">
				<td><?php _e(ucfirst($post->post_type), 'zensor');?></td>
				<td><?php echo $post->post_title;?></td>
				<td><?php echo $post->display_name;?></td>
				<td><?php echo date(__('Y-m-d \<\b\r \/\> g:i:s a'), $post->last_updated); ?></td>
				<td><?php echo stripslashes($post->message);?></td>
				<td class="centered">
					<a <?php if($preload) echo 'id="zensor_active"';?> 
					   class="edit" 
					   href="<?php echo isset($_GET['id']) ? Zensor_Common::uri() : Zensor_Common::moderation_page_link( $post->ID ); ?>" 
					   onclick="javascript: zensor_showPreview(this, '<?php echo $iframe_link;?>', <?php echo $post->ID; ?>); return false;">
						<?php echo $preview_text ?>
					</a>
				</td>
			</tr>
		<?php
		if( $preload ) Zensor_Manage::preview_row(true, $iframe_link, $post->ID);
	}

	/**
	 * Changes the Zensor moderation status for a group of posts
	 *
	 * The group of posts is either 'awaiting' or 'rejected' posts and they
	 * can be either approved or rejected.
	 *
	 * @param int $moderator_id The user id of the moderator doing the moderation
	 * @param string $which_posts The posts to moderate. Either 'awaiting' or 'rejected'
	 * @param string $what_action The desired status.  Either 'approved' or 'rejected'
	 */
	function bulk_moderate( $moderator_id, $which_posts, $what_action )
	{
		global $wpdb, $zensor_table;
	
        $posts = $wpdb->get_var( "SELECT GROUP_CONCAT(post_id) FROM $zensor_table WHERE moderation_status='$which_posts'" );
        $posts = explode(',', $posts);

		$message = __('Bulk', 'zensor') . " $what_action";
		// Update records that are already in the table.
		$query = "UPDATE $zensor_table SET moderation_status='$what_action', ".
		         "moderator_id=$moderator_id, message='$message' ".
				 "WHERE moderation_status='$which_posts'";
		$wpdb->query( $query );
		
        // XXX: Send email to owner of post
        $notifications = get_option('zensor_author_notifications');
        $notifications = array_unique(array_merge( $notifications, $posts ));
        update_option( 'zensor_author_notifications', $notifications );
	}

	/**
	 * Changes the Zensor moderation status of a single post and emails the owner
	 *
	 * @param int $post_id The id of the post being moderated
	 * @param string $action The action to take.  Either 'approve' or 'reject'
	 * @param string $message The message to attach to the moderation
	 */
	function moderate( $post_id, $action, $message )
	{
		global $user_identity, $user_ID, $wpdb, $zensor_table;

		$mod_info = new Zensor_Info($post_id);
		if( $action == 'approve' ) {
			if( !$mod_info->approve($user_ID, $user_identity, $message) ) return false;

			$email_subject = Zensor_Common::replace_tags(get_option( "zensor_approval_email_subject" ), $mod_info);
			$email_body = Zensor_Common::replace_tags(get_option( "zensor_approval_email_body" ), $mod_info);
		} else if( $action == 'reject' ) {
			if( !$mod_info->reject($user_ID, $user_identity, $message) ) return false;

			$email_subject = Zensor_Common::replace_tags(get_option( "zensor_rejection_email_subject" ), $mod_info);
			$email_body = Zensor_Common::replace_tags(get_option( "zensor_rejection_email_body" ), $mod_info);
		} else {
			return false;
		}

        // XXX: Send email to owner of post
        $notifications = get_option('zensor_author_notifications');
        $notifications []= $post_id;
        update_option( 'zensor_author_notifications', $notifications );

		return true;
	}

	/**
	 * Processes POSTs to do the individual and bulk moderation
	 */
	function handle_posts()
	{
		global $user_identity, $user_ID, $wpdb, $zensor_table;
	
		if( isset( $_POST['zensor_bulk_action'] ) ) {
			check_admin_referer('zensor_bulk-action');
			if( isset( $_POST['zensor_bulk-approve'] ) ) {
				Zensor_Manage::bulk_moderate( $user_ID, 
											  $_POST['zensor_bulk_action'], 
											  ZENSOR_APPROVED );
			} else if( isset( $_POST['zensor_bulk-reject'] ) ) {
				Zensor_Manage::bulk_moderate( $user_ID, 
					                          $_POST['zensor_bulk_action'], 
					 						  ZENSOR_REJECTED );
			}
		} else if( isset( $_POST['zensor_bulk-reject'] ) ) {
			check_admin_referer('zensor_bulk-action');
			Zensor_Manage::bulk_moderate( $user_ID, ZENSOR_REJECTED );

		} else if( isset( $_POST['zensor_moderate'] ) ) {
			check_admin_referer('zensor_moderate');
			Zensor_Manage::moderate( $_POST['zensor_post_id'], 
								     isset( $_POST['zensor_approve'] ) ? 'approve' : 'reject', 
								     $_POST['zensor_message'] );
		}
	}
}

/*
 * Hook into the init action to process POSTs
 */
if( function_exists('add_action') ) 
{
	add_action('init', array('Zensor_Manage', 'handle_posts') );
}
?>