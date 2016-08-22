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
		//	add_action( 'edit_' . $term,                array( $this, 'edit_term_data'     ),  10, 2   );
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
		wp_localize_script( 'yourls-admin', 'yourlsAdmin', array(
			'shortSubmit'   => '<a onclick="prompt(\'URL:\', jQuery(\'#shortlink\').val()); return false;" class="button button-small" href="#">' . __( 'Get Shortlink' ) . '</a>',
			'defaultError'  => __( 'There was an error with your request.' )
		));
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

		// Get my individual data items.
		$link   = get_term_meta( $taxonomy->term_id, '_yourls_term_url' );

		// If we are creating a YOURLS for the first time, build those fields.
		if ( empty( $link ) ) {

			// The field for the keyword.
			echo '<tr class="form-field yourls-term-form-field yourls-term-form-field-keyword">';

				// The field label.
				echo '<th scope="row"><label>' . __( 'Generate YOURLS link', 'wpyourls' ) . '</label></th>';

				// The field input.
				echo '<td>';
					echo '<p>';

					echo '<input id="yourls-term-keyword" class="yourls-term-keyword-field regular-text" type="text" name="yourls-term-data[keyword]" value="" />';

					echo '<span class="description">' . __( 'optional keyword' ) . '</span>';

					echo '</p>';

					echo '<input type="button" class="button button-secondary button-small yourls-api" id="yourls-get" name="yourls-get" value="' . __( 'Create YOURLS link', 'wpyourls' ) . '" data-term-id="' . absint( $taxonomy->term_id ) . '" />';

					// And our create / update type.
					echo '<input type="hidden" name="yourls-term-create" id="yourls-term-create" value="1" />';

					// Our nonce.
					wp_nonce_field( 'yourls_term_nonce', 'yourls_term_nonce', false, true );

				// Close the row item.
				echo '</td>';

			// Close the field.
			echo '</tr>';
		}
	}

	/**
	 * Save the term data being passed.
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

		// Set our term name.
		$term_name   = ! empty( $_POST['tag-name'] ) ? sanitize_text_field( $_POST['tag-name'] ) : '';

		// Get all my terms we want to use this on.
		$term_array = YOURLSCreator_Helper::get_yourls_terms();

		// Make sure we're on the taxonomy we want.
		if ( empty( $term_array ) || ! in_array( $taxonomy, $term_array ) ) {
			return;
		}

		// Handle our keyword sanitization.
		$keyword    = ! empty( $_POST['yourls-new-term-keyword'] ) ? YOURLSCreator_Helper::prepare_api_keyword( $_POST['yourls-new-term-keyword'] ) : '';

		// And run the processing
		self::process_term_yourls( $term_id, $taxonomy, $keyword, $term_name );
	}

	/**
	 * Save the term data being passed.
	 *
	 * @param integer $term_id   Current term ID.
	 *
	 * @return void
	 */
	public function edit_term_data( $term_id ) {

		// Bail without the nonce check.
		if ( empty( $_POST['yourls_term_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['yourls_term_nonce'] ), 'yourls_term_nonce' ) ) {
			return;
		}

		// Get all my terms we want to use this on.
		$terms  = YOURLSCreator_Helper::get_yourls_terms();

		// Make sure we're on the taxonomy we want.
		if ( empty( $terms ) || ! isset( $_POST['taxonomy'] ) || ! in_array( $_POST['taxonomy'], $terms ) ) {
			return;
		}

		// Make sure some YOURLS data was sent.
		if ( empty( $_POST['yourls-term-data'] ) || empty( $_POST['yourls-term-process'] ) ) {
			return;
		}

		// Set the flag for our processing type.
		$type   = sanitize_key( $_POST['yourls-term-process'] );

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

		return;
	}

	// End class.
}

} // End exists check.

// Instantiate our class.
$YOURLSCreator_TermMeta = new YOURLSCreator_TermMeta();
$YOURLSCreator_TermMeta->init();
