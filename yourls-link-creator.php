<?php 
/* 
Plugin Name: YOURLS Link Creator
Plugin URI: http://andrewnorcross.com/plugins/
Description: Creates a shortlink using YOURLS and stores as postmeta.
Version: 1.0
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 

// Start up the engine 
class YOURSCreator
{
	
	/**
	 * This is our constructor
	 *
	 * @return YOURSCreator
	 */
	public function __construct() {
		add_action		( 'admin_enqueue_scripts',		array( $this, 'scripts_styles'		), 10		);
		add_action		( 'admin_menu',					array( $this, 'yourls_settings'		) 			);
		add_action		( 'admin_init', 				array( $this, 'reg_settings'		) 			);
		add_action		( 'do_meta_boxes',				array( $this, 'metabox_yourls'		), 10,	2	);
		add_action		( 'wp_ajax_create_yourls',		array( $this, 'create_yourls'		)			);
		add_action		( 'wp_ajax_stats_yourls',		array( $this, 'stats_yourls'		)			);
		add_action		( 'wp_ajax_clicks_yourls',		array( $this, 'clicks_yourls'		)			);
		add_action		( 'manage_posts_custom_column',	array( $this, 'display_columns'		), 10,	2	);
		add_action		( 'yourls_cron',				array( $this, 'yourls_click_cron'	)			);
		add_filter		( 'manage_posts_columns',		array( $this, 'register_columns'	)			);
		add_filter		( 'get_shortlink',				array( $this, 'yourls_shortlink'	), 10,	3	);
		add_filter		( 'plugin_action_links',		array( $this, 'quick_link'			), 10,	2	);

		register_activation_hook			( __FILE__, array( $this, 'schedule_cron'		)			);		
		register_deactivation_hook			( __FILE__, array( $this, 'remove_cron'			)			);
	}


	/**
	 * Scripts and stylesheets
	 *
	 * @return YOURSCreator
	 */

	public function scripts_styles($hook) {
		
		$current_screen = get_current_screen();
		if ( 'settings_page_yourls-settings' == $current_screen->base ) {
			wp_enqueue_style( 'yourls-admin', plugins_url('/lib/css/yourls-admin.css', __FILE__), array(), null, 'all' );
			wp_enqueue_script( 'yourls-ajax', plugins_url('/lib/js/yourls.ajax.js', __FILE__) , array('jquery'), null, true );
		}

		if ( $hook == 'edit.php' ) {
			wp_enqueue_style( 'yourls-admin', plugins_url('/lib/css/yourls-admin.css', __FILE__), array(), null, 'all' );
		}

		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			wp_enqueue_style( 'yourls-admin', plugins_url('/lib/css/yourls-admin.css', __FILE__), array(), null, 'all' );
			wp_enqueue_script( 'yourls-ajax', plugins_url('/lib/js/yourls.ajax.js', __FILE__) , array('jquery'), null, true );
		}

	}


	/**
	 * show settings link on plugins page
	 *
	 * @return YOURSCreator
	 */

    public function quick_link( $links, $file ) {

		static $this_plugin;
		
		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}
 
    	// check to make sure we are on the correct plugin
    	if ($file == $this_plugin) {
        	
			$settings_link	= '<a href="'.menu_page_url( 'yourls-settings', 0 ).'">Settings</a>';
        
        	array_unshift($links, $settings_link);
    	}
 
		return $links;

	}	

	/**
	 * register and display columns
	 *
	 * @since 1.0
	 */


	public function register_columns( $columns ) {

		$current_screen = get_current_screen();

		$yourls_options = get_option('yourls_options');

		$args = array(
			'public'	=> true,
			'_builtin'	=> false
		); 

		$customs	= get_post_types($args);
		$builtin	= array('post' => 'post');

		$types		= isset($yourls_options['cpt'])	? array_merge($customs, $builtin) : $builtin;
		$screen		= $current_screen->post_type;

		if ( !in_array( $screen,  $types ) )
			return $columns;

		$icon = '<img src="'.plugins_url('/lib/img/yourls-click.png', __FILE__).'" alt="Clicked" title="Clicked">';
		$columns['yourls-click'] = $icon;
	 
		return $columns;
	}

	public function display_columns( $column_name, $post_id ) {

		$current_screen = get_current_screen();

		$yourls_options = get_option('yourls_options');

		$args = array(
			'public'	=> true,
			'_builtin'	=> false
		); 

		$customs	= get_post_types($args);
		$builtin	= array('post' => 'post');

		$types		= isset($yourls_options['cpt'])	? array_merge($customs, $builtin) : $builtin;
		$screen		= $current_screen->post_type;

		if ( !in_array( $screen,  $types ) )
			return;

		if ( 'yourls-click' != $column_name )
			return;
	 	
		$count	= get_post_meta($post_id, '_yourls_clicks', true);	
		
		$clicks	= empty( $count ) ? '0' : $count;

		echo '<span>'.$clicks.'</span>';

	}

	/**
	 * Create shortlink function. Called on ajax
	 *
	 * @return YOURSCreator
	 */

	public function create_yourls (){

		// get the post ID first
		$postID		= $_POST['postID'];
		$custom_kw	= (isset($_POST['keyword']) && $_POST['keyword'] !== 'none' ? $_POST['keyword'] : '');
		
		// only fire when settings have been filled out
		$yourls_options = get_option('yourls_options');

		if(	empty($yourls_options['api']) || empty($yourls_options['url']) )
			return;

		if (!current_user_can('edit_post', $postID))
			return;

		// check for existing YOURLS
		$yourls_exist = get_post_meta($postID, '_yourls_url', true);	
						
		// go get us a swanky new short URL if we dont have one
		if(empty($yourls_exist) ) {
			$clean_url	= str_replace('http://', '', $yourls_options['url']);
	
			$yourls		= 'http://'.$clean_url.'/yourls-api.php';
			$api_key	= $yourls_options['api'];
			$action		= 'shorturl';
			$format		= 'JSON';
			$keyword	= $custom_kw;
			$post_url	= get_permalink($postID);
	
			$yourls_r	= $yourls.'?signature='.$api_key.'&action='.$action.'&url='.$post_url.'&format='.$format.'&keyword='.$keyword.'';
			
			$response	= wp_remote_get( $yourls_r );

			$ret = array();

			if( is_wp_error( $response ) ) {
				// we don't want to interfere with the save process. store message and return
				$ret['success']	= false;
				$ret['message'] = 'There was an error contacting the YOURLS API. Please try again.';
				$ret['error']	= 'There was an error contacting the YOURLS API. Please try again.';
				$ret['errcode']	= 'NOCONNECT';
				$ret['process'] = 'remote post failed';
				echo json_encode($ret);
				die();

			} else {
				$data		= $response['body'];
			}

			if(!$data) {
				$ret['success'] = false;
				$ret['error']	= 'Unknown error';
				$ret['errcode']	= 'NORETURN';
				echo json_encode($ret);
				die();
			}

			if($data){
				$ret['success'] = true;
				$ret['message'] = 'You have created a new YOURLS link';
				$ret['link']	= esc_url($data);
				update_post_meta($postID, '_yourls_url', $data);
				echo json_encode($ret);
				die();
			}

		}
		
	}

	/**
	 * retireve stats
	 *
	 * @return YOURSCreator
	 */

	public function stats_yourls() {

		$yourls_options = get_option('yourls_options');
		
		if(	empty($yourls_options['api']) || empty($yourls_options['url']) )
			return;

		$postID		= $_POST['postID'];

		$yourls_url = get_post_meta($postID, '_yourls_url', true);

		// gimme some click data honey
		if(!empty($yourls_url) ) {
			$clean_url	= str_replace('http://', '', $yourls_options['url']);
	
			$yourls		= 'http://'.$clean_url.'/yourls-api.php';
			$api_key	= $yourls_options['api'];
			$action		= 'url-stats';
			$format		= 'json';
			$shorturl	= $yourls_url;
	
			$yourls_r	= $yourls.'?signature='.$api_key.'&action='.$action.'&shorturl='.$shorturl.'&format='.$format.'';
			
			$response	= wp_remote_get( $yourls_r );

			$ret = array();

			if( is_wp_error( $response ) ) {
				// we don't want to interfere with the save process. store message and return
				$ret['success']	= false;
				$ret['message'] = 'There was an error contacting the YOURLS API. Please try again.';
				$ret['error']	= 'There was an error contacting the YOURLS API. Please try again.';
				$ret['errcode']	= 'NOCONNECT';
				$ret['process'] = 'remote post failed';
				echo json_encode($ret);
				die();

			} else {
				$raw_data	= $response['body'];
				$data		= json_decode($raw_data);
			}

			if(!$data) {
				$ret['success'] = false;
				$ret['error']	= 'Unknown error';
				$ret['errcode']	= 'NORETURN';
				echo json_encode($ret);
				die();
			}

			if($data){
				$linkdata	= $data->link;
				$clicks		= $linkdata->clicks;

				$ret['success'] = true;
				$ret['message'] = 'Your shortlink has been clicked '.$clicks.' times.';
				$ret['clicks']	= $clicks;
				update_post_meta($postID, '_yourls_clicks', $clicks);
				echo json_encode($ret);
				die();
			}

		}

	}

	/**
	 * run update job to get click counts via manual ajax
	 *
	 * @return YOURSCreator
	 */

	public function clicks_yourls() {

		$yourls_options = get_option('yourls_options');
		
		if(	empty($yourls_options['api']) || empty($yourls_options['url']) )
			return;

		$args = array (
			'fields'		=> 'ids',
			'post_type'		=> 'any',
			'numberposts'	=> -1,
			'meta_key'		=> '_yourls_url',
			);		

		$yourls_posts = get_posts( $args );
		$yourls_count = (count($yourls_posts) > 0 ) ? true : false;

		$ret = array();

		if($yourls_count == false) {
			$ret['success'] = false;
			$ret['error']	= 'No posts to check.';
			$ret['errcode']	= 'NOPOSTS';
			echo json_encode($ret);
			die();
		}

		foreach ($yourls_posts as $post) : setup_postdata($post);

			$yourls_url = get_post_meta($post, '_yourls_url', true);
			$clean_url	= str_replace('http://', '', $yourls_options['url']);
			$yourls		= 'http://'.$clean_url.'/yourls-api.php';
			$api_key	= $yourls_options['api'];
			$action		= 'url-stats';
			$format		= 'json';
			$shorturl	= $yourls_url;

			$yourls_r	= $yourls.'?signature='.$api_key.'&action='.$action.'&shorturl='.$shorturl.'&format='.$format.'';
			
			$response	= wp_remote_get( $yourls_r );

			if( is_wp_error( $response ) ) {
				$ret['success'] = false;
				$ret['error']	= 'Could not connect to the YOURLS server.';
				$ret['errcode']	= 'APIERROR';
				echo json_encode($ret);
				die();
			}

			$raw_data	= $response['body'];
			$data		= json_decode($raw_data);

			if($data){
				$linkdata	= $data->link;
				$clicks		= $linkdata->clicks;
				update_post_meta($post, '_yourls_clicks', $clicks);
			}
				
		endforeach;

			$ret['success'] = true;
			$ret['message']	= 'Data has been updated.';
			echo json_encode($ret);
			die();

	}

	/**
	 * scheduling for YOURLS cron jobs
	 *
	 * @return YOURSCreator
	 */

	public function schedule_cron() {
		if ( !wp_next_scheduled( 'yourls_cron' ) ) {
			wp_schedule_event(time(), 'hourly', 'yourls_cron');
		}
	}

	public function remove_cron() {
		$timestamp = wp_next_scheduled( 'yourls_cron' );
		wp_unschedule_event($timestamp, 'yourls_cron' );
	}

	/**
	 * run update job to get click counts via cron
	 *
	 * @return YOURSCreator
	 */

	public function yourls_click_cron() {

		$yourls_options = get_option('yourls_options');
		
		if(	empty($yourls_options['api']) || empty($yourls_options['url']) )
			return;

		$args = array (
			'fields'		=> 'ids',
			'post_type'		=> 'any',
			'numberposts'	=> -1,
			'meta_key'		=> '_yourls_url',
			);		

		$yourls_posts = get_posts( $args );
		$yourls_count = (count($yourls_posts) > 0 ) ? true : false;

		if($yourls_count == false)
			return;

		foreach ($yourls_posts as $post) : setup_postdata($post);

			$yourls_url = get_post_meta($post, '_yourls_url', true);
			$clean_url	= str_replace('http://', '', $yourls_options['url']);
			$yourls		= 'http://'.$clean_url.'/yourls-api.php';
			$api_key	= $yourls_options['api'];
			$action		= 'url-stats';
			$format		= 'json';
			$shorturl	= $yourls_url;

			$yourls_r	= $yourls.'?signature='.$api_key.'&action='.$action.'&shorturl='.$shorturl.'&format='.$format.'';
			
			$response	= wp_remote_get( $yourls_r );

			if( is_wp_error( $response ) )
				return;

			$raw_data	= $response['body'];
			$data		= json_decode($raw_data);

			if($data){
				$linkdata	= $data->link;
				$clicks		= $linkdata->clicks;
				update_post_meta($post, '_yourls_clicks', $clicks);
			}
				
		endforeach;

	}

	/**
	 * build out settings page and meta boxes
	 *
	 * @return YOURSCreator
	 */

	public function yourls_settings() {
	    add_submenu_page('options-general.php', 'YOURLS Settings', 'YOURLS Settings', 'manage_options', 'yourls-settings', array( $this, 'yourls_settings_display' ));
	}

	/**
	 * display metabox
	 *
	 * @return YOURSCreator
	 */

	public function metabox_yourls( $page, $context ) {
		$yourls_options = get_option('yourls_options');
		
		if(	empty($yourls_options['api']) || empty($yourls_options['url']) )
			return;

		$args = array(
			'public'	=> true,
			'_builtin'	=> false
		); 

		$customs	= get_post_types($args);
		$builtin	= array('post' => 'post');	
		
		$types		= isset($yourls_options['cpt'])	? array_merge($customs, $builtin) : $builtin;
    	
		if ( in_array( $page,  $types ) && 'side' == $context )
			add_meta_box('yours-post-display', __('YOURLS Shortlink'), array(&$this, 'yours_post_display'), $page, $context, 'high');
	}


	/**
	 * Register settings
	 *
	 * @return YOURSCreator
	 */


	public function reg_settings() {
		register_setting( 'yourls_options', 'yourls_options');
	}

		
	/**
	 * Display YOURLS shortlink if present
	 *
	 * @return YOURSCreator
	 */

	public function yours_post_display() {
	
		global $post;
		$yourls_link	= get_post_meta($post->ID, '_yourls_url', true);
		$yourls_clicks	= get_post_meta($post->ID, '_yourls_clicks', true);
		
		$click_count	= empty($yourls_clicks) ? 'no clicks' : ($yourls_clicks > 1 ? $yourls_clicks .' clicks' : '1 click');

		if(!empty($yourls_link)) {

			echo '<p class="yourls-exist-block">';            
			echo '<input id="yourls_link" size="28" title="click to highlight" class="yourls-link widefat" type="text" name="yourls_link" value="'.$yourls_link.'" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';
			echo '</p>';

			echo '<p class="howto">' . __('Your YOURLS link has generated '.$click_count.'.') . '</p>';
		
		}

		if(empty($yourls_link)) {

			echo '<p class="yourls-create-block">';
			echo '<input id="yourls_keyw" class="yourls-keyw" size="20" type="text" name="yourls_keyw" value="" tabindex="501" />';
			echo '<img class="ajax-loading btn-yourls" src="'.plugins_url('/lib/img/wpspin-light.gif', __FILE__).'">';
			echo '<input type="button" class="button-secondary yourls-api" id="yourls_get" type="text" name="yourls_get" value="Get YOURLS" tabindex="502" />';
			echo '<span class="howto">' . __('optional keyword') . '</span>';
			echo '</p>';
			
		}
	}

	/**
	 * Filter wp_shortlink with new YOURLS link
	 *
	 * @return YOURSCreator
	 */

	public function yourls_shortlink($shortlink, $id, $context) {

		// no shortlinks exist on non-singular items, so bail
		if (!is_singular() )
			return;

		$yourls_options = get_option('yourls_options');
		
		// Look for the post ID passed by wp_get_shortlink() first
		if ( empty( $id ) ) {
			global $post;
			$id = $post->ID;
		}

		// Fall back in case we still don't have a post ID
		if ( empty( $id ) ) {
		
			if ( ! empty( $shortlink ) )
				return $shortlink;

			return false;
		}

		if(	empty($yourls_options['api']) || empty($yourls_options['url']) )
			return $shortlink;

		if(	!isset($yourls_options['sht']) )
			return $shortlink;
		
		$yourls_exist = get_post_meta($id, '_yourls_url', true);

		if ( !empty($yourls_exist))
			$shortlink = get_post_meta( $id, '_yourls_url', true );

		return $shortlink;
	}

	/**
	 * Display main options page structure
	 *
	 * @return YOURSCreator
	 */
	 
	public function yourls_settings_display() {
		if (!current_user_can('manage_options') )
			return;
		?>
	
		<div class="wrap">
    	<div class="icon32" id="icon-yourls"><br></div>
		<h2><?php _e('YOURLS Link Creator Settings') ?></h2>
        
        <div id="poststuff" class="metabox-holder has-right-sidebar">
		<?php
		echo $this->settings_side();
		echo $this->settings_open();
		?>

           	<div class="yourls-form-text">
           	<p><?php _e('This block of text will eventually have an explanation of what it does.') ?></p>
            </div>
                
            <div class="yourls-form-options">
	            <form method="post" action="options.php">
			    <?php
                settings_fields( 'yourls_options' );
				$yourls_options	= get_option('yourls_options');

				$yourls_url		= (isset($yourls_options['url'])	? $yourls_options['url']	: ''		);
				$yourls_api		= (isset($yourls_options['api'])	? $yourls_options['api']	: ''		);
				$yourls_cpt		= (isset($yourls_options['cpt'])	? $yourls_options['cpt']	: 'false'	);
				$yourls_sht		= (isset($yourls_options['sht'])	? $yourls_options['sht']	: 'false'	);
				?>

                <table class="form-table yours-table">
				<tbody>
                	<tr>
                        <th><label for="yourls_options[url]"><?php _e('YOURLS Custom URL') ?></label></th>
                        <td>
						<input type="text" class="regular-text" value="<?php echo $yourls_url; ?>" id="yourls_url" name="yourls_options[url]">
                        <p class="description"><?php _e('Actual URL only. Omit the http://') ?></p>
						</td>
                    </tr>

                	<tr>
                        <th><label for="yourls_options[api]"><?php _e('YOURLS API Signature Key') ?></label></th>
                        <td>
						<input type="text" class="regular-text" value="<?php echo $yourls_api; ?>" id="yourls_api" name="yourls_options[api]">
                        <p class="description"><?php _e('Found in the tools section on your YOURLS admin page.') ?></p>
						</td>
                    </tr>

                	<tr>
                        <th><label for="yourls_options[cpt]"><?php _e('Display on Custom Post Types') ?></label></th>
                        <td>
						<input type="checkbox" name="yourls_options[cpt]" id="yourls_cpt" value="true" <?php checked( $yourls_cpt, 'true' ); ?> />
                        <span class="description"><?php _e('Display the YOURLS creator on public custom post types') ?></span>
						</td>
                    </tr>

                	<tr>
                        <th><label for="yourls_options[sht]"><?php _e('Use YOURLS for Shortlink') ?></label></th>
                        <td>
						<input type="checkbox" name="yourls_options[sht]" id="yourls_sht" value="true" <?php checked( $yourls_sht, 'true' ); ?> />
                        <span class="description"><?php _e('Use the YOURLS link wherever wp_shortlink is fired') ?></span>
						</td>
                    </tr>

				</tbody>
                </table>        
    
	    		<p><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
				</form>

			</div>

	<?php echo $this->settings_close(); ?>

	</div>
	</div>     
	
	<?php }

    /**
     * Some extra stuff for the settings page
     *
     * this is just to keep the area cleaner 
     *
     * @return YOURSCreator
     */

    public function settings_side() { ?>

		<div id="side-info-column" class="inner-sidebar">
			<div class="meta-box-sortables">
				<div id="yourls-admin-about" class="postbox yours-sidebox">
					<h3 class="hndle" id="about-sidebar"><?php _e('About the Plugin') ?></h3>
					<div class="inside">
						<p>Talk to <a href="http://twitter.com/norcross" target="_blank">@norcross</a> on twitter or visit the <a href="http://wordpress.org/support/plugin//" target="_blank">plugin support form</a> for bugs or feature requests.</p>
						<p><?php _e('<strong>Enjoy the plugin?</strong>') ?><br />
						<a href="http://twitter.com/?status=I'm using @norcross's YOURLS AJAX plugin - check it out! http://l.norc.co/yajax/" target="_blank"><?php _e('Tweet about it') ?></a> <?php _e('and consider donating.') ?></p>
						<p><?php _e('<strong>Donate:</strong> A lot of hard work goes into building plugins - support your open source developers. Include your twitter username and I\'ll send you a shout out for your generosity. Thank you!') ?><br />
						<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="11085100">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
						</form></p>
					</div>
				</div>
			</div>

			<div class="meta-box-sortables">
				<div id="yourls-data-refresh" class="postbox yours-sidebox">
					<h3 class="hndle" id="data-sidebar"><?php _e('Data Options:') ?></h3>
					<div class="inside">
						<p>Click the button below to refresh the click count data for all posts with a YOURLS link.</p>
						<input type="button" class="yours-click-updates button-secondary" value="Refresh Click Counts" >
						<img class="ajax-loading btn-yourls" src="<?php echo plugins_url('/lib/img/wpspin-light.gif', __FILE__); ?>" >

<!--					the YOURLS API doesn't support a way just check for a URL or not.
						<hr />
						<p>Click the button below to check for existing YOURLS data for your site content.</p>
						<input type="button" class="yours-import button-secondary" value="Import YOURLS data" >
-->						
					</div>
				</div>
			</div>
			
			<div class="meta-box-sortables">
				<div id="yourls-admin-more" class="postbox yours-sidebox">
					<h3 class="hndle" id="links-sidebar"><?php _e('Links:') ?></h3>
					<div class="inside">
						<ul>
						<li><a href="http://wordpress.org/extend/plugins//" target="_blank">Plugin on WP.org</a></li>
						<li><a href="https://github.com/norcross/r" target="_blank">Plugin on GitHub</a></li>
						<li><a href="http://wordpress.org/support/plugin/" target="_blank">Support Forum</a><li>
            			</ul>
					</div>
				</div>
			</div>

		</div> <!-- // #side-info-column .inner-sidebar -->

    <?php }

	public function settings_open() { ?>

		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">
				<div id="normal-sortables" class="meta-box-sortables">
					<div id="about" class="postbox">
						<div class="inside">

    <?php }

	public function settings_close() { ?>

						<br class="clear" />
						</div>
					</div>
				</div>
			</div>
		</div>

    <?php }	

/// end class
}


// Instantiate our class
$YOURSCreator = new YOURSCreator();
