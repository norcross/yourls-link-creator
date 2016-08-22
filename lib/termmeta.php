<?php
/**
 * YOURLS Link Creator - Term Meta Module
 *
 * Contains term meta related functions
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

if ( ! class_exists( 'YOURLSCreator_TermMeta' ) ) {

/**
 * Set up and load our class.
 */
class YOURLSCreator_TermMeta
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {

		// Get all my terms we want to use this on.
		$terms  = YOURLSCreator_Helper::get_yourls_terms();

		// If no terms being used, bail.
		if ( empty( $terms ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts',            array( $this, 'scripts_styles'      ),  10      );
		add_action( 'init',                             array( $this, 'register_term_meta'  )           );

		// Now call the action for each taxonomy in the array.
		foreach ( $terms as $term ) {

			// The field displays.
			add_action( $term . '_add_form_fields',     array( $this, 'add_term_fields'    )           );
			add_action( $term . '_edit_form_fields',    array( $this, 'edit_term_fields'   )           );

			// And our term saving.
			add_action( 'create_' . $term,              array( $this, 'add_term_data'      ),  10, 2   );
			add_action( 'edit_' . $term,                array( $this, 'edit_term_data'     ),  10, 2   );
		}
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
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php', 'term-new.php' ) ) ) {
			return;
		}

		// Set our JS and CSS prefixes.
		$file   = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'yourls-admin' : 'yourls-admin.min';

		// Load our files.
		wp_enqueue_style( 'yourls-admin', plugins_url( '/css/' . $file . '.css', __FILE__ ), array(), YOURLS_VER, 'all' );
		wp_enqueue_script( 'yourls-admin', plugins_url( '/js/' . $file . '.js', __FILE__ ) , array( 'jquery' ), YOURLS_VER, true );
	}

	/**
	 * Register our term meta items.
	 *
	 * @return void
	 */
	public function register_term_meta() {
	    register_meta( 'term', '_yourls_term_url', array( $this, 'sanitize_url' ) );
	    register_meta( 'term', '_yourls_term_clicks', array( $this, 'sanitize_clicks' ) );
	}

	/**
	 * Show the fields on the "add new term" page for the taxonomies we enabled.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return mixed           The fields, or nothing.
	 */
	public function add_term_fields( $taxonomy ) {

		// Get all my terms we want to use this on.
		$terms  = YOURLSCreator_Helper::get_yourls_terms();

		// Make sure we're working with an approved term.
		if ( ! in_array( $taxonomy, $terms ) ) {
			return;
		}

		// The checkbox for the link generation.
		echo '<div class="form-field yourls-term-new-field yourls-term-new-create-field">';
			echo '<input type="checkbox" id="yourls-new-term-box" name="yourls-new-term-box" value="1">';
			echo '<label for="yourls-new-term-box">' . __( 'Generate YOURLS link', 'wpyourls' ) . '</label>';
		echo '</div>';

		// The keyword field to show if we indeed checked the box.
		echo '<div class="form-field yourls-term-new-field yourls-term-new-keyword-field">';
			echo '<input type="text" id="yourls-new-term-keyword" name="yourls-new-term-keyword" value="">';
			echo '<span class="description">' . __( 'optional keyword' ) . '</span>';
		echo '</div>';

		// Our nonce.
		wp_nonce_field( 'yourls_new_term_nonce', 'yourls_new_term_nonce', false, true );
	}

	/**
	 * Show the fields on the "edit term" page for the taxonomies we enabled.
	 *
	 * @param object $taxonomy  Current taxonomy term object.
	 *
	 * @return mixed             The fields, or nothing.
	 */
	public function edit_term_fields( $taxonomy ) {

		// Get all my terms we want to use this on.
		$terms  = YOURLSCreator_Helper::get_yourls_terms();

		// Make sure we're working with an approved term.
		if ( ! in_array( $taxonomy->taxonomy, $terms ) ) {
			return;
		}

		// Set our term ID.
		$id     = absint( $taxonomy->term_id );

		// Check to see if we have a URL or not.
		$link   = get_term_meta( $id, '_yourls_term_url', true );

		// Set up our labeling based on a link being present.
		$label  = ! empty( $link ) ? __( 'YOURLS Link Data', 'wpyourls' ) : __( 'Generate YOURLS link', 'wpyourls' );

		// Our main wrapper field.
		echo '<tr class="form-field yourls-term-form-field">';

			// The field label.
			echo '<th scope="row"><label>' . esc_html( $label ) . '</label></th>';

			// The field output itself, based on a link being present.
			echo '<td>';
				echo ! empty( $link ) ? self::edit_yourls_term_link( $link, $id ) : self::new_yourls_term_link();
			echo '</td>';

		// Close our main wrapper.
		echo '</tr>';
	}

	/**
	 * The field output for adding a new YOURLS link to an existing taxonomy.
	 *
	 * @return HTML              The field markup.
	 */
	public static function new_yourls_term_link() {

		// Set an empty.
		$field  = '';

		// The checkbox to show / hide the keyword.
		$field .= '<p class="yourls-term-edit-create-field">';
			$field .= '<input type="checkbox" id="yourls-edit-term-box" name="yourls-edit-term-box" value="1">';
			$field .= '<label for="yourls-edit-term-box">' . __( 'Generate YOURLS link', 'wpyourls' ) . '</label>';
		$field .= '</p>';

		// The keyword field to show if we indeed checked the box.
		$field .= '<p class="yourls-term-edit-keyword-field">';
			$field .= '<input type="text" id="yourls-edit-term-keyword" name="yourls-edit-term-keyword" value="">';
			$field .= '<span class="description">' . __( 'optional keyword' ) . '</span>';
		$field .= '</p>';

		// Our nonce.
		$field .= wp_nonce_field( 'yourls_term_edit_nonce', 'yourls_term_edit_nonce', false, false );

		// And return our field build.
		return $field;
	}

	/**
	 * The field output for displaying an YOURLS link to an existing taxonomy.
	 *
	 * @param  string  $link     The current link tied to the term.
	 * @param  integer $term_id  Current term ID.
	 *
	 * @return HTML              The field markup.
	 */
	public static function edit_yourls_term_link( $link = '', $term_id = 0 ) {

		// Get our current click count.
		$count  = YOURLSCreator_Helper::get_yourls_term_meta( $term_id, '_yourls_term_clicks', 0 );

		// Create a nonce for later
		$nonce  = wp_create_nonce( 'yourls_term_link_delete' );

		// Set an empty.
		$field  = '';

		// The field showing the current URL along with the delete button.
		$field .= '<p class="yourls-term-edit-update-field">';
			$field .= '<input id="yourls-term-link" title="click to highlight" class="yourls-term-link-input" type="text" name="yourls-term-link" value="' . esc_url( $link ) . '" readonly="readonly" onclick="this.focus();this.select()" />';
			$field .= '<span class="dashicons dashicons-no yourls-term-delete" title="' . __( 'Delete Link', 'wpyourls' ) . '" data-term-id="' . absint( $term_id ) . '" data-nonce="' . esc_attr( $nonce ) . '"></span>';
		$field .= '</p>';

		// the box with the counting
		$field .= '<p class="description"> ' . sprintf( _n( 'Your YOURLS link has generated %d click.', 'Your YOURLS link has generated %d clicks.', absint( $count ), 'wpyourls' ), absint( $count ) ) .'</p>';

		// And return our field build.
		return $field;
	}

	/**
	 * Save the term data being passed from the "add new term" page.
	 *
	 * @param integer $term_id  Term ID.
	 * @param integer $tax_id   Term taxonomy ID.
	 *
	 * @return void
	 */
	public function add_term_data( $term_id, $tax_id ) {

		// Without the checkbox, just bail.
		if ( empty( $_POST['yourls-new-term-box'] ) || empty( $_POST['taxonomy'] ) ) {
			return;
		}

		// Bail without the nonce check.
		if ( empty( $_POST['yourls_new_term_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['yourls_new_term_nonce'] ), 'yourls_new_term_nonce' ) ) {
			return;
		}

		// Set our taxonomy.
		$taxonomy    = sanitize_key( $_POST['taxonomy'] );

		// Make sure we're on the taxonomy we want.
		if ( ! in_array( $taxonomy, YOURLSCreator_Helper::get_yourls_terms() ) ) {
			return;
		}

		// Set our term name.
		$term_name   = ! empty( $_POST['tag-name'] ) ? sanitize_text_field( $_POST['tag-name'] ) : '';

		// Handle our keyword sanitization.
		$keyword    = ! empty( $_POST['yourls-new-term-keyword'] ) ? YOURLSCreator_Helper::prepare_api_keyword( $_POST['yourls-new-term-keyword'] ) : '';

		// And run the processing
		self::process_term_yourls( $term_id, $taxonomy, $keyword, $term_name );
	}

	/**
	 * Save the term data being passed from the "edit term" page.
	 *
	 * @param integer $term_id  Term ID.
	 * @param integer $tax_id   Term taxonomy ID.
	 *
	 * @return void
	 */
	public function edit_term_data( $term_id, $tax_id ) {

		// Without the checkbox, just bail.
		if ( empty( $_POST['yourls-edit-term-box'] ) || empty( $_POST['taxonomy'] ) ) {
			return;
		}

		// Bail without the nonce check.
		if ( empty( $_POST['yourls_term_edit_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['yourls_term_edit_nonce'] ), 'yourls_term_edit_nonce' ) ) {
			return;
		}

		// Make sure some YOURLS data was sent.
		if ( empty( $_POST['yourls-edit-term-box'] ) ) {
			return;
		}

		// Set our taxonomy.
		$taxonomy    = sanitize_key( $_POST['taxonomy'] );

		// Make sure we're on the taxonomy we want.
		if ( ! in_array( $taxonomy, YOURLSCreator_Helper::get_yourls_terms() ) ) {
			return;
		}

		// Set our term name.
		$term_name   = ! empty( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';

		// Handle our keyword sanitization.
		$keyword    = ! empty( $_POST['yourls-edit-term-keyword'] ) ? YOURLSCreator_Helper::prepare_api_keyword( $_POST['yourls-edit-term-keyword'] ) : '';

		// And run the processing
		self::process_term_yourls( $term_id, $taxonomy, $keyword, $term_name );
	}

	/**
	 * Sanitize the stored URL.
	 *
	 * @param  string $input  The returned URL.
	 *
	 * @return string $input  The sanitized data.
	 */
	public function sanitize_url( $input ) {
		return ! empty( $input ) ? esc_url( $input ) : false;
	}

	/**
	 * Sanitize the stored click count.
	 *
	 * @param  string $input  The returned count value.
	 *
	 * @return string $input  The sanitized data.
	 */
	public function sanitize_clicks( $input ) {
		return ! empty( $input ) ? absint( $input ) : '0';
	}

	/**
	 * Run the actual YOURLS function.
	 *
	 * @param  integer $term_id    The term ID to create the YOURLS link for.
	 * @param  string  $taxonomy   The taxonomy tied to the term ID.
	 * @param  string  $keyword    Optional keyword.
	 * @param  string  $term_name  Optional link title.
	 *
	 * @return void
	 */
	public static function process_term_yourls( $term_id = 0, $taxonomy = '', $keyword = '', $term_name = '' ) {

		// Bail if the API key or URL have not been entered.
		if(	false === $api = YOURLSCreator_Helper::get_yourls_api_data() ) {
			return;
		}

		// Get my term URL and title.
		$url    = YOURLSCreator_Helper::prepare_api_link( $term_id, $taxonomy );

		// Set my args for the API call.
		$args   = array( 'url' => esc_url( $url ), 'title' => $term_name, 'keyword' => $keyword );

		// Make the API call.
		$build  = YOURLSCreator_Helper::run_yourls_api_call( 'shorturl', $args );

		// Bail if empty data or error received.
		//
		// @TODO add some sort of error return.
		if ( empty( $build ) || false === $build['success'] ) {
			return;
		}

		// We have done our error checking and we are ready to go.
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// Get my short URL.
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// Update the post meta.
			update_term_meta( $term_id, '_yourls_term_url', $shorturl );
			update_term_meta( $term_id, '_yourls_term_clicks', '0' );

			// Do the action after saving.
			do_action( 'yourls_after_url_term_save', $term_id, $shorturl );
		}

		// And return.
		return;
	}

	// End class.
}

} // End exists check.

// Instantiate our class.
$YOURLSCreator_TermMeta = new YOURLSCreator_TermMeta();
$YOURLSCreator_TermMeta->init();
