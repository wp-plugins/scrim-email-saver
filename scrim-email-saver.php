<?php
/*
 Plugin Name: Scr.im Email Saver
 Plugin URI: http://bbpep.com/plugins/scrim-email-saver/
 Description: The plugin filters your blog's comments and bbPress posts for email IDs and converts them into <a href="http://scr.im/">Scr.im</a> links so that your users' email IDs do not get picked up by bots and they receive less (if not zero) spam.
 Author: gautamgupta
 Author URI: http://gaut.am/
 Version: 0.3
*/

/** Version */
if ( !defined( 'SES_VER' ) ) define( 'SES_VER', '0.3' );

if ( !function_exists( 'ses_save_emails' ) ) : /* Same function exists in bbPress plugin, this is here to remove fatal errors if bbPress functions are loaded into WP */
/**
 * Save the Emails!
 *
 * @param string $content The content to be processed
 * @uses wp_remote_get() To make the call
 * @uses wp_remote_retrieve_body() To retrieve the body content of the call
 * @return string The processed content
 */
function ses_save_emails( $content ) {
	/* Get on with the business, match all the emails */
	preg_match_all( '/\b[.0-9a-z_+-]+@[.0-9a-z_+-]+\.[0-9a-z]{2,}\b/i', $content, $emails );
	//'/\b[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/' --> used by another plugin OR '#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i' --> used by make_clickable (above is combined)

	if ( !$emails = array_filter( array_map( 'sanitize_email', (array) $emails[0] ) ) ) /* Sanitize all the emails and then filter for null/false values */
		return $content; /* No emails? Then return the content! */

	foreach ( $emails as $email ) {
		/* Ok, we are in, now call the scrim api and request for generating the scrim code
		   WP_Http does a great job for us by taking all the tensions and applying all the possible methods ;) */
		$scrim = wp_remote_retrieve_body( /* Check if any errors are there */
				wp_remote_get( /* Make the call */
					"http://scr.im/xml/email={$email}&type=text",
					array(
						'user-agent' => 'Scr.im Email Saver WordPress Plugin v' . SES_VER  /* Brand our plugin by user agent */
					)
				)
			);
		if ( !$scrim || strpos( $scrim, 'http://scr.im/' ) === false ) /* Call failed? No scrim? Go to the next email please! */
			continue;

		$scrim = trim( $scrim ); /* Make the URL */

		/* All done, now replace the actual email id with the URL
		   We dont need <a href> or rel=nofollow because the make_clickable filter on post_text does that for us */
		$content = str_replace( $email, $scrim, $content );
	}

	/* Finally, return the content */
	return $content;
}
endif;

/* We avoid comment_text etc filter to prevent WP_Http calls everytime */
add_filter( 'pre_comment_content',        'ses_save_emails', -9, 1 );
add_filter( 'bbp_new_topic_pre_content',  'ses_save_emails', -9, 1 );
add_filter( 'bbp_new_reply_pre_content',  'ses_save_emails', -9, 1 );
add_filter( 'bbp_edit_topic_pre_content', 'ses_save_emails', -9, 1 );
add_filter( 'bbp_edit_reply_pre_content', 'ses_save_emails', -9, 1 );
