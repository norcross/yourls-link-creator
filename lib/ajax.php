<?php
/**
 * YOURLS Link Creator - Ajax Module
 *
 * Contains our ajax related functions
 *
 * @package YOURLS Link Creator
 */

/*
	Copyright 2015 Reaktiv Studios

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

if ( ! class_exists( 'YOURLSCreator_Ajax' ) ) {

/**
 * Set up and load our class.
 */
class YOURLSCreator_Ajax
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_create_post_yourls',   array( $this, 'create_post_yourls'  )           );
		add_action( 'wp_ajax_delete_post_yourls',   array( $this, 'delete_post_yourls'  )           );
		add_action( 'wp_ajax_delete_term_yourls',   array( $this, 'delete_term_yourls'  )           );
		add_action( 'wp_ajax_inline_post_yourls',   array( $this, 'inline_post_yourls'  )           );
		add_action( 'wp_ajax_stats_post_yourls',    array( $this, 'stats_post_yourls'   )           );
		add_action( 'wp_ajax_status_api_yourls',    array( $this, 'status_api_yourls'   )           );
		add_action( 'wp_ajax_refresh_yourls',       array( $this, 'refresh_yourls'      )           );
		add_action( 'wp_ajax_convert_yourls',       array( $this, 'convert_yourls'      )           );
		add_action( 'wp_ajax_import_yourls',        array( $this, 'import_yourls'       )           );
	}

	/**
	 * Create shortlink function for posts.
	 */
	public function create_post_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_editor_create', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail without a post ID.
		if ( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Now cast the post ID.
		$post_id    = absint( $_POST['post_id'] );

		// Bail if we aren't working with a published or scheduled post.
		if ( ! in_array( get_post_status( $post_id ), YOURLSCreator_Helper::get_yourls_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Do a quick check for a URL.
		if ( false !== $link = YOURLSCreator_Helper::get_yourls_meta( $post_id, '_yourls_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Do a quick check for a permalink.
		if ( false === $url = YOURLSCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Check for keyword and get the title.
		$keyword = ! empty( $_POST['keyword'] ) ? YOURLSCreator_Helper::prepare_api_keyword( sanitize_key( $_POST['keyword'] ) ) : '';
		$title   = get_the_title( $post_id );

		// Set my args for the API call.
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keyword );

		// Make the API call.
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'shorturl', $args );

		// Bail if empty data.
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if error received.
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// We have done our error checking and we are ready to go.
		if ( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// Get my short URL.
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// Update the post meta.
			update_post_meta( $post_id, '_yourls_url', $shorturl );
			update_post_meta( $post_id, '_yourls_clicks', '0' );

			// And do the API return.
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new YOURLS link.', 'wpyourls' );
			$ret['linkurl'] = $shorturl;
			$ret['linkbox'] = YOURLSCreator_Helper::get_yourls_linkbox( $shorturl, $post_id );
			echo json_encode( $ret );
			die();
		}

		// We've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpyourls' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Delete shortlink function for posts.
	 */
	public function delete_post_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_editor_delete', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail without a post ID.
		if ( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Now cast the post ID.
		$post_id    = absint( $_POST['post_id'] );

		// Do a quick check for a URL.
		if ( false === $link = YOURLSCreator_Helper::get_yourls_meta( $post_id, '_yourls_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_URL_EXISTS';
			$ret['message'] = __( 'There is no URL to delete.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Passed it all. go forward.
		delete_post_meta( $post_id, '_yourls_url' );
		delete_post_meta( $post_id, '_yourls_clicks' );

		// And do the API return.
		$ret['success'] = true;
		$ret['message'] = __( 'You have removed your YOURLS link.', 'wpyourls' );
		$ret['linkbox'] = YOURLSCreator_Helper::get_yourls_subbox( $post_id );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Delete shortlink function
	 */
	public function delete_term_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_term_link_delete', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail without a term ID.
		if ( empty( $_POST['term_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_TERM_ID';
			$ret['message'] = __( 'No term ID was present.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Now cast the term ID.
		$id     = absint( $_POST['term_id'] );

		// Check to see if we have a URL or not.
		$link   = get_term_meta( $id, '_yourls_term_url', true );

		// Do a quick check for a URL.
		if ( empty( $link ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_URL_EXISTS';
			$ret['message'] = __( 'There is no URL to delete.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Passed it all. go forward.
		delete_term_meta( $id, '_yourls_term_url' );
		delete_term_meta( $id, '_yourls_term_clicks' );

		// And do the API return.
		$ret['success'] = true;
		$ret['message'] = __( 'You have removed your YOURLS link.', 'wpyourls' );
		$ret['linkbox'] = YOURLSCreator_TermMeta::new_yourls_term_link();
		echo json_encode( $ret );
		die();
	}

	/**
	 * Create shortlink function inline. Called on ajax
	 */
	public function inline_post_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail without a post ID.
		if ( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Now cast the post ID.
		$post_id    = absint( $_POST['post_id'] );

		// Bail if we aren't working with a published or scheduled post.
		if ( ! in_array( get_post_status( $post_id ), YOURLSCreator_Helper::get_yourls_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_inline_create_' . absint( $post_id ), 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Do a quick check for a URL.
		if ( false !== $link = YOURLSCreator_Helper::get_yourls_meta( $post_id, '_yourls_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Do a quick check for a permalink.
		if ( false === $url = YOURLSCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Get my post title.
		$title  = get_the_title( $post_id );

		// Check for a keyword.
		$keywd  = YOURLSCreator_Helper::get_yourls_keyword( $post_id );

		// Set my args for the API call.
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// Make the API call.
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'shorturl', $args );

		// Bail if empty data.
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if error received.
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// We have done our error checking and we are ready to go.
		if ( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// Get my short URL.
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// Update the post meta.
			update_post_meta( $post_id, '_yourls_url', $shorturl );
			update_post_meta( $post_id, '_yourls_clicks', '0' );

			// And do the API return.
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new YOURLS link.', 'wpyourls' );
			$ret['rowactn'] = '<span class="update-yourls">' . YOURLSCreator_Helper::update_row_action( $post_id ) . '</span>';
			echo json_encode( $ret );
			die();
		}

		// We've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpyourls' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Retrieve stats.
	 */
	public function stats_post_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail without a post ID.
		if ( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Now cast the post ID.
		$post_id    = absint( $_POST['post_id'] );

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_inline_update_' . absint( $post_id ), 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Get my click number.
		$clicks = YOURLSCreator_Helper::get_single_click_count( $post_id );

		// Bad API call.
		if ( empty( $clicks['success'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = $clicks['errcode'];
			$ret['message'] = $clicks['message'];
			echo json_encode( $ret );
			die();
		}

		// Got it. update the meta.
		update_post_meta( $post_id, '_yourls_clicks', $clicks['clicknm'] );

		// And do the API return.
		$ret['success'] = true;
		$ret['message'] = __( 'Your YOURLS click count has been updated', 'wpyourls' );
		$ret['clicknm'] = $clicks['clicknm'];
		echo json_encode( $ret );
		die();
	}

	/**
	 * Run the status check on call.
	 */
	public function status_api_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_status_nonce', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Make the API call.
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'db-stats' );

		// Handle the check and set it.
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// Set the option return.
		if ( false !== get_option( 'yourls_api_test' ) ) {
			update_option( 'yourls_api_test', $check );
		} else {
			add_option( 'yourls_api_test', $check, null, 'no' );
		}

		// Now get the API data.
		$data   = YOURLSCreator_Helper::get_api_status_data();

		// Check to see if no data happened.
		if ( empty( $data ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_STATUS_DATA';
			$ret['message'] = __( 'The status of the YOURLS API could not be determined.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// If we have data, send back things.
		if ( ! empty( $data ) ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['baricon'] = $data['icon'];
			$ret['message'] = $data['text'];
			$ret['stcheck'] = '<span class="dashicons dashicons-yes api-status-checkmark"></span>';
			echo json_encode( $ret );
			die();
		}

		// We've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpyourls' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Run update job to get click counts via manual ajax.
	 */
	public function refresh_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_refresh_nonce', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if the API key or URL have not been entered.
		if ( false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Fetch the IDs that contain a YOURLS url meta key.
		if ( false === $items = YOURLSCreator_Helper::get_all_yours_ids() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_ITEM_IDS';
			$ret['message'] = __( 'There are no items with stored URLs.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Loop the ID groups.
		foreach ( $items as $type => $item_array ) {

			// Now loop my item array.
			foreach ( $item_array as $item_id ) {

				// Get my click number.
				$clicks = YOURLSCreator_Helper::get_single_click_count( $item_id, $type );

				// Bad API call.
				if ( empty( $clicks['success'] ) ) {
					$ret['success'] = false;
					$ret['errcode'] = $clicks['errcode'];
					$ret['message'] = $clicks['message'];
					echo json_encode( $ret );
					die();
				}

				// If no count, continue.
				if ( empty( $clicks['clicknm'] ) ) {
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

		// And do the API return.
		$ret['success'] = true;
		$ret['message'] = __( 'The click counts have been updated', 'wpyourls' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Convert from Ozh (and Otto's) plugin.
	 */
	public function convert_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Start our return.
		$ret = array();

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_convert_nonce', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Filter our key to replace.
		$key = apply_filters( 'yourls_key_to_convert', 'yourls_shorturl' );

		// Bail if we have no key to convert.
		if ( empty( $key ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_KEY_SET';
			$ret['message'] = __( 'There is no meta key set to convert.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Fetch the IDs that contain a YOURLS url meta key.
		if ( false === $items = YOURLSCreator_Helper::get_yourls_post_ids( $key ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_KEYS';
			$ret['message'] = __( 'There are no meta keys to convert.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Run the update function. If none return, say so.
		if ( false === $update = YOURLSCreator_Helper::convert_yourls_keys( $key ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'KEY_MISSING';
			$ret['message'] = __( 'There are no keys matching this criteria. Please try again.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// We had matches. return the success message with a count.
		if ( $update > 0 ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['updated'] = absint( $update );
			$ret['message'] = sprintf( _n( '%d key has been updated.', '%d keys have been updated.', absint( $update ), 'wpyourls' ), absint( $update ) );
			echo json_encode( $ret );
			die();
		}
	}

	/**
	 * Check the YOURLS install for existing links and pull the data if it exists.
	 */
	public function import_yourls() {

		// Only run on admin.
		if ( ! is_admin() ) {
			die();
		}

		// Verify our nonce.
		$check  = check_ajax_referer( 'yourls_import_nonce', 'nonce', false );

		// Check to see if our nonce failed.
		if ( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if the API key or URL have not been entered.
		if (    false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Set my args for the API call.
		$args   = array( 'filter' => 'top', 'limit' => apply_filters( 'yourls_import_limit', 999 ) );

		// Make the API call.
		$fetch  = YOURLSCreator_Helper::run_yourls_api_call( 'stats', $args );

		// Bail if empty data.
		if ( empty( $fetch ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Bail if error received.
		if ( false === $fetch['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// Bail if no links received.
		if ( empty( $fetch['data']['links'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_LINKS';
			$ret['message'] = __( 'There was no available link data to import.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Filter the incoming for matching links.
		$filter = YOURLSCreator_Helper::filter_yourls_import( $fetch['data']['links'] );

		// Bail if no matching links received.
		if ( empty( $filter ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_MATCHING_LINKS';
			$ret['message'] = __( 'There were no matching links to import.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Set a false flag.
		$error  = false;

		// Now filter them.
		foreach ( $filter as $item ) {

			// Do the import.
			$import = YOURLSCreator_Helper::maybe_import_link( $item );

			// Bail if error received.
			if ( empty( $import ) ) {
				$error  = true;
				break;
			}
		}

		// Bail if we had true on the import.
		if ( true === $error ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_IMPORT_ACTION';
			$ret['message'] = __( 'The data could not be imported.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// Hooray. it worked. do the ajax return.
		if ( false === $error ) {
			$ret['success'] = true;
			$ret['message'] = __( 'All available YOURLS data has been imported.', 'wpyourls' );
			echo json_encode( $ret );
			die();
		}

		// We've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpyourls' );
		echo json_encode( $ret );
		die();
	}

	// End class.
}

} // End exists check.


// Instantiate our class.
$YOURLSCreator_Ajax = new YOURLSCreator_Ajax();
$YOURLSCreator_Ajax->init();
