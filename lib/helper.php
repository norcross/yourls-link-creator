<?php
/**
 * YOURLS Link Creator - Helper Module
 *
 * Contains various functions and whatnot
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

if ( ! class_exists( 'YOURLSCreator_Helper' ) ) {

// Start up the engine
class YOURLSCreator_Helper
{

	/**
	 * get an option from the serialized array
	 * or the entire thing
	 *
	 * @param  string $key [description]
	 * @return [type]      [description]
	 */
	public static function get_yourls_option( $key = '' ) {

		// fetch the data
		$data   = get_option( 'yourls_options' );

		// bail if none exists
		if ( empty( $data ) ) {
			return false;
		}

		// return the entire thing if no key is requested
		if ( empty( $key ) ) {
			return $data;
		}

		// return the specific key if it exists
		if ( ! empty( $key ) && isset( $data[$key] ) ) {
			return $data[$key];
		}

		// return false, nothing there
		return false;
	}

	/**
	 * get a post meta item with YOURLS data
	 *
	 * @param  integer $post_id  [description]
	 * @param  string  $key      [description]
	 * @param  string  $fallback [description]
	 * @return [type]            [description]
	 */
	public static function get_yourls_meta( $post_id = 0, $key = '_yourls_url', $fallback = false ) {

		// get my item
		$item	= get_post_meta( $post_id, $key, true );

		// return the item if there
		if ( ! empty( $item ) ) {
			return $item;
		}

		// return either empty or fallback
		return isset( $fallback ) ? $fallback : false;
	}

	/**
	 * get the post types that YOURLS is enabled for
	 *
	 * @return [type] [description]
	 */
	public static function get_yourls_types() {

		// fetch any custom post types and merge with the built in
		$custom = self::get_yourls_option( 'typ' );
		$built  = array( 'post' => 'post', 'page' => 'page' );

		// return the full array
		return ! empty( $custom ) ? array_merge( $custom, $built ) : $built;
	}

	/**
	 * get the two components of the API and return
	 * them (or one if key is provided)
	 *
	 * @param  string $key [description]
	 * @return [type]      [description]
	 */
	public static function get_yourls_api_data( $key = '' ) {

		// fetch the stored option array
		$option = self::get_yourls_option();

		// if anything is missing, return false
		if ( empty( $option ) || empty( $option['url'] ) || empty( $option['api'] ) ) {
			return false;
		}

		// make a data array
		$data   = array( 'url' => $option['url'], 'key' => $option['api'] );

		// return one or the entire thing
		return empty( $key ) ? $data : $data[$key];
	}

	/**
	 * get all the post IDs that contain the YOURLS url
	 *
	 * @return array the post IDs containing the meta key
	 */
	public static function get_yourls_post_ids( $key = '_yourls_url' ) {

		// call the global database
		global $wpdb;

		// set up our query
		$query  = $wpdb->prepare("
			SELECT	post_id
			FROM	$wpdb->postmeta
			WHERE	meta_key = '%s'
		", esc_sql( $key ) );

		// fetch the column
		$ids    = $wpdb->get_col( $query );

		// return the array of IDs or false if none
		return ! empty( $ids ) ? $ids : false;
	}

	/**
	 * get the API endpoint URL
	 *
	 * @return string   the URL
	 */
	public static function get_yourls_api_url() {

		// fetch the stored base URL link
		$base   = self::get_yourls_api_data( 'url' );

		// parse the link
		$data   = parse_url( esc_url( $base ) );

		// build the API link
		$link   = $data['scheme'] . '://' . $data['host'] . '/yourls-api.php';

		// return it with optional filter
		return apply_filters( 'yourls_api_url', $link );
	}

	/**
	 * make a API request to the YOURLS server
	 *
	 * @param  string $action [description]
	 * @param  array  $args   [description]
	 * @param  string $format [description]
	 * @return [type]         [description]
	 */
	public static function run_yourls_api_call( $action = '', $args = array(), $format = 'json', $decode = true ) {

		// bail if no action is passed
		if ( empty( $action ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'MISSING_ACTION',
				'message'   => __( 'No API action was provided.', 'wpyourls' )
			);
		}

		// bail if an invalid action is passed
		if ( ! in_array( $action, array( 'shorturl', 'expand', 'url-stats', 'stats', 'db-stats' ) ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'INVALID_ACTION',
				'message'   => __( 'The API action was invalid.', 'wpyourls' )
			);
		}

		// bail if the API key or URL have not been entered
		if(	false === $apikey = self::get_yourls_api_data( 'key' ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'NO_API_DATA',
				'message'   => __( 'No data was returned from the API call.', 'wpyourls' )
			);
		}

		// only fire if user has the option
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'INVALID_USER',
				'message'   => __( 'The user requesting the URL does not have authorization.', 'wpyourls' )
			);
		}

		// set the default query args with the required items
		$base   = array( 'signature' => esc_attr( $apikey ), 'action' => esc_attr( $action ), 'format' => esc_attr( $format ) );

		// now add our optional args
		$args   = ! empty( $args ) ? array_merge( $args, $base ) : $base;

		// construct the args for a remote POST
		$build  = wp_remote_post( self::get_yourls_api_url(), array(
			'method'       => 'POST',
			'timeout'      => 45,
			'sslverify'    => false,
			'httpversion'  => '1.1',
			'body'         => $args,
			)
		);

		// bail on empty return
		if ( empty( $build ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'EMPTY_RESPONSE',
				'message'   => __( 'The response from the API was empty.', 'wpyourls' )
			);
		}

		// bail on wp_error
		if ( is_wp_error( $build ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'API_ERROR',
				'message'   => $build->get_error_message()
			);
		}

		// get our response code
		$code   = wp_remote_retrieve_response_code( $build );

		// bail on a not 200
		if ( $code !== 200 ) {
			return array(
				'success'   => false,
				'errcode'   => 'RESPONSE_CODE',
				'message'   => sprintf( __( 'The API call returned a %s response code.', 'wpyourls' ), $code )
			);
		}

		// get the body
		$body   = wp_remote_retrieve_body( $build );

		// bail on empty body
		if ( empty( $body ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'EMPTY_BODY',
				'message'   => __( 'No data was present in the body from the API call.', 'wpyourls' )
			);
		}

		// if we do not want it decoded, return as is
		if ( empty( $decode ) ) {
			return array(
				'success'   => true,
				'errcode'   => null,
				'data'      => $body
			);
		}

		// decode the JSON
		$data   = json_decode( $body, true );

		// bail on empty JSON
		if ( empty( $data ) ) {
			return array(
				'success'   => false,
				'errcode'   => 'EMPTY_JSON',
				'message'   => __( 'The JSON could not be parsed.', 'wpyourls' )
			);
		}

		// return the decoded data
		return array(
			'success'   => true,
			'errcode'   => null,
			'data'      => $data
		);
	}

	/**
	 * make the API call to get the individual click count
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	public static function get_single_click_count( $post_id = 0 ) {

		// get the URL
		$url    = self::get_yourls_meta( $post_id );

		// a secondary check to see if we have the URL
		if ( empty( $url ) ) {
			return false;
		}

		// make the API call
		$build  = self::run_yourls_api_call( 'url-stats', array( 'shorturl' => esc_url( $url ) ) );

		// bail if empty data or error received
		if ( empty( $build ) || false === $build['success'] ) {
			return array(
				'success'   => false,
				'errcode'   => 'NO_DATA',
				'message'   => __( 'No API data was returned.', 'wpyourls' )
			);
		}

		// get my click number
		$count  = is_array( $build ) && ! empty( $build['data']['link']['clicks'] ) ? absint( $build['data']['link']['clicks'] ) : '0';

		// and return
		return array(
			'success'   => true,
			'errcode'   => null,
			'clicknm'   => $count
		);
	}

	/**
	 * take the full API return and filter out the relevant data
	 *
	 * @param  array  $group [description]
	 * @return [type]       [description]
	 */
	public static function filter_yourls_import( $group = array() ) {

		// set an empty
		$data   = array();

		// loop them
		foreach ( $group as $item ) {

			// make sure the items we need exist
			if ( empty( $item['url'] ) || empty( $item['shorturl'] ) ) {
				continue;
			}

			// run the link comparison
			if ( false === self::compare_import_link( esc_url( $item['url'] ) ) ) {
				continue;
			}

			// make a slug
			$slug   = self::create_import_slug( $item['url'] );

			// fetch the click count
			$clicks = ! empty( $item['clicks'] ) ? absint( $item['clicks'] ) : '0';

			// and make a single item
			$data[] = array( 'slug' => $slug, 'link' => esc_url( $item['url'] ), 'short' => esc_url( $item['shorturl'] ), 'clicks' => $clicks );
		}

		// return the data
		return ! empty( $data ) ? $data : false;
	}

	/**
	 * compare a URL being imported to the site URL
	 *
	 * @param  string $link [description]
	 * @return [type]       [description]
	 */
	public static function compare_import_link( $link = '' ) {

		// get my home host link
		$home   = parse_url( home_url( '/' ), PHP_URL_HOST );

		// parse my incoming
		$import = parse_url( esc_url( $link ), PHP_URL_HOST );

		// return true / false based on comparison
		return self::strip_trailing_slash( $home ) == self::strip_trailing_slash( $import ) ? true : false;
	}

	/**
	 * make me a fancy slug
	 *
	 * @param  string $link [description]
	 * @return [type]       [description]
	 */
	public static function create_import_slug( $link = '' ) {

		// parse it
		$slug   = parse_url( esc_url( $link ), PHP_URL_PATH );

		// return it
		return str_replace( '/', '', $slug );
	}

	/**
	 * look in the database for a matching slug
	 * and update accordingly
	 *
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 */
	public static function maybe_import_link( $data = array() ) {

		// call the global database
		global $wpdb;

		// set up our query
		$query  = $wpdb->prepare("
			SELECT	ID
			FROM	$wpdb->posts
			WHERE	post_name = '%s'
			AND     post_status = '%s'
		", esc_sql( $data['slug'] ), esc_sql( 'publish' ) );

		// fetch the column
		$post   = $wpdb->get_col( $query );

		// if we have it, use it
		if ( ! empty( $post ) && ! empty( $post[0] ) ) {
			update_post_meta( absint( $post[0] ), '_yourls_url', esc_url( $data['short'] ) );
			update_post_meta( absint( $post[0] ), '_yourls_clicks', absint( $data['clicks'] ) );
		}

		// and return
		return true;
	}

	/**
	 * get the link box when we dont have a YOURLS link
	 *
	 * @param  string $link [description]
	 * @return [type]       [description]
	 */
	public static function get_yourls_subbox( $post_id = 0 ) {

		// make the nonce
		$nonce  = wp_create_nonce( 'yourls_editor_create' );

		// an empty
		$box    = '';

		// display the box
		$box   .= '<p class="yourls-meta-block yourls-input-block">';

			// input field for the optional keyword
			$box   .= '<input id="yourls-keyw" class="yourls-keyw" size="20" type="text" name="yourls-keyw" value="" tabindex="501" />';

			// simple instruction
			$box   .= '<span class="description">' . __( 'optional keyword', 'wpyourls' ) . '</span>';

		// display the box
		$box   .= '<p class="yourls-meta-block yourls-submit-block">';

			// button to actually fetch the link
			$box   .= '<input type="button" class="button button-secondary button-small yourls-api" id="yourls-get" name="yourls-get" value="' . __( 'Create YOURLS link', 'wpyourls' ) . '" tabindex="502" data-nonce="' . esc_attr( $nonce ) . '" data-post-id="' . absint( $post_id ) . '" />';

			// the spinner
			$box   .= '<span class="spinner yourls-spinner"></span>';

		$box   .= '</p>';

		// and return it
		return $box;
	}

	/**
	 * get the link box when we have a YOURLS link
	 *
	 * @param  string  $link    [description]
	 * @param  integer $post_id [description]
	 * @param  integer $count   [description]
	 * @return [type]           [description]
	 */
	public static function get_yourls_linkbox( $link = '', $post_id = 0, $count = 0 ) {

		// make the nonce
		$nonce  = wp_create_nonce( 'yourls_editor_delete' );

		// an empty
		$box    = '';

		// wrap the paragraph
		$box   .= '<p class="yourls-meta-block yourls-exist-block">';

			$box   .= '<input id="yourls-link" title="click to highlight" class="yourls-link-input" type="text" name="yourls-link" value="' . esc_url( $link ) . '" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';

			$box   .= '<span class="dashicons dashicons-no yourls-delete" title="' . __( 'Delete Link', 'wpyourls' ) . '" data-post-id="' . absint( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '"></span>';

		$box   .= '</p>';

		// the box with the counting
		$box   .= '<p class="yourls-meta-block howto"> ' . sprintf( _n( 'Your YOURLS link has generated %d click.', 'Your YOURLS link has generated %d clicks.', absint( $count ), 'wpyourls' ), absint( $count ) ) .'</p>';

		// and return it
		return $box;
	}

	/**
	 * build the inline action row for creating a YOURLS link
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	public static function create_row_action( $post_id = 0 ) {

		// make the nonce
		$nonce  = wp_create_nonce( 'yourls_inline_create_' . absint( $post_id ) );

		// return the link
		return '<a href="#" class="yourls-admin-row-link yourls-admin-create" data-nonce="' . esc_attr( $nonce ) . '" data-post-id="' . absint( $post_id ) . '">' . __( 'Create YOURLS', 'wpyourls' ) . '</a>';
	}

	/**
	 * build the inline action row for updating a YOURLS link
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	public static function update_row_action( $post_id = 0 ) {

		// make the nonce
		$nonce  = wp_create_nonce( 'yourls_inline_update_' . absint( $post_id ) );

		// return the link
		return '<a href="#" class="yourls-admin-row-link yourls-admin-update" data-nonce="' . esc_attr( $nonce ) . '" data-post-id="' . absint( $post_id ) . '">' . __( 'Update YOURLS', 'wpyourls' ) . '</a>';
	}

	/**
	 * take an array of text items and sanitize
	 * each one, then return the array
	 *
	 * @param  array  $items [description]
	 * @return [type]        [description]
	 */
	public static function sanitize_array_text( $items = array() ) {

		// set up an empty array for cleaning
		$clean  = array();

		// loop my items
		foreach( $items as $k => $v ) {
			$clean[$k]	= sanitize_text_field( $v );
		}

		// return the cleaned array
		return $clean;
	}

	/**
	 * take a provided keyword (if it exists) and make sure it's
	 * sanitized properly
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	public static function prepare_api_keyword( $string = '' ) {
		return preg_replace( '/[^A-Za-z0-9]/', '', $string );
	}

	/**
	 * fetch the permalink from a post ID and return it
	 * with the trailing slash removed
	 *
	 * @param  integer $post_id [description]
	 * @param  boolean $strip   [description]
	 * @return [type]           [description]
	 */
	public static function prepare_api_link( $post_id = 0, $strip = true ) {

		// bail without a link
		if ( empty( $post_id ) ) {
			return false;
		}

		// fetch the URL
		$link   = get_permalink( $post_id );

		// bail without a URL
		if ( empty( $link ) ) {
			return false;
		}

		// return the URL stripped (or not)
		return false !== $strip ? self::strip_trailing_slash( $link ) : $link;
	}

	/**
	 * remove the trailing slash from a URL
	 *
	 * @param  string $link  [description]
	 * @return [type]        [description]
	 */
	public static function strip_trailing_slash( $link = '' ) {
		return substr( $link, -1 ) == '/' ? substr( $link, 0, -1 ) : $link;
	}

	/**
	 * check permissions on saving meta data
	 *
	 * @param  integer $post_id [description]
	 * @param  string  $cap     [description]
	 * @return [type]           [description]
	 */
	public static function meta_save_check( $post_id = 0, $cap = 'edit_post' ) {

		// Bail out if running an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}

		// Bail out if running an ajax/
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		// Bail out if running a cron */
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		// Bail out if user does not have permissions
		if ( ! empty( $post_id ) && ! current_user_can( $cap, $post_id ) ) {
			return true;
		}

		// return false
		return false;
	}

// end class
}

// end exists check
}

// Instantiate our class
new YOURLSCreator_Helper();

