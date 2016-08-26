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

/**
 * Set up and load our class.
 */
class YOURLSCreator_Helper
{

	/**
	 * Get an option from the serialized array or the entire thing.
	 *
	 * @param  string $key   The key inside the serialized array.
	 *
	 * @return mixed  $data  The single data item, the entire array, or false if none.
	 */
	public static function get_yourls_option( $key = '' ) {

		// Fetch the entire data array.
		$data   = get_option( 'yourls_options', array() );

		// Bail if none exists.
		if ( empty( $data ) ) {
			return false;
		}

		// Return the entire thing if no key is requested.
		if ( empty( $key ) ) {
			return $data;
		}

		// Return the specific key if it exists.
		if ( ! empty( $key ) && isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}

		// Return false, nothing there.
		return false;
	}

	/**
	 * Get a post meta item with YOURLS data.
	 *
	 * @param  integer $post_id   The post ID tied to the meta.
	 * @param  string  $key       The meta key to look up.
	 * @param  string  $fallback  An optional default value if none exists.
	 *
	 * @return mixed              The postmeta data, fallback value, or false.
	 */
	public static function get_yourls_meta( $post_id = 0, $key = '_yourls_url', $fallback = false ) {

		// Fetch my postmmeta item.
		$item	= get_post_meta( $post_id, $key, true );

		// Return the item if there.
		if ( ! empty( $item ) ) {
			return $item;
		}

		// Return either empty or fallback.
		return isset( $fallback ) ? $fallback : false;
	}

	/**
	 * Get a term meta item with YOURLS data.
	 *
	 * @param  integer $term_id   The term ID tied to the meta.
	 * @param  string  $key       The meta key to look up.
	 * @param  string  $fallback  An optional default value if none exists.
	 *
	 * @return mixed              The postmeta data, fallback value, or false.
	 */
	public static function get_yourls_term_meta( $term_id = 0, $key = '_yourls_term_url', $fallback = false ) {

		// Fetch my postmmeta item.
		$item	= get_term_meta( $term_id, $key, true );

		// Return the item if there.
		if ( ! empty( $item ) ) {
			return $item;
		}

		// Return either empty or fallback.
		return isset( $fallback ) ? $fallback : false;
	}

	/**
	 * Get the stored URL for the item.
	 *
	 * @param  integer $item_id  The ID of the item being checked.
	 * @param  string  $type     Whether we want a post or term URL.
	 *
	 * @return string  $link     The URL or false if none exists.
	 */
	public static function get_yourls_link( $item_id = 0, $type = 'post' ) {

		// Since most are a post, check that first.
		if ( 'post' === $type ) {
			$link   = get_post_meta( $item_id, '_yourls_url', true );
		}

		// Now check for terms.
		if ( 'term' === $type ) {
			$link   = get_term_meta( $item_id, '_yourls_term_url', true );
		}

		// Return the URL, or false.
		return ! empty( $link ) ? $link : false;
	}

	/**
	 * Get the post types that YOURLS is enabled for.
	 *
	 * @return array  The array of enabled post types.
	 */
	public static function get_yourls_types() {

		// Fetch any custom post types saved by the user.
		$custom = self::get_yourls_option( 'typ' );

		// Build an array of the default post types we are enabling.
		$built  = array( 'post' => 'post', 'page' => 'page' );

		// Filter the default enabled post types.
		$built  = apply_filters( 'yourls_post_types', $built );

		// Return the full array.
		return ! empty( $custom ) ? array_merge( $custom, $built ) : $built;
	}

	/**
	 * Get the terms that YOURLS is enabled for.
	 *
	 * @return array  The array of enabled terms.
	 */
	public static function get_yourls_terms() {

		// Fetch any custom taxonomies saved by the user.
		$custom = self::get_yourls_option( 'trm' );

		// Build an array of the default taxonomies we are enabling.
		$built  = array( 'category', 'post_tag' );

		// Filter the default enabled taxonomies.
		$built  = apply_filters( 'yourls_taxonomies', $built );

		// Return the full array.
		return ! empty( $custom ) ? array_merge( $custom, $built ) : $built;
	}

	/**
	 * Get the post statuses that YOURLS is enabled for.
	 *
	 * @return array  The array of enabled post statuses.
	 */
	public static function get_yourls_status( $action = '' ) {

		// Return only publish for saving.
		if ( ! empty( $action ) && 'save' === $action ) {
			return apply_filters( 'yourls_post_status', array( 'publish' ), $action  );
		}

		// Return the default, filtered.
		return apply_filters( 'yourls_post_status', array( 'publish', 'future' ), $action );
	}

