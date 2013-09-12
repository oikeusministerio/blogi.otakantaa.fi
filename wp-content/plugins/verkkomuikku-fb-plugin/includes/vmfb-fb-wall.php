<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Provide Facebook Wall by using FB.wall jQuery extension.
 * See: http://www.neosmart.de/social-media/facebook-wall
 * 
 * This file should be included from Verkkomuikku FB plugin and run before 
 * init action so that scripts and css get included.
 * 
 */
if (!class_exists('Vmfb_Wall')) {
	class Vmfb_Wall {
		
		var $settings;
		
		/**
		 * Constructor
		 * 
		 */
		public function __construct() {
			$this->init();
		}
		
		/**
		 * Init function
		 * 
		 */
		public function init() {
			$this->init_attributes();
			
			// Check if user didn't specify access_token for the wall and
			// use site application access_token instead.
			add_action('plugins_loaded', array(&$this, 'use_app_access_token'));
			
			// Include required javascript
			add_action('init', array(&$this, 'add_plugin_scripts'));

			// include the standard style-sheet or specify the path to a new one
		    add_action('wp_print_styles', array(&$this, 'add_plugin_stylesheet'));			
			
		    // Register dashboard settings
			add_action('admin_init', array(&$this, 'register_settings'));
		}

		/**
		 * General init tasks 
		 * 
		 */
		public function init_attributes() {

			// Load settings
			$this->settings = apply_filters('vmfb_wall_settings', get_option('vmfb_wall_settings'));
										
			// Init settings
			// TODO: possibility to reset settings
			if (!$this->settings) {
				$this->settings = array(
					"avatarAlternative"		=>	'avatar-alternative.jpg',
					"avatarExternal"		=>	'avatar-external.jpg',
					"id"					=> 	'',
					"max"					=>	5,
					"showComments" 			=>	true,
					"showGuestEntries"		=>	true,
					"translateAt" 			=>	__('at', 'verkkomuikku'),
					"translateLikeThis" 	=>	__('like this', 'verkkomuikku'),
					"translateLikesThis" 	=>	__('likes this', 'verkkomuikku'),
					"translateErrorNoData" 	=>	__('has not shared any information.', 'verkkomuikku'),
					"translatePeople" 		=>	__('people', 'verkkomuikku'),
					"timeConversion" 		=>	24, // 12 | 24
					"useAvatarAlternative" 	=>	false,
					"useAvatarExternal" 	=>	false,
					"accessToken" 			=>	''				
				);
				
				// Update here, since the following options are in their own option rows in database
				update_option('vmfb_wall_settings', $this->settings);
			}
		}
		
		/**
		 * If there is no user specified access_token, try to use
		 * application access token.
		 * 
		 * Loaded after plugins loaded, since get_app_access_token tries
		 * to use Vmfeu FB Connect plugin.
		 * 
		 */
		public function use_app_access_token() {
			
			// Check that user specified access token is empty
			if (!trim($this->settings["accessToken"])) {
				
				// Get application access token
				$this->settings["accessToken"] = $this->get_app_access_token();
			}
			
			return;			
		}		
		
		/**
		 * Get application access token. This access token can be
		 * used to fetch info from public groups etc. This
		 * access_token is used if user didn't specify their own from dashboard
		 * settings.
		 * 
		 * @uses Vmfeu_Fb_Connect extension from Verkkomuikku Front End User plugin
		 * 
		 * @return string $access_token
		 * 
		 */
		public function get_app_access_token() {
			global $vmfeu_fb_connect;
			
			$access_token = null;
			
			// Utilize vmfeu_fb_connect, it should already have the access_token
			if ($vmfeu_fb_connect) {
				$access_token = $vmfeu_fb_connect->get_app_access_token();
			}
			
			return apply_filters('vmfb_wall_app_access_token', $access_token);
		}
		
		/**
		 * Add javascript 
		 * 
		 */
		public function add_plugin_scripts() {
			// Include vmfb scripts
			wp_register_script('jstorage',
		   		plugins_url('jq-fb-wall/jstorage.js', __FILE__),
		       	array('jquery'),
		       	'1.0');
			wp_enqueue_script('jstorage');	
						
			wp_register_script('vmfb_wall_script',
		   		plugins_url('jq-fb-wall/jquery.neosmart.fb.wall.js', __FILE__),
		       	array('jquery', 'jstorage'),
		       	'1.0');
			wp_enqueue_script('vmfb_wall_script');			
			
		}

		/**
		 * Include basic stylesheet
		 * 
		 */
		public function add_plugin_stylesheet() {
	        $styleUrl = WP_PLUGIN_URL . '/verkkomuikku-fb-plugin/includes/jq-fb-wall/jquery.neosmart.fb.wall.css';
	        $styleFile = WP_PLUGIN_DIR . '/verkkomuikku-fb-plugin/includes/jq-fb-wall/jquery.neosmart.fb.wall.css';
	        if ( file_exists($styleFile) ) {
	            wp_register_style('vmfb_wall_stylesheet', $styleUrl);
	            wp_enqueue_style( 'vmfb_wall_stylesheet' );
	        }
		}		
				
		/**
		 * Register dahsboard settings. Utilizes Verkkomuikku FB plugin settings page
		 * 
		 * Settings from jQuery FB wall extension:
		 * avatarAlternative:		'avatar-alternative.jpg',
		 * avatarExternal:			'avatar-external.jpg',
		 * id: 						'neosmart.gmbh',
		 * max:						5,
		 * showComments:			true,
		 * showGuestEntries:		true,
		 * translateAt:				'at',
		 * translateLikeThis:		'like this',
		 * translateLikesThis:		'likes this',
		 * translateErrorNoData:	'has not shared any information.',
		 * translatePeople:			'people',
		 * timeConversion:			24,
		 * useAvatarAlternative:	false,
		 * useAvatarExternal:		false,
		 * accessToken:				''
		 * 
		 */
		public function register_settings() {
			add_settings_section('facebook_wall_settings', __('Facebook Wall settings', 'verkkomuikku'), array(&$this, 'facebook_wall_settings_section'), 'VerkkomuikkuFbSettings');
			add_settings_field('id', __('Facebook Object ID (group, user...)', 'verkkomuikku'), array(&$this, 'sf_fb_wall_id'), 'VerkkomuikkuFbSettings', 'facebook_wall_settings');			
			add_settings_field('accessToken', __('Facebook Object Access Token', 'verkkomuikku'), array(&$this, 'sf_fb_wall_access_token'), 'VerkkomuikkuFbSettings', 'facebook_wall_settings');
			add_settings_field('max', __('How many to display?', 'verkkomuikku'), array(&$this, 'sf_fb_wall_max'), 'VerkkomuikkuFbSettings', 'facebook_wall_settings');			
			add_settings_field('showComments', __('Show comments', 'verkkomuikku'), array(&$this, 'sf_fb_wall_show_comments'), 'VerkkomuikkuFbSettings', 'facebook_wall_settings');
			add_settings_field('showGuestEntries', __('Show guest entries', 'verkkomuikku'), array(&$this, 'sf_fb_wall_show_guest_entries'), 'VerkkomuikkuFbSettings', 'facebook_wall_settings');
			
			register_setting( 'VerkkomuikkuFbSettings', 'vmfb_wall_settings');
		}
		
		/**
		 * Display text for settings section
		 * 
		 */
		public function facebook_wall_settings_section () {
			?>
				<p><?php _e("To show Facebook Wall in your site, please fill following options and use Facebook Wall Widget or call function vmfb_wall()", "verkkomuikku")?></p>
			<?php 
		}
		
		/**
		 * Echo settings field: Facebook object ID (group, user....)
		 * 
		 */
		public function sf_fb_wall_id() {
			$val = $this->settings["id"];
			?>
			<input type="text" size="20" name="vmfb_wall_settings[id]" id="fb_wall_id" value="<?php echo $val; ?>"/>
			<?php
		}

		/**
		 * Echo settings field: Facebook object access token
		 * 
		 */
		public function sf_fb_wall_access_token() {
			$val = $this->settings["accessToken"];

			// Show empty field if the token is app access token.
			// This class uses app access token if user has NOT inputted
			// own access token here. If we let app access token to
			// be submited via this form we'll break teh system ;)
			if ($val == $this->get_app_access_token())
				$val = "";
			?>
			<input type="text" size="32" name="vmfb_wall_settings[accessToken]" id="fb_wall_access_token" value="<?php echo $val; ?>"/>
			<p><?php _e("Site app access token is used, unless you specify an access token here. Open groups / pages wall can be displayed with site app access token. If you wan't to show something private, please provide access_token here!", "verkkomuikku")?></p>
			<?php
		}

		/**
		 * Echo settings field: How many to display
		 * 
		 */
		public function sf_fb_wall_max() {
			$val = $this->settings["max"];
			?>
			<select name="vmfb_wall_settings[max]" id="sf_fb_wall_max"/>
			<?php for ($i = 1; $i < 11; $i++):?>
				<option value="<?php echo $i?>" <?php echo $i == $val ? ' selected="selected"' : ''; ?>><?php echo $i?></option>
			<?php endfor; ?>
			</select>
			<?php
		}
		
		/**
		 * Echo settings field: Show comments
		 * 
		 */
		public function sf_fb_wall_show_comments() {
			$val = $this->settings["showComments"];
			?>
			<input type="checkbox" name="vmfb_wall_settings[showComments]" id="sf_fb_wall_show_comments" value="1" <?php echo $val ? ' checked="checked"' : '';?>/>
			<?php
		}

		/**
		 * Echo settings field: Show comments
		 * 
		 */
		public function sf_fb_wall_show_guest_entries() {
			$val = $this->settings["showGuestEntries"];
			?>
			<input type="checkbox" name="vmfb_wall_settings[showGuestEntries]" id="sf_fb_wall_show_guest_entries" value="1" <?php echo $val ? ' checked="checked"' : '';?>/>
			<?php
		}			

		/**
		 * Simple check that required settings are filled
		 * 
		 */
		public function settings_ok() {
			
			if ($this->settings["id"] && $this->settings['accessToken']) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Show Facebook wall
		 * 
		 * @param array $settings You may override default settings
		 * @param string $html_id optional HTML ID if you want to add several walls on a page 
		 * @return string $html
		 */
		public function get_wall($settings = "", $html_id = "vmfb_wall_1") {
			global $vmfeu_fb_connect;
			
			$settings = wp_parse_args($settings, $this->settings);

			$html = "";
			
			ob_start();
			
			?>
			<div class="vmfb_wall_wrapper">
				<div id="<?php echo $html_id; ?>"></div>
				<?php // IE 7 doesn't display the wall properly... See themes ie7.css file ?>
				<!--[if IE 7]>
				<p><?php _e("Your browser (IE 7) doesn't support this functionality. Please update your browser or switch to Chrome or FireFox.", "verkkomuikku") ?></p>
				<![endif]-->
				<script type="text/javascript">
					//<![CDATA[
					if (!jQuery.browser.msie || jQuery.browser.version != "7.0") {
						jQuery(function(){
							jQuery('#<?php echo $html_id; ?>').fbWall({<?php
								 foreach ($settings as $setting => $val) {
								 	if ($setting == "showComments")
								 		echo "showComments: true,";
								 	elseif ($setting == "showGuestEntries")
								 		echo "showGuestEntries: true,";
									elseif (is_numeric($val))
										echo $setting.": ".$val.",";
									else
										echo $setting.": '".$val."',";
								} 
								
								// Checkboxes, if not checked, werent on the loop...
								if (!isset($settings["showComments"]))
									echo "showComments: false,";
								if (!isset($settings["showGuestEntries"]))
									echo "showGuestEntries: false,";
								
							?>});
						});
					}
					//]]>
				</script>
			</div>
			<?php 
			$html = ob_get_contents();
			ob_end_clean();
			
			return $html;
		}
	}
}

$vmfb_wall = new Vmfb_Wall();

/**
 * Template function to display Facebook wall
 * First checks that there are required settings filled..
 * 
 * @param boolean $echo
 * @return string $html
 */
function vmfb_show_wall($echo = true) {
	global $vmfb_wall;
	
	if (!$vmfb_wall->settings_ok()) {
		if (current_user_can('edit_users'))
			echo __("Please edit Facebook Wall widget settings", "verkkomuikku");
		return false;
	}
	
	$html = $vmfb_wall->get_wall();
	
	if ($echo)
		echo $html;

	return $html;
}
?>