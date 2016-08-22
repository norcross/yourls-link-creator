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


if ( ! function_exists( 'yourls_display_box' ) ) {
	/**
	 * Display the box with the shortlink.
	 *
	 * @param  integer $post_id  Current post ID.
	 * @param  boolean $echo     Whether to echo out the link or just return it.
	 *
	 * @return mixed             The HTML markup.
	 */
	function yourls_display_box( $post_id = 0, $echo = true ) {

		// Fetch the post ID if not provided.
		if ( empty( $post_id ) ) {

			// Call the object.
			global $post;

			// Bail if missing.
			if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
				return;
			}

			// Set my post ID.
			$post_id	= absint( $post->ID );
		}

		// Check for the link.
		if ( false === $link = YOURLSCreator_Helper::get_yourls_meta( $post_id ) ) {
			return;
		}

		// Echo the box if requested.
		if ( ! empty( $echo ) ) {
			echo YOURLSCreator_Front::yourls_display( $post_id );
		}

		// Return the box.
		return YOURLSCreator_Front::yourls_display( $post_id );
	}
}


if ( ! function_exists( 'get_yourls_shortlink' ) ) {
	/**
	 * Get the raw shortlink for a post.
	 *
	 * @param  integer $post_id  Current post ID.
	 * @param  boolean $echo     Whether to echo out the link or just return it.
	 *
	 * @return string            The link, or nothing.
	 */
	function get_yourls_shortlink( $post_id = 0, $echo = false ) {

		// Fetch the post ID if not provided.
		if ( empty( $post_id ) ) {

			// Call the object.
			global $post;

			// Bail if missing.
			if ( empty( $post ) || ! is_object( $post ) ) {
				return;
			}

			// Set my post ID.
			$post_id	= absint( $post->ID );
		}

		// Check for the link.
		if ( false === $link = YOURLSCreator_Helper::get_yourls_meta( $post_id ) ) {
			return;
		}

		// Echo the link if requested.
		if ( ! empty( $echo ) ) {
			echo esc_url( $link );
		}

		// Return the link.
		return esc_url( $link );
	}

}


if ( ! function_exists( 'get_yourls_term_shortlink' ) ) {
	/**
	 * Get the raw shortlink for a term.
	 *
	 * @param  integer $term_id  Current term ID.
	 * @param  boolean $echo     Whether to echo out the link or just return it.
	 *
	 * @return string            The link, or nothing.
	 */
	function get_yourls_term_shortlink( $term_id = 0, $echo = false ) {

		// Bail without a term ID.
		if ( empty( $term_id ) ) {
			return;
		}

		// Check for the link.
		if ( false === $link = YOURLSCreator_Helper::get_yourls_term_meta( $term_id ) ) {
			return;
		}

		// Echo the link if requested.
		if ( ! empty( $echo ) ) {
			echo esc_url( $link );
		}

		// Return the link.
		return esc_url( $link );
	}

}
