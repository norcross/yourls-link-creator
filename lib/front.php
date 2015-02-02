<?php
/**
 * YOURLS Link Creator - Front End Module
 *
 * Contains front end display functions
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

if ( ! class_exists( 'YOURLSCreator_Front' ) ) {

// Start up the engine
class YOURLSCreator_Front
{

	/**
	 * This is our constructor
	 *
	 * @return YOURLSCreator_Front
	 */
	public function __construct() {
		add_action( 'wp_head',                      array( $this, 'shortlink_meta'      )           );
		add_action( 'yourls_display',               array( $this, 'yourls_display'      )           );
	}

	/**
	 * add shortlink into head if present
	 *
	 * @return [type] [description]
	 */
	public function shortlink_meta() {

		// no shortlinks exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// check options to see if it's enabled
		if ( false === YOURLSCreator_Helper::get_yourls_option( 'sht' ) ) {
			return;
		}

		// call the global post object
		global $post;

		// check existing postmeta for YOURLS
		$link   = YOURLSCreator_Helper::get_yourls_meta( $post->ID );

		// got a YOURLS? well then add it
		if( ! empty( $link ) ) {
			echo '<link href="' . esc_url( $link ) . '" rel="shortlink">' . "\n";
		}
	}

	/**
	 * our pre-built template tag
	 *
	 * @return [type] [description]
	 */
	public function yourls_display( $echo = true ) {

		global $post;

		// check existing postmeta for YOURLS
		$link   = YOURLSCreator_Helper::get_yourls_meta( $post->ID );

		// bail if there is no shortlink
		if ( empty( $link ) ) {
			return;
		}

		// set an empty
		$show   = '';

		// build the markup
		$show  .= '<p class="yourls-display">' . __( 'Shortlink:', 'wpyourls' );
			$show  .= '<input id="yourls-link" size="28" title="' . __( 'click to highlight', 'wpyourls' ) . '" type="text" name="yourls-link" value="'. esc_url( $link ) .'" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';
		$show  .= '</p>';

		// filter it
		$box    = apply_filters( 'yourls_template_tag', $show, $post->ID );

		// echo the box if requested
		if ( ! empty( $echo ) ) {
			echo $box;
		}

		// return the box
		return $box;
	}

// end class
}

// end exists check
}

// Instantiate our class
new YOURLSCreator_Front();

