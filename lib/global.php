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

// end class
}

// end exists check
}

// Instantiate our class
new YOURLSCreator_Global();

