<?php
/**
 * YOURLS Link Creator - Global Module
 *
 * Contains functions and options that involve both front and back
 *
 * @package YOURLS Link Creator
 */
/*  Copyright 2015 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'YOURLSCreator_Global' ) ) {

/**
 * Set up and load our class.
 */
class YOURLSCreator_Global
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pre_get_shortlink',            array( $this, 'shortlink_button'    ),  2,  2   );
		add_filter( 'get_shortlink',                array( $this, 'yourls_shortlink'    ),  10, 3   );
		add_action( 'transition_post_status',       array( $this, 'yourls_on_publish'   ),  10, 3   );
		add_action( 'publish_future_post',          array( $this, 'yourls_on_schedule'  ),  10      );

		// Our two cron jobs.
		add_action( 'yourls_cron',                  array( $this, 'yourls_click_cron'   )           );
		add_action( 'yourls_test',                  array( $this, 'yourls_test_cron'    )           );
	}

	/**
	 * Hijack the normal shortlink button and use ours instead.
	 *
	 * @param  string  $shortlink  The existing short URL.
	 * @param  integer $id         Post ID, or 0 for the current post.
	 *
	 * @return string  $shortlink  The YOURLS link or existing URL.
	 */
	public function shortlink_button( $shortlink, $id ) {

		// Bail if the setting isn't enabled.
		if(	false === $enabled = YOURLSCreator_Helper::get_yourls_option( 'sht' ) ) {
			return $shortlink;
		}

		// Check existing postmeta for YOURLS.
		$custom = YOURLSCreator_Helper::get_yourls_meta( $id );

		// Return the custom YOURLS link or the regular one.
		return ! empty( $custom ) ? $custom : $shortlink;
	}

	/**
	 * Filter wp_shortlink with new YOURLS link.
	 *
	 * @param  string  $shortlink  The existing short URL.
	 * @param  integer $id         Post ID, or 0 for the current post.
	 * @param  string  $context    The context for the link. One of 'post' or 'query'.
	 *
	 * @return string  $shortlink  The YOURLS link or existing URL.
	 */
	public function yourls_shortlink( $shortlink, $id, $context ) {

		// No shortlinks exist on non-singular items, so bail.
		if ( ! is_singular() ) {
			return;
		}

		// Look for the post ID passed by wp_get_shortlink() first.
		if ( empty( $id ) ) {

			// Call the global post object.
			global $post;

			// And get the ID.
			$id = absint( $post->ID );
		}

		// Fall back in case we still don't have a post ID.
		if ( empty( $id ) ) {
			return ! empty( $shortlink ) ? $shortlink : false;
		}

		// Check existing postmeta for YOURLS.
		$custom = YOURLSCreator_Helper::get_yourls_meta( $id );

		// Return the custom YOURLS link or the regular one.
		return ! empty( $custom ) ? $custom : $shortlink;
	}

	/**
	 * Generate a YOURLS link when a post is manually moved from future to publish.
	 *
	 * @param  string $new_status  The status the post is changing to.
	 * @param  string $old_status  The status the post is changing from.
	 * @param  object $post        The WP_Post object.
	 *
	 * @return void
	 */
	public function yourls_on_publish( $new_status, $old_status, $post ) {

		// We only want to handle items going from 'future' to 'publish'
		if ( 'future' === $old_status && 'publish' === $new_status ) {
        	YOURLSCreator_Helper::get_single_shorturl( $post->ID, 'sch' );
		}
	}

	/**
	 * Generate a YOURLS link when a post is automatically moved from future to publish.
	 *
	 * @param  integer $post_id  The post ID to create a URL with.
	 *
	 * @return void
	 */
	public function yourls_on_schedule( $post_id ) {
		YOURLSCreator_Helper::get_single_shorturl( $post_id, 'sch' );
	}

	/**
	 * Run update job to get click counts via cron.
	 *
	 * @return void
	 */
	public function yourls_click_cron() {

		// Bail if the API key or URL have not been entered.
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// Fetch the IDs that contain a YOURLS url meta key.
		if ( false === $items = YOURLSCreator_Helper::get_all_yours_ids() ) {
			return false;
		}

		// Loop the ID groups.
		foreach ( $items as $type => $item_array ) {

			// Now loop my item array.
			foreach ( $item_array as $item_id ) {

				// Get my click number.
				$clicks = YOURLSCreator_Helper::get_single_click_count( $item_id, $type );

				// Bad API call or no count to return.
				if ( empty( $clicks['success'] ) || empty( $clicks['clicknm'] ) ) {
					continue;
				}

				// Update the post meta.
				if ( 'post' === $type ) {
					update_post_meta( $item_id, '_yourls_clicks', absint( $clicks['clicknm'] ) );
				}

				// Update the post meta.
				if ( 'term' === $type ) {
					update_term_meta( $item_id, '_yourls_term_clicks', absint( $clicks['clicknm'] ) );
				}
			}
		}
	}

	/**
	 * Run a daily test to make sure the API is available.
	 *
	 * @return void
	 */
	public function yourls_test_cron() {

		// Bail if the API key or URL have not been entered.
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// Make the API call.
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'db-stats', array(), false );

		// Handle the check and set it.
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// Set the option return.
		if ( false !== get_option( 'yourls_api_test' ) ) {
			update_option( 'yourls_api_test', $check );
		} else {
			add_option( 'yourls_api_test', $check, null, 'no' );
		}
	}

	// End class.
}

} // End exists check.

// Instantiate our class.
$YOURLSCreator_Global = new YOURLSCreator_Global();
$YOURLSCreator_Global->init();
