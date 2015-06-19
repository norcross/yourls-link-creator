<?php
/**
 * YOURLS Link Creator - Display Module
 *
 * Contains template tag and other display functions
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

/**
 * display the box with the shortlink
 */
if ( ! function_exists( 'yourls_display_box' ) ) {

	function yourls_display_box( $post_id = 0, $echo = true ) {

		// fetch the post ID if not provided
		if ( empty( $post_id ) ) {

			// call the object
			global $post;

			// bail if missing
			if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
				return;
			}

			// set my post ID
			$post_id	= absint( $post->ID );
		}

		// check for the link
		if ( false === $link = YOURLSCreator_Helper::get_yourls_meta( $post_id ) ) {
			return;
		}

		// echo the box if requested
		if ( ! empty( $echo ) ) {
			echo YOURLSCreator_Front::yourls_display( $post_id );
		}

		// return the box
		return YOURLSCreator_Front::yourls_display( $post_id );
	}
}

/**
 * display the raw short URL
 */
if ( ! function_exists( 'get_yourls_shortlink' ) ) {

	function get_yourls_shortlink( $post_id = 0, $echo = false ) {

		// fetch the post ID if not provided
		if ( empty( $post_id ) ) {

			// call the object
			global $post;

			// bail if missing
			if ( empty( $post ) || ! is_object( $post ) ) {
				return;
			}

			// set my post ID
			$post_id	= absint( $post->ID );
		}

		// check for the link
		if ( false === $link = YOURLSCreator_Helper::get_yourls_meta( $post_id ) ) {
			return;
		}

		// echo the link if requested
		if ( ! empty( $echo ) ) {
			echo esc_url( $link );
		}

		// return the link
		return esc_url( $link );
	}

}

