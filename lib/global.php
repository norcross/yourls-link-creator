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

// Start up the engine
class YOURLSCreator_Global
{

	/**
	 * This is our constructor
	 *
	 * @return YOURLSCreator_Global
	 */
	public function __construct() {
		add_filter( 'pre_get_shortlink',            array( $this, 'shortlink_button'    ),  2,  2   );
		add_filter( 'get_shortlink',                array( $this, 'yourls_shortlink'    ),  10, 3   );
		add_action( 'transition_post_status',       array( $this, 'yourls_on_publish'   ),  10, 3   );
		add_action( 'publish_future_post',          array( $this, 'yourls_on_schedule'  ),  10      );

		// our two cron jobs
		add_action( 'yourls_cron',                  array( $this, 'yourls_click_cron'   )           );
		add_action( 'yourls_test',                  array( $this, 'yourls_test_cron'    )           );
	}

	/**
	 * hijack the normal shortlink button and
	 * use ours instead
	 *
	 * @param  [type] $shortlink [description]
	 * @param  [type] $id        [description]
	 * @return [type]            [description]
	 */
	public function shortlink_button( $shortlink, $id ) {

		// bail if the setting isn't enabled
		if(	false === $enabled = YOURLSCreator_Helper::get_yourls_option( 'sht' ) ) {
			return $shortlink;
		}

		// check existing postmeta for YOURLS
		$custom = YOURLSCreator_Helper::get_yourls_meta( $id );

		// return the custom YOURLS link or the regular one
		return ! empty( $custom ) ? $custom : $shortlink;
	}

	/**
	 * Filter wp_shortlink with new YOURLS link
	 *
	 * @param  [type] $shortlink [description]
	 * @param  [type] $id        [description]
	 * @param  [type] $context   [description]
	 * @return [type]            [description]
	 */
	public function yourls_shortlink( $shortlink, $id, $context ) {

		// no shortlinks exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// Look for the post ID passed by wp_get_shortlink() first
		if ( empty( $id ) ) {

			// call the global post object
			global $post;

			// and get the ID
			$id = absint( $post->ID );
		}

		// Fall back in case we still don't have a post ID
		if ( empty( $id ) ) {
			return ! empty( $shortlink ) ? $shortlink : false;
		}

		// check existing postmeta for YOURLS
		$custom = YOURLSCreator_Helper::get_yourls_meta( $id );

		// return the custom YOURLS link or the regular one
		return ! empty( $custom ) ? $custom : $shortlink;
	}

	/**
	 * generate a YOURLS link when a post is
	 * manually moved from future to publish
	 *
	 * @param  [type] $new_status [description]
	 * @param  [type] $old_status [description]
	 * @param  [type] $post       [description]
	 * @return [type]             [description]
	 */
	public function yourls_on_publish( $new_status, $old_status, $post ) {

		// we only want to handle items going from 'future' to 'publish'
		if ( 'future' == $old_status && 'publish' == $new_status ) {
        	YOURLSCreator_Helper::get_single_shorturl( $post->ID, 'sch' );
		}
	}

	/**
	 * generate a YOURLS link when a post is
	 * automatically moved from future to publish
	 *
	 * @param  [type] $post_id [description]
	 * @return [type]          [description]
	 */
	public function yourls_on_schedule( $post_id ) {
		YOURLSCreator_Helper::get_single_shorturl( $post_id, 'sch' );
	}

	/**
	 * run update job to get click counts via cron
	 *
	 * @return void
	 */
	public function yourls_click_cron() {

		// bail if the API key or URL have not been entered
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// fetch the IDs that contain a YOURLS url meta key
		$items  = YOURLSCreator_Helper::get_yourls_post_ids();

		// bail if none are present
		if ( empty( $items ) ) {
			return false;
		}

		// loop the IDs
		foreach ( $items as $item_id ) {

			// get my click number
			$clicks = YOURLSCreator_Helper::get_single_click_count( $item_id );

			// and update my meta
			if ( ! empty( $clicks['clicknm'] ) ) {
				update_post_meta( $item_id, '_yourls_clicks', $clicks['clicknm'] );
			}
		}
	}

	/**
	 * run a daily test to make sure the API is available
	 *
	 * @return void
	 */
	public function yourls_test_cron() {

		// bail if the API key or URL have not been entered
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// make the API call
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'db-stats', array(), false );

		// handle the check and set it
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// set the option return
		if ( false !== get_option( 'yourls_api_test' ) ) {
			update_option( 'yourls_api_test', $check );
		} else {
			add_option( 'yourls_api_test', $check, null, 'no' );
		}
	}

// end class
}

// end exists check
}

// Instantiate our class
new YOURLSCreator_Global();

