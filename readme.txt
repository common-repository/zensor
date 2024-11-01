=== Zensor ===
Contributors: scompt
Donate link: http://scompt.com/projects/zensor
Tags: workflow, posts, pages, cms, publishing, moderate
Requires at least: 2.1
Tested up to: 2.2
Stable tag: trunk

Zensor enforces a two-step publishing workflow in WordPress.

== Description ==

Zensor doesn't work for the current version of WordPress, probably won't be updated anytime soon, and could cause loss of life, limb, or data.

Zensor enforces a two-step publishing workflow in WordPress.  The two steps are:

1. An author submits an article (post/page) for review.
1. A moderator approves or disapproves the article.

The article is only visible after the second step.  The author can add a note that the 
moderator can read when evaluating the article.  Similarly, the moderator can include
a justification for his/her decision.

= Moderators =
Who is a moderator is determined by the `zensor_moderate` capability.  Upon installation, 
the Administrator is assigned this capability.  Also, a new role, Zensor Moderator is created, which
only has this capability and `read`.  This is a dedicated user for moderation, eliminating
potential conflicts of interest.  The Zensor Moderator cannot modify posts in any way
except to accept/reject them and add a note that will be sent back to the author.  Perfect for 
the boss who needs to sign off on everything, but shouldn't be allowed to add his own opinion.
If you'd like other roles to be able to moderate, then you can grant them the `zensor_moderate`
capability using the [Role Manager](http://www.im-web-gefunden.de/wordpress-plugins/role-manager/) plugin.

= Notifications =
Every time someone does something, the other party is notified.  These notifications include:

* Hello author, your article was just approved/rejected.
* Hello moderator, an article was just saved that you need to look at.

Notification frequencies for both author and moderator can be independently set
to either immediate, hourly, or daily.  All messages can be edited through the 
options interface.

== Installation ==

*Important Note*: Upon installation, all posts and pages are put into the `awaiting` state
and will need to be approved/rejected.

1. Upload the `zensor` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Goto the Manage->Zensor panel and Approve/Reject the existing posts and pages

== Frequently Asked Questions ==

= I activated the plugin and now nothing shows up on my blog!  Where'd it all go? =

When you activate the plugin, all posts and pages are put into the `awaiting` state.  This 
means that they're no longer viewable to normal users.  To get them back, you need
to either approve or reject them.  This can be done from the Manage->Zensor panel.  If
you want to approve all of them, you can scroll to the bottom of the screen and hit
`Bulk Approve`.

= This isn't working out for me.  How do I get rid of the plugin cleanly? =

That's sorry to hear.  I'd love to hear what you didn't like.  
Let [me](mailto:scompt@scompt.com) know!

If you still want to uninstall the plugin, go to the Options->Zensor panel and scroll
to the bottom of the screen.  There you'll find an `Uninstall` button.

== Screenshots ==

1. Write a note that will be displayed to the moderator.  Note the tip at the bottom of the page.
2. Filter pages/posts based on the moderation status.
3. Moderator page to view all of the pages/posts in the Zensor system.
4. Moderator page with preview of post and form for moderator.  Note the bulk buttons at the bottom of the page.
5. Sample email that a moderator receives when a post is made.

== Future Plans ==

* Consolidate all of the options into a single option containing an array.  It's too messy to have so many options for the messages.
* A feed of the pages/posts in the moderation system.  Would have to be authenticated.
* Should posts in the moderation system be private/draft?
* Keep a copy of the old, approved version of a page/post while the new version is in the queue.

== Version History ==

= Version 0.7 =

* Notifications frequency is now selectable
* Added fix to work with [Improved Include Page](http://www.vtardia.com/improved-include-page/) plugin.  Thanks, Vincent!
* Moved localizing out of javascript file

= Version 0.6 =

* Moved development to wp-plugins.org
* Administrator is granted `zensor_moderate` capability on activation

= Version 0.5 =

* First push to [Siebold Gymnasium](http://www.siebold-gymnasium.de) website
