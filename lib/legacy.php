<?php
/**
 * YOURLS Link Creator - Legacy Module
 *
 * Holds the old functions from Ozh's plugin
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
 * Template tag: return/echo short URL with no formatting
 * just a straight copy of the original function from Ozh
 */
if ( ! function_exists( 'wp_ozh_yourls_raw_url' ) ) {

	function wp_ozh_yourls_raw_url( $echo = false ) {

		global $id;

		$short = apply_filters( 'ozh_yourls_shorturl', wp_ozh_yourls_geturl( $id ) );

		if ( $short ) {

			if ( $echo ) {
				echo $short;
			}

			return $short;
		}
	}

}

/**
 * Template tag: echo short URL for current post
 */
if ( ! function_exists( 'wp_ozh_yourls_url' ) ) {

	function wp_ozh_yourls_url() {

		global $id;

		$short = esc_url( apply_filters( 'ozh_yourls_shorturl', wp_ozh_yourls_geturl( $id ) ) );

		if ($short) {

			$rel    = esc_attr( apply_filters( 'ozh_yourls_shorturl_rel', 'nofollow alternate shorturl shortlink' ) );

			$title  = esc_attr( apply_filters( 'ozh_yourls_shorturl_title', 'Short URL' ) );

			$anchor = esc_html( apply_filters( 'ozh_yourls_shorturl_anchor', $short ) );

			echo "<a href=\"$short\" rel=\"$rel\" title=\"$title\">$anchor</a>";

		}

	}

}

/**
 * Get or create the short URL for a post.
 * Input integer (post id), output string(url)
 */
if ( ! function_exists( 'wp_ozh_yourls_geturl' ) ) {

	function wp_ozh_yourls_geturl( $id ) {

		// Hardcode this const to always poll the shortening service. Debug tests only, obviously.
		if( defined('YOURLS_ALWAYS_FRESH') && YOURLS_ALWAYS_FRESH ) {
			$short = null;
		} else {
			$short = get_post_meta( $id, 'yourls_shorturl', true );
		}

		// bypassing the fetch action from Ozh's plugin to avoid errors
		/*
		// short URL never was not created before? let's get it now!
		if ( ! $short && ! is_preview() && ! get_post_custom_values( 'yourls_fetching', $id) ) {

			// Allow plugin to define custom keyword
			$keyword = apply_filters( 'ozh_yourls_custom_keyword', '', $id );
			$short = wp_ozh_yourls_get_new_short_url( get_permalink( $id ), $id, $keyword );
		}
		*/

		return $short;
	}

}