	/**
	 * Check a post ID for a saved custom keyword.
	 *
	 * @param  integer $post_id  The post ID tied to the meta.
	 *
	 * @return string            Either the keyword, or false.
	 */
	public static function get_yourls_keyword( $post_id = 0 ) {

		// Check for a keyword.
		$keywd  = get_post_meta( $post_id, '_yourls_keyword', true );

		// Return our keyword, or false.
		return ! empty( $keywd ) ? $keywd : false;
	}

	/**
	 * Get the two components of the API and return them (or one if key is provided).
	 *
	 * @param  string $key  Optional single key stored as part of the API data.
	 *
	 * @return mixed        The entire data array, or a single key.
	 */
	public static function get_yourls_api_data( $key = '' ) {

		// Fetch the stored option array.
		$option = self::get_yourls_option();

		// If anything is missing, return false.
		if ( empty( $option ) || empty( $option['url'] ) || empty( $option['api'] ) ) {
			return false;
		}

		// Make a data array.
		$data   = array( 'url' => $option['url'], 'key' => $option['api'] );

		// Return one or the entire thing.
		return empty( $key ) ? $data : $data[ $key ];
	}

	/**
	 * Get all the post IDs that contain the YOURLS url.
	 *
	 * @param  string $key  The meta key in the database we want to fetch.
	 *
	 * @return array        The post IDs containing the meta key.
	 */
	public static function get_yourls_post_ids( $key = '_yourls_url' ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$query  = $wpdb->prepare("
			SELECT	post_id
			FROM	$wpdb->postmeta
			WHERE	meta_key = '%s'
		", esc_sql( $key ) );

		// Fetch the column.
		$ids    = $wpdb->get_col( $query );

		// Return the array of IDs or false if none.
		return ! empty( $ids ) ? $ids : false;
	}

	/**
	 * Get all the term IDs that contain the YOURLS url.
	 *
	 * @param  string $key  The meta key in the database we want to fetch.
	 *
	 * @return array        The term IDs containing the meta key.
	 */
	public static function get_yourls_term_ids( $key = '_yourls_term_url' ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$query  = $wpdb->prepare("
			SELECT	term_id
			FROM	$wpdb->termmeta
			WHERE	meta_key = '%s'
		", esc_sql( $key ) );

		// Fetch the column.
		$ids    = $wpdb->get_col( $query );

		// Return the array of IDs or false if none.
		return ! empty( $ids ) ? $ids : false;
	}

	/**
	 * Get all the IDs that contain a YOURLS link.
	 *
	 * @return array  The post and term IDs containing the meta key.
	 */
	public static function get_all_yours_ids() {

		// First set an empty array.
		$ids    = array();

		// Run the check for posts.
		if ( false !== $post = self::get_yourls_post_ids() ) {
			$ids['post']    = $post;
		}

		// Run the check for terms.
		if ( false !== $term = self::get_yourls_term_ids() ) {
			$ids['term']    = $term;
		}

		// Return our items.
		return ! empty( $ids ) ? $ids : false;
	}

	/**
	 * Get the API endpoint URL.
	 *
	 * @return string   The stored API endpoint.
	 */
	public static function get_yourls_api_url() {

		// Fetch the stored base URL link.
		if ( false === $stored = self::get_yourls_api_data( 'url' ) ) {
			return false;
		}

		// Parse the link.
		$parsed = parse_url( esc_url( $stored ) );

		// Bail if its too malformed or our pieces are missing.
		if ( ! $parsed || empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
			return false;
		}

		// Build the base URL again.
		$base   = $parsed['scheme'] . '://' . $parsed['host'];

		// Check for a subfolder and add the path if it exists.
		if ( ! empty( $parsed['path'] ) ) {
			$base   = self::strip_trailing_slash( $base ) . $parsed['path'];
		}

		// Build the API link.
		$link   = self::strip_trailing_slash( $base ) . '/yourls-api.php';

		// Return it with optional filter.
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
	public static function run_yourls_api_call( $action = '', $args = array(), $user = true, $format = 'json', $decode = true ) {

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
		if( false !== $user && false === $check = YOURLSCreator_Helper::check_yourls_cap( 'ajax' ) ) {
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
			'redirection'  => 5,
			'sslverify'    => false,
			'httpversion'  => '1.0',
			'blocking'     => true,
			'headers'      => array(),
			'body'         => $args,
			'cookies'      => array()
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
	 * run the API call for getting a single short URL
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	public static function get_single_shorturl( $post_id = 0, $check = 'sav' ) {

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), self::get_yourls_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = self::get_yourls_api_data() ) {
			return;
		}

		// bail if user hasn't checked the box
		if ( false === $onschd = self::get_yourls_option( $check ) ) {
		   	return;
		}

		// check for a link and bail if one exists
		if ( false !== $exist = self::get_yourls_meta( $post_id ) ) {
			return;
		}

		// get my post URL and title
		$url    = self::prepare_api_link( $post_id );
		$title  = get_the_title( $post_id );

		// check for a keyword
		$keywd  = self::get_yourls_keyword( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = self::run_yourls_api_call( 'shorturl', $args, false );

		// bail if empty data or error received
		if ( empty( $build ) || false === $build['success'] ) {
			return;
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {
			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_yourls_url', $shorturl );
			update_post_meta( $post_id, '_yourls_clicks', '0' );

			// do the action after saving
			do_action( 'yourls_after_url_save', $post_id, $shorturl );
		}

		// we have a keyword and we're going to store it
		if( ! empty( $keywd ) ) {
			// update the post meta
			update_post_meta( $post_id, '_yourls_keyword', $keywd );
		} else {
			// delete it if none was passed
			delete_post_meta( $post_id, '_yourls_keyword' );
		}
	}

	/**
	 * Make the API call to get the individual click count.
	 *
	 * @param  integer $item_id  The ID of the item (post or term).
	 * @param  string  $type     Whether we want a post or term URL.
	 *
	 * @return array             The count data.
	 */
	public static function get_single_click_count( $item_id = 0, $type = 'post' ) {

		// Get my URL and bail if we don't have one.
		if ( false === $url = self::get_yourls_link( $item_id, $type ) ) {
			return false;
		}

		// make the API call
		$build  = self::run_yourls_api_call( 'url-stats', array( 'shorturl' => esc_url( $url ) ), false );

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
	 * Convert old YOURLS keys (from old plugins) to the new one.
	 *
	 * @param  string  $key    The old meta key to convert.
	 *
	 * @return integer $count  The total amount of items updated.
	 */
	public static function convert_yourls_keys( $key = 'yourls_shorturl' ) {

		// Bail without a key.
		if ( empty( $key ) ) {
			return false;
		}

		// Set up SQL query.
		global $wpdb;

		// Prepare my query.
		$setup  = $wpdb->prepare("
			UPDATE $wpdb->postmeta
			SET    meta_key = '%s'
			WHERE  meta_key = '%s'
			",
			esc_sql( '_yourls_url' ), esc_sql( $key )
		);

		// Run SQL query.
		$query = $wpdb->query( $setup );

		// Return the count if greater than zero, or false.
		return absint( $query ) > 0 ? $query : false;
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

		// check for a keyword
		$keywd  = get_post_meta( $post_id, '_yourls_keyword', true );

		// an empty
		$box    = '';

		// display the box
		$box   .= '<p class="yourls-meta-block yourls-input-block">';

			// input field for the optional keyword
			$box   .= '<input id="yourls-keyw" class="yourls-keyw" size="20" type="text" name="yourls-keyw" value="' . esc_attr( $keywd ) . '" tabindex="501" />';

			// simple instruction
			$box   .= '<span class="description">' . __( 'optional keyword', 'wpyourls' ) . '</span>';

		// first check our post status
		if ( ! in_array( get_post_status( $post_id ), array( 'publish', 'future', 'pending' ) ) ) {
			$box   .= '<p class="yourls-meta-block howto">' . __( 'a YOURLS link cannot be generated until the post is saved.', 'wpyourls' ) . '</p>';
		} else {
			$box   .= self::yourls_submit_box( $post_id );
		}

		// and return it
		return $box;
	}

	/**
	 * display the submit box (with nonce) for the metabox
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	public static function yourls_submit_box( $post_id = 0 ) {

		// make the nonce
		$nonce  = wp_create_nonce( 'yourls_editor_create' );

		// our empty
		$box    = '';

		// display the box
		$box   .= '<p class="yourls-meta-block yourls-submit-block">';

			// button to actually fetch the link
			$box   .= '<input type="button" class="button button-secondary button-small yourls-api" id="yourls-get" name="yourls-get" value="' . __( 'Create YOURLS link', 'wpyourls' ) . '" tabindex="502" data-nonce="' . esc_attr( $nonce ) . '" data-post-id="' . absint( $post_id ) . '" />';

			// the spinner
			$box   .= '<span class="spinner yourls-spinner"></span>';

		$box   .= '</p>';

		// send it back
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

		// check for a keyword
		$keywd  = get_post_meta( $post_id, '_yourls_keyword', true );

		// an empty
		$box    = '';

		// wrap the paragraph
		$box   .= '<p class="yourls-meta-block yourls-exist-block">';

			$box   .= '<input id="yourls-link" title="click to highlight" class="yourls-link-input" type="text" name="yourls-link" value="' . esc_url( $link ) . '" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';

			$box   .= '<span class="dashicons dashicons-no yourls-delete" title="' . __( 'Delete Link', 'wpyourls' ) . '" data-post-id="' . absint( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '"></span>';

		$box   .= '</p>';

		// the box with the counting
		$box   .= '<p class="yourls-meta-block howto"> ' . sprintf( _n( 'Your YOURLS link has generated %d click.', 'Your YOURLS link has generated %d clicks.', absint( $count ), 'wpyourls' ), absint( $count ) ) .'</p>';

		// hidden field for the optional keyword
		$box   .= '<input id="yourls-keyw" class="yourls-keyw" type="hidden" name="yourls-keyw" value="' . esc_attr( $keywd ) . '" />';

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
	 * fetch the API status we checked for
	 * @return [type] [description]
	 */
	public static function get_api_status_data() {

		// fetch the option key we stored
		if ( false === $check = get_option( 'yourls_api_test' ) ) {
			return;
		}

		// set a default data aray
		$data   = array(
			'icon'  => '<span class="api-status-icon api-status-icon-unknown"></span>',
			'text'  => __( 'The status of the YOURLS API could not be determined.', 'wpyourls' )
		);

		// handle the success
		if ( $check == 'connect' ) {

			// return the icon and text
			$data   = array(
				'icon'  => '<span class="api-status-icon api-status-icon-good"></span>',
				'text'  => __( 'The YOURLS API is currently accessible.', 'wpyourls' )
			);
		}

		// handle the failure
		if ( $check == 'noconnect' ) {

			// return the icon and text
			$data   = array(
				'icon'  => '<span class="api-status-icon api-status-icon-bad"></span>',
				'text'  => __( 'The YOURLS API is currently NOT accessible.', 'wpyourls' )
			);
		}

		// return it
		return $data;
	}

	/**
	 * Take a provided keyword (if it exists) and make sure it's sanitized properly.
	 *
	 * @param  string $string  The keyword string.
	 *
	 * @return string $string  The cleaned keyword string.
	 */
	public static function prepare_api_keyword( $string = '' ) {

		// Check for the filter.
		$filter = apply_filters( 'yourls_keyword_filter', '/[^A-Za-z0-9]/' );

		// Return it.
		return preg_replace( $filter, '', $string );
	}

	/**
	 * Fetch the link from a post or term ID and return it with optional trailing slash removed.
	 *
	 * @param  integer $item_id  The ID of the item (either a post or term).
	 * @param  string  $term     The term we're looking up (if it's a taxonomy).
	 *
	 * @return string  $link     The returned URL or false if none exists.
	 */
	public static function prepare_api_link( $item_id = 0 , $term = '' ) {

		// Bail without an ID.
		if ( empty( $item_id ) ) {
			return false;
		}

		// First check if we have a post object for our item and fetch the URL.
		if ( false !== $post_data = get_post( $item_id ) ) {
			$link   = get_permalink( $item_id );
		}

		// Now check if we have a post object for our item and fetch the URL.
		if ( ! empty( $term ) && false !== $term_data = term_exists( $item_id, $term ) ) {
			$link   = get_term_link( $item_id, $term );
		}

		// Bail without a URL.
		if ( empty( $link ) || is_wp_error( $link ) ) {
			return false;
		}

		// Filter the strip check.
		$strip  = apply_filters( 'yourls_strip_urls', false, $item_id );

		// Return the URL stripped (or not).
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
	 * check the user capability with an optional filter
	 *
	 * @param  string $action [description]
	 * @param  string $cap    [description]
	 * @return [type]         [description]
	 */
	public static function check_yourls_cap( $action = 'display', $cap = 'edit_others_posts' ) {

		// set the cap
		$cap    = apply_filters( 'yourls_user_cap', $cap, $action );

		// return it
		return ! current_user_can( $cap ) ? false : true;
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

		// Bail out if running an ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		// Bail out if running a cron, unless we've skipped that
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		// Bail out if user does not have permissions
		if ( false === $check = self::check_yourls_cap( 'save' ) ) {
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

