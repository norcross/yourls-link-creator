<?php
/**
 * Plugin Name: YOURLS Link Creator
 * Plugin URI: http://andrewnorcross.com/plugins/yourls-link-creator/
 * Description: Creates a shortlink using YOURLS and stores as postmeta.
 * Author: Andrew Norcross
 * Author http://andrewnorcross.com
 * Version: 2.1.1
 * Text Domain: wpyourls
 * Domain Path: languages
 * GitHub Plugin URI: https://github.com/norcross/yourls-link-creator
 */
/*
 * Copyright 2012 Andrew Norcross
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 */

// Set my base for the plugin.
if ( ! defined( 'YOURLS_BASE' ) ) {
	define( 'YOURLS_BASE', plugin_basename(__FILE__) );
}

// Set my directory for the plugin.
if ( ! defined( 'YOURLS_DIR' ) ) {
	define( 'YOURLS_DIR', plugin_dir_path( __FILE__ ) );
}

// Set my version for the plugin.
if ( ! defined( 'YOURLS_VER' ) ) {
	define( 'YOURLS_VER', '2.1.1' );
}

/**
 * Set up and load our class.
 */
class YOURLSCreator
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'plugins_loaded',               array( $this, 'textdomain'          )           );
		add_action( 'plugins_loaded',               array( $this, 'load_files'          )           );

		// Handle the scheduling and removal of cron jobs.
		add_action( 'plugins_loaded',               array( $this, 'schedule_crons'      )           );
		register_deactivation_hook      ( __FILE__, array( $this, 'remove_crons'        )           );
	}

	/**
	 * Load textdomain for international goodness.
	 *
	 * @return textdomain
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpyourls', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Call our files in the appropriate place.
	 *
	 * @return void
	 */
	public function load_files() {

		// Load our global.
		require_once( 'lib/global.php' );

		// Load our helper file.
		require_once( 'lib/helper.php' );

		// Load our back end.
		if ( is_admin() ) {
			require_once( 'lib/settings.php' );
			require_once( 'lib/postmeta.php' );
			require_once( 'lib/termmeta.php' );
			require_once( 'lib/ajax.php' );
		}

		// Load our front end.
		if ( ! is_admin() ) {
			require_once( 'lib/front.php' );
		}

		// Load our template tag file.
		require_once( 'lib/display.php' );

		// Load our legacy file.
		require_once( 'lib/legacy.php' );
	}

	/**
	 * Add our scheduled cron jobs.
	 *
	 * @return void
	 */
	public function schedule_crons() {

		// Optional filter to disable this all together.
		if ( false === apply_filters( 'yourls_run_cron_jobs', true ) ) {
			return;
		}

		// Schedule the click check.
		if ( ! wp_next_scheduled( 'yourls_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'yourls_cron' );
		}

		// Schedule the API ping test.
		if ( ! wp_next_scheduled( 'yourls_test' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'yourls_test' );
		}
	}

	/**
	 * Remove the cron jobs on deactivation.
	 *
	 * @return void
	 */
	public function remove_crons() {

		// Fetch the timestamps/
		$click  = wp_next_scheduled( 'yourls_cron' );
		$check  = wp_next_scheduled( 'yourls_test' );

		// Remove the jobs.
		wp_unschedule_event( $click, 'yourls_cron', array() );
		wp_unschedule_event( $check, 'yourls_test', array() );
	}

	// End the class.
}

// Instantiate our class.
$YOURLSCreator = new YOURLSCreator();
$YOURLSCreator->init();
