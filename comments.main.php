<?php
/**
 * This template generates an RSS 3.0 feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * See {@link http://backend.userland.com/rss}
 *
 * @package evoskins
 * @subpackage rss3
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// What level of detail do we want?
$feed_content = $Blog->get_setting('comment_feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	// This will normaly have been detected earlier but just for security:
	debug_die( 'Feeds are disabled.');
}

if( !$Blog->get_setting( 'comments_latest' ) )
{ // The latest comments are disabled for current blog
	// Redirect to page with text/html mime type
	header_redirect( get_dispctrl_url( 'comments' ), 302 );
	// will have exited
}

if( isset($Item) )
{	// Comments for a specific Item:
	$post_ID = $Item->ID;
	$selfurl = format_to_output( $Item->get_feedback_feed_url( '_rss2' ), 'xmlattr' );
}
else
{	// Comments for the blog:
	$post_ID = NULL;
	$selfurl = format_to_output( $Blog->get_comment_feed_url( '_rss2' ), 'xmlattr' );
}
$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment' ),
		'statuses' => array ( 'published' ),
		'post_ID' => $post_ID,
		'order' => 'DESC',
		'comments' => $Blog->get_setting('comments_per_feed'),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();

headers_content_mightcache( 'text/plain' );		// In most situations, you do NOT want to cache dynamic content!

// Add caching headers
// TODO: Last-Modified
header('Expires: '.date('r', time() + 300)); // TODO: dh> should be a centralized setting. Maybe through the Skin class, if type is "feed"?
require_once 'ad.include.php';

?>
title: <?php
			$Blog->disp( 'name', 'xml' );
			// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
			request_title( array(
					'title_before'=> ' - ',
					'title_after' => '',
					'title_none'  => '',
					'glue'        => ' - ',
					'title_single_disp' => true,
					'format'      => 'xml',
				) );
			// ------------------------------ END OF REQUEST TITLE -----------------------------
		?>

link: <?php
			$Blog->disp( 'lastcommentsurl', 'xml' );
			param('p');
			if (p)
				echo "&p=$p";

?>

language: <?php $Blog->disp( 'locale', 'xml' ) ?>

generator: <?php printf("%s %s\n", $app_name, $app_version); ?>
docs: http://www.aaronsw.com/2002/rss30
ttl: 60
		<?php while( $Comment = & $CommentList->get_next( false ) )
		{ // Loop through comments:
			// Load comment's Item:
			$Comment->get_Item();
?>

title: <?php echo format_to_output(
			sprintf(
				/* TRANS: First %s: Commentator's name, second %s: post title */
				T_( '%s in response to: %s' ),
				$Comment->get_author( array( 'format' => 'raw', 'link_to' => '' ) ),
				$Comment->Item->get_title(
					array(
						'format' => 'raw',
						'link_type' => 'none',
					)
				)
			),
			'xml' ); ?>

created: <?php $Comment->time( 'r', true ); ?>

creator: <?php printf('%s %s', $Comment->get_author_email(), $Comment->get_author_name()); ?>

guid: c<?php $Comment->ID() ?>@<?php echo $baseurl ?>
			<?php
			$content = $Comment->get_content();
			if( $feed_content == 'excerpt' )
			{
				$content = excerpt($content);
			}
			$content = format_to_output( make_rel_links_abs($content), 'entityencoded' );
			$content = preg_replace('/\r|\n/', "\r\n\t", $content);
			?>

description: <?php echo $content; ?>

link: <?php $Comment->permanent_url(); ?>

		<?php } /* End of comment loop. */ ?>
