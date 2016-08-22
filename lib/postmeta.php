<?php
/**
 * YOURLS Link Creator - Post Meta Module
 *
 * Contains post meta related functions
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

if ( ! class_exists( 'YOURLSCreator_PostMeta' ) ) {

/**
 * Set up and load our class.
 */
class YOURLSCreator_PostMeta
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts',        array( $this, 'scripts_styles'      ),  10      );
		add_action( 'add_meta_boxes',               array( $this, 'yourls_metabox'      ),  11      );
		add_action( 'save_post',                    array( $this, 'yourls_keyword'      )           );
		add_action( 'save_post',                    array( $this, 'yourls_on_save'      )           );
		add_action( 'manage_posts_custom_column',   array( $this, 'display_columns'     ),  10, 2   );
		add_filter( 'manage_posts_columns',         array( $this, 'register_columns'    )           );
		add_filter( 'post_row_actions',             array( $this, 'yourls_row_action'   ),  10, 2   );
		add_filter( 'page_row_actions',             array( $this, 'yourls_row_action'   ),  10, 2   );
	}

	/**
	 * Load our admin side scripts and stylesheets.
	 *
	 * @param  string $hook  The admin page we are on.
	 *
	 * @return void
	 */
	public function scripts_styles( $hook ) {

		// Bail if not on the right part.
		if ( ! in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// Set our JS and CSS prefixes.
		$file   = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'yourls-admin' : 'yourls-admin.min';

		// Load our files.
		wp_enqueue_style( 'yourls-admin', plugins_url( '/css/' . $file . '.css', __FILE__ ), array(), YOURLS_VER, 'all' );
		wp_enqueue_script( 'yourls-admin', plugins_url( '/js/' . $file . '.js', __FILE__ ) , array( 'jquery' ), YOURLS_VER, true );
		wp_localize_script( 'yourls-admin', 'yourlsAdmin', array(
			'shortSubmit'   => '<a onclick="prompt(\'URL:\', jQuery(\'#shortlink\').val()); return false;" class="button button-small" href="#">' . __( 'Get Shortlink' ) . '</a>',
			'defaultError'  => __( 'There was an error with your request.' )
		));
	}

	/**
	 * Call the metabox if on an appropriate post type and post status.
	 *
	 * @return void
	 */
	public function yourls_metabox() {

		// Fetch the global post object.
		global $post;

		// Make sure we're working with an approved post type.
		if ( ! in_array( $post->post_type, YOURLSCreator_Helper::get_yourls_types() ) ) {
			return;
		}

		// Bail if the API key or URL have not been entered.
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// Only fire if user has the option.
		if(	false === $check = YOURLSCreator_Helper::check_yourls_cap() ) {
			return;
		}

		// Now add the meta box.
		add_meta_box( 'yourls-post-display', __( 'YOURLS Shortlink', 'wpyourls' ), array( __class__, 'yourls_post_display' ), $post->post_type, 'side', 'high' );
	}

	/**
	 * Display YOURLS shortlink if present.
	 *
	 * @param  object $post  The global WP_Post object.
	 *
	 * @return HTML          The meta box.
	 */
	public static function yourls_post_display( $post ) {

		// Cast our post ID.
		$post_id    = absint( $post->ID );

		// Check for a link and click counts.
		$link   = YOURLSCreator_Helper::get_yourls_meta( $post_id, '_yourls_url' );

		// If we have no link, display our box.
		if ( empty( $link ) ) {

			// Display the box.
			echo YOURLSCreator_Helper::get_yourls_subbox( $post_id );

			// And return.
			return;
		}

		// We have a shortlink. show it along with the count.
		if( ! empty( $link ) ) {

			// Fetch the count.
			$count  = YOURLSCreator_Helper::get_yourls_meta( $post_id, '_yourls_clicks', '0' );

			// And echo the box.
			echo YOURLSCreator_Helper::get_yourls_linkbox( $link, $post_id, $count );
		}
	}

	/**
	 * Cur check for a custom YOURLS keyword.
	 *
	 * @param  integer $post_id  The post ID for the potential keyword.
	 *
	 * @return void
	 */
	public function yourls_keyword( $post_id ) {

		// Run various checks to make sure we aren't doing anything weird.
		if ( YOURLSCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// Make sure we're working with an approved post type.
		if ( ! in_array( get_post_type( $post_id ), YOURLSCreator_Helper::get_yourls_types() ) ) {
			return;
		}

		// We don't have a keyword, so delete any meta and return.
		if ( empty( $_POST['yourls-keyw'] ) ) {

			// Delete the actual keyword.
			delete_post_meta( $post_id, '_yourls_keyword' );

			// And go about our business.
			return;
		}

		// We have a keyword. So sanitize it.
		$keywd  = YOURLSCreator_Helper::prepare_api_keyword( $_POST['yourls-keyw'] );

		// Update the post meta.
		update_post_meta( $post_id, '_yourls_keyword', $keywd );
	}

	/**
	 * Create yourls link on publish if one doesn't exist
	 *
	 * @param  integer $post_id  The post ID for the YOURLS url to be saved.
	 *
	 * @return void
	 */
	public function yourls_on_save( $post_id ) {

		// Bail if this is an import since it'll potentially mess up the process.
		if ( ! empty( $_POST['import_id'] ) ) {
			return;
		}

		// Run various checks to make sure we aren't doing anything weird.
		if ( YOURLSCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// Bail if we aren't working with a published or scheduled post.
		if ( ! in_array( get_post_status( $post_id ), YOURLSCreator_Helper::get_yourls_status( 'save' ) ) ) {
			return;
		}

		// Make sure we're working with an approved post type.
		if ( ! in_array( get_post_type( $post_id ), YOURLSCreator_Helper::get_yourls_types() ) ) {
			return;
		}

		// Bail if the API key or URL have not been entered.
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// Bail if user hasn't checked the box.
		if ( false === $onsave = YOURLSCreator_Helper::get_yourls_option( 'sav' ) ) {
		   	return;
		}

		// Check for a link and bail if one exists.
		if ( false !== $exist = YOURLSCreator_Helper::get_yourls_meta( $post_id ) ) {
			return;
		}

		// Get my post URL and title.
		$url    = YOURLSCreator_Helper::prepare_api_link( $post_id );
		$title  = get_the_title( $post_id );

		// And optional keyword.
		$keywd  = ! empty( $_POST['yourls-keyw'] ) ? YOURLSCreator_Helper::prepare_api_keyword( $_POST['yourls-keyw'] ) : '';

		// Set my args for the API call.
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// Make the API call.
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'shorturl', $args );

		// Bail if empty data or error received.
		if ( empty( $build ) || false === $build['success'] ) {
			return;
		}

		// We have done our error checking and we are ready to go.
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// Get my short URL.
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// Update the post meta.
			update_post_meta( $post_id, '_yourls_url', $shorturl );
			update_post_meta( $post_id, '_yourls_clicks', '0' );

			// Do the action after saving.
			do_action( 'yourls_after_url_save', $post_id, $shorturl );
		}
	}

	/**
	 * The custom display columns for click counts.
	 *
	 * @param  string  $column   The column name.
	 * @param  integer $post_id  The post ID for the YOURLS url.
	 *
	 * @return mixed/HTML        The markup and data for the column.
	 */
	public function display_columns( $column, $post_id ) {

		// Start my column output.
		switch ( $column ) {

		case 'yourls-click':

			echo '<span>' . YOURLSCreator_Helper::get_yourls_meta( $post_id, '_yourls_clicks', '0' ) . '</span>';

			break;

			// End all case breaks.
		}
	}

	/**
	 * Register and display columns.
	 *
	 * @param  array $columns  The existing array of columns for th post type.
	 *
	 * @return array $columns  The updated array of columns for th post type.
	 */
	public function register_columns( $columns ) {

		// Call the global post type object.
		global $post_type_object;

		// Make sure we're working with an approved post type.
		if ( ! in_array( $post_type_object->name, YOURLSCreator_Helper::get_yourls_types() ) ) {
			return $columns;
		}

		// If for some reason the column already exists, return the array as-is.
		if ( array_key_exists( 'yourls-click', $columns ) ) {
			return $columns;
		}

		// Build our new column markup.
		$columns['yourls-click'] = '<span title="' . __( 'YOURLS Clicks', 'wpyourls' ) . '" class="dashicons dashicons-editor-unlink"></span>';

		// Return the columns.
		return $columns;
	}

	/**
	 * The action row link based on the status.
	 *
	 * @param  array $actions  The existing array of post row actions.
	 * @param  object $post    The WP_Post object.
	 *
	 * @return array $actions  The updated array of post row actions.
	 */
	public function yourls_row_action( $actions, $post ) {

		// make sure we're working with an approved post type
		if ( ! in_array( $post->post_type, YOURLSCreator_Helper::get_yourls_types() ) ) {
			return $actions;
		}

		// Bail if we aren't working with a published or scheduled post.
		if ( ! in_array( get_post_status( $post->ID ), YOURLSCreator_Helper::get_yourls_status() ) ) {
			return $actions;
		}

		// Vheck for existing and add our new action.
		if ( false === $exist = YOURLSCreator_Helper::get_yourls_meta( $post->ID ) ) {
			$actions['create-yourls'] = YOURLSCreator_Helper::create_row_action( $post->ID );
		} else {
			$actions['update-yourls'] = YOURLSCreator_Helper::update_row_action( $post->ID );
		}

		// Return the actions.
		return $actions;
	}

	// End class.
}

} // End exists check.

// Instantiate our class.
$YOURLSCreator_PostMeta = new YOURLSCreator_PostMeta();
$YOURLSCreator_PostMeta->init();
