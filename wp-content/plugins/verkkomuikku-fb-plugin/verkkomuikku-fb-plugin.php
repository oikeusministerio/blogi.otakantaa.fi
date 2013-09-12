<?php
/*
Plugin Name: Verkkomuikku Facebook Plugin
Plugin URI: http://www.verkkomuikku.fi
Description: Include some Open Graph stuff. Requires Verkkomuikku Front end user plugin
Author: Teemu Muikku verkkomuikku@gmail.com
Version: 1.0
Author URI: http://www.verkkomuikku.fi
*/

/**
 * 
 * Opengraph protocol
 * http://ogp.me/
 * http://developers.facebook.com/docs/opengraph/
 * Opengraph sivu, asetukset hallintapaneeliin
 * 
 * Caching
 * http://developers.facebook.com/docs/reference/api/realtime/
 * http://developers.facebook.com/docs/api/realtime
 * 
 * Graph API
 * http://developers.facebook.com/docs/reference/api/
 * 
 * Stats
 * http://developers.facebook.com/docs/reference/api/#analytics
 * http://developers.facebook.com/docs/authentication/#app-login
 * 
 * Like button
 * https://developers.facebook.com/docs/reference/plugins/like/
 * 
 * Send button
 * http://developers.facebook.com/docs/reference/plugins/send/
 *
 * Facebook Wall jQuery plugin - used for displaying Facebook wall, see
 * http://www.neosmart.de/social-media/facebook-wall
 * - located in verkkomuikku-fb-plugin/jq-fb-wall
 * 
 * Post to your wall
 * http://wordpress.org/extend/plugins/wordbook/screenshots/
 * 
 * NOTE: Facebook like / share buttons to work with IE8 add
 * <html xmlns:fb="http://ogp.me/ns/fb#"...> eg. filter language_attributes
 * 
 * TODO: nag to admin pages if there are no app id and secret set
 * - if no og:image, search for best image match from the post / site
 * - nag of all missing og meta tags.
 * - better explanations of how og meta tags are outputed when Yoast SEO plugin is enabled
 * 
 * like yms statistiikka
 *  - http://developers.facebook.com/blog/post/323/
 *  - Legacy REST http://developers.facebook.com/docs/reference/rest/links.getStats/
 *  - FQL http://developers.facebook.com/docs/reference/fql/link_stat/
 *  - Graph API http://developers.facebook.com/docs/reference/api/insights/ 
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

load_plugin_textdomain("verkkomuikku", PLUGINDIR."/verkkomuikku-fb-plugin");

if (!class_exists('Verkkomuikku_Fb_Plugin')) {
	
	class Verkkomuikku_Fb_Plugin {

		var $settings;
		var $app_id;
		var $app_secret;		
		var $fb_user;
		var $fb_user_status;
		var $og_meta_ok;
		var $channel_url;
		var $facebook_script_loaded = false;
		
		public function __construct() {
			
			// First, load application ID and secret so they can be checked against.
			$this->app_id = get_option('vmfb_fb_app_id');
			$this->app_secret = get_option('vmfb_fb_app_secret');
			
			// Set channel url. Chanel url is a channel.php file that is used for cross domain communication.
			// See: http://developers.facebook.com/blog/post/530/
			$this->channel_url = plugins_url('facebook/channel.php' , dirname(__FILE__));			
			
			// Hold a flag that shows if open graph meta tags are already outputed
			$this->og_meta_ok = false;
			
			// Set attributes
			add_action('plugins_loaded', array(&$this, 'init_attributes'));
			
			// Widgets
			include_once('includes/vmfb-widgets.php');
			add_action( 'widgets_init', create_function( '', 'return register_widget("Vmfb_Wall_Widget");' ) );
			add_action( 'widgets_init', create_function( '', 'return register_widget("Vmfb_Facebook_Like_Widget");' ) );
			
			// Load facebook script, the sooner the better
			add_action('vmfeu_wp_body', array(&$this, 'load_facebook'));
			add_action('wp_footer', array(&$this, 'load_facebook'));
			add_action('admin_print_footer_scripts', array(&$this, 'load_facebook'));
			add_filter('language_attributes', array(&$this, 'facebook_xmlns'));
						
			if (is_admin()) {
				// include the menu file
				include_once('includes/vmfb-admin-settings.php');

				// register the settings for the menu only display sidebar menu for a user with a certain capability, in this case only the "admin"
				add_action('admin_init', array(&$this, 'register_settings'));
					
				// call the wppb_create_menu function
				add_action('admin_menu', array(&$this, 'create_admin_page'));
				
				
			} else {
				// Load styles
				add_action('wp_print_styles', array(&$this, 'add_plugin_stylesheet'));
			    
				// include Javascript 
			    add_action('init', array(&$this, 'add_plugin_scripts'));				
				
			    // include open graph meta tags 
			    // Do it after Yoast SEO plugin if it is enabled or in wp_head at least.
			    add_action('wpseo_opengraph', array(&$this, 'include_open_graph_meta_tags'));
				add_action('wp_head', array(&$this, 'include_open_graph_meta_tags'));
				
			}
		}			
		
		/**
		 * General init tasks 
		 * 
		 */
		public function init_attributes() {

			// Load settings
			$this->settings = apply_filters('vmfb_settings', get_option('vmfb_settings'));
										
			// Init settings
			// TODO: possibility to reset settings
			if (!$this->settings) {
				$this->settings = array();
				$this->settings['use_site_open_graph'] 			= 1;
				$this->settings['use_default_style']	 		= 1;
				
				// Update here, since the following options are in their own option rows in database
				update_option('vmfb_settings', $this->settings);
			}
			
			$this->settings['open_graph_meta_tags'] = apply_filters('vmfb_open_graph_meta_tags', get_option('vmfb_open_graph_meta_tags'));
			
			// App id not saved in vmfb_open_graph_meta_tags
			$this->settings['open_graph_meta_tags']['app_id'] = $this->app_id;
			if (!$this->settings['open_graph_meta_tags']) {
				$open_graph_meta_tags = array(
					'title' 	=> get_bloginfo('name'),
					'type'		=> 'website',
					'url'		=> home_url(),
					'image' 	=> "",
					'site_name' => get_bloginfo('name'),
					'description' => get_bloginfo('description'),
					'admins' 	=> "",
					'app_id'	=> $this->app_id,
					// Optional - location
					'latitude' 	=> '',
					'longitude' => '',
					'street_address' => '',
					'locality'	=> '',
					'region'	=> '',
					'postal-code' => '',
					'country-name' => '',
					// Optional - Contact
					'email' => get_site_option('admin_email'),
					'phone_number' => '',
					'fax_number' => '',
					'locale' => ''
				);
				update_option('vmfb_open_graph_meta_tags', $open_graph_meta_tags);
				$this->settings['open_graph_meta_tags'] = $open_graph_meta_tags;
			}
			
			// Get connected user, Facebook cookie etc.
			$this->set_facebook_user();
		}

		/**
		 * Dashboard settings page
		 */
		function create_admin_page(){
			add_submenu_page('users.php', __('Verkkomuikku FB plugin', 'verkkomuikku'), 'Verkkomuikku FB plugin', 'delete_users', 'VerkkomuikkuFbSettings', 'vmfb_settings_page');
		}
		
		/**
		 * WP admin settings register
		 * TODO: Add all settings, basic fields etc
		 */
		function register_settings() { 	// whitelist options, you can add more register_settings changing the second parameter
			
			add_settings_section('general_settings', __('General Settings', 'verkkomuikku'), array(&$this, 'general_settings_section'), 'VerkkomuikkuFbSettings');
			add_settings_field('use_default_style', __('Use plugin CSS', 'verkkomuikku'), array(&$this, 'sf_use_default_style'), 'VerkkomuikkuFbSettings', 'general_settings');

			add_settings_section('open_graph_settings', __('Open Graph Settings', 'verkkomuikku'), array(&$this, 'open_graph_settings_section'), 'VerkkomuikkuFbSettings');
			add_settings_field('use_site_open_graph', __('Include site open graph meta. This is required if you want to show Like button for your site.', 'verkkomuikku'), array(&$this, 'sf_use_site_open_graph'), 'VerkkomuikkuFbSettings', 'open_graph_settings');
			add_settings_field('vmfb_open_graph_meta_tags', __('Open Graph meta tags for your site. These are not modified by post / page, please utilize Yoast SEO plugin to generate good og meta tags.', 'verkkomuikku'), array(&$this, 'sf_open_graph_meta_tags'), 'VerkkomuikkuFbSettings', 'open_graph_settings');
			
			add_settings_section('facebook_app_settings', __('Facebook Application settings', 'verkkomuikku'), array(&$this, 'facebook_app_settings_section'), 'VerkkomuikkuFbSettings');
			add_settings_field('vmfb_fb_app_id', __('Facebook Application ID', 'verkkomuikku'), array(&$this, 'sf_app_id'), 'VerkkomuikkuFbSettings', 'facebook_app_settings');			
			add_settings_field('vmfb_fb_app_secret', __('Facebook Application secret key', 'verkkomuikku'), array(&$this, 'sf_app_secret'), 'VerkkomuikkuFbSettings', 'facebook_app_settings');
			
			register_setting( 'VerkkomuikkuFbSettings', 'vmfb_settings');
			register_setting( 'VerkkomuikkuFbSettings', 'vmfb_open_graph_meta_tags', array(&$this, 'validate_open_graph_meta_tags'));
			register_setting( 'VerkkomuikkuFbSettings', 'vmfb_fb_app_id');
			register_setting( 'VerkkomuikkuFbSettings', 'vmfb_fb_app_secret');
		}
		
		/**
		 * Display guide text for the general settings section
		 * 
		 */
		function general_settings_section() {}

		/**
		 * Display guide text for the open graph settings section
		 * 
		 */
		function open_graph_settings_section() {
			?>
			<p>
			<?php _e('Facebook Like button requires Open Graph meta tags included in site HTML <head> section. If you wan\'t to use like button for your site, enable Open Graph for the site, fill at least required meta tags and use shortcode [vmfb_like_button] or call global $vmfb->like_button() to display the like button.', 'verkkomuikku'); ?>
			<?php _e('You can enable like button for pages and posts if you like. The meta tags will get automatically modified to correspond the page or post.', 'verkkomuikku'); ?>
			</p>
			<p><?php _e("How to use OG meta tags, see: ", "verkkomuikku");?>http://developers.facebook.com/docs/opengraph/</p>
			<?php 
		}

		/**
		 * Display guide text for Facebook Application settings section
		 * 
		 */
		public function facebook_app_settings_section() {
			$message = __("To provide Facebook functionality, please provide Facebook application id and app secret", "verkkomuikku");
			return '<p>'.$message.'</p>'; 
		}
		
		/**
		 * Echo settings field: Include plugin default style sheet
		 * 
		 */
		function sf_use_default_style() {
			$use = $this->settings["use_default_style"];
			?>
				<input type="checkbox" name="vmfb_settings[use_default_style]" id="use_default_style" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}

		/**
		 * Echo settings field: Use Open Graph for the site eg. include open graph meta tags to <head>
		 * 
		 */
		function sf_use_site_open_graph() {
			$use = $this->settings["use_site_open_graph"];
			?>
				
				<input type="checkbox" name="vmfb_settings[use_site_open_graph]" id="use_site_open_graph" value="1" <?php echo checked( 1, $use, false ) ?>/>
				<p><?php _e("If you use Yoast SEO plugin, you should use open graph section from there and disable this one.", "verkkomuikku"); ?></p>
			<?php 
		}
		
		/**app_id
		 * Echo settings field: Open graph meta tags
		 * 
		 */
		function sf_open_graph_meta_tags() {
			$tags = $this->settings["open_graph_meta_tags"];
			
			// These are required by Facebook
			$mandatory_tags = array(
					'title',
					'type',
					'url',
					'image',
			);
			
			$guides = array(
				'title' => __("The title of your object as it should appear within the graph. (blog name)", "verkkomuikku"),
				'type'  => __("website, see:", "verkkomuikku")." http://developers.facebook.com/docs/opengraph/#types",
				'image' => __("Image URL. Aspect ratio: max 3:1, supported types: PNG, JPG, GIF", "verkkomuikku"),
				'url'	=> __("The canonical URL of your object that will be used as its permanent ID in the graph. (site url)", "verkkomuikku"),
				'site_name' => __("A human-readable name for your site. (blog name)", "verkkomuikku"),
				'admins' => __("A comma-separated list of either Facebook user IDs or a Facebook Platform application ID (fill into field app_id) that administers this page. It is valid to include both fb:admins and fb:app_id on your page.", "verkkomuikku"),
				'description' => __("A one to two sentence description of your page. (blog description)", "verkkomuikku"),
			);
			
			echo "<table><tbody>";
			foreach ($tags as $tag => $val) :
				
					$mandatory = in_array($tag, $mandatory_tags) ? "<strong>required</strong>" : "optional";
					$mandatory = "<small>".$mandatory."</small>";
					
					$guide = isset($guides[$tag]) ? $guides[$tag] : '';
				?>
				<tr>
				<td><label for="open_graph_meta_tags_<?php echo $tag ?>"><?php echo $tag ?></label></td>
				<td><input type="text" size="20" name="vmfb_open_graph_meta_tags[<?php echo $tag ?>]" id="vmfb_open_graph_meta_tags_<?php echo $tag ?>" value="<?php echo $val ?>"/></td>
				<td><?php echo $mandatory; ?></td>
				<td><?php echo $guide; ?></td>
				</tr>
				<?php
			endforeach;
			echo "</tbody></table>";
		}		
		
		
		/**
		 * Echo settings field: Facebook application ID
		 * 
		 */
		public function sf_app_id() {
			?>
			<input type="text" size="20" name="vmfb_fb_app_id" id="fb_app_id" value="<?php echo $this->app_id; ?>"/>
			<?php
		}

		/**
		 * Echo settings field: Facebook application secret key
		 * 
		 */
		public function sf_app_secret() {
			?>
			<input type="text" size="32" name="vmfb_fb_app_secret" id="fb_app_secret" value="<?php echo $this->app_secret; ?>"/>
			<?php
		}		
		
		/**
		 * Validate admin settings
		 * 
		 * @param array $input
		 */
		function validate_settings($input) {
			// Check our textbox option field contains no HTML tags - if so strip them out
			// $input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);
			return $input; // return validated input
		}
		
		/**
		 * Escape text in array
		 * 
		 * @param $tags
		 */
		function validate_open_graph_meta_tags($tags) {
			foreach ($tags as $key => $val) {
				$tags[$key] = esc_attr($val);
			}
			return $tags;
		}
		
		/**
		 * Include basic stylesheet for login, registration and profile forms
		 * 
		 */
		public function add_plugin_stylesheet() {
	        $styleUrl = WP_PLUGIN_URL . '/verkkomuikku-fb-plugin/css/style.css';
	        $styleFile = WP_PLUGIN_DIR . '/verkkomuikku-fb-plugin/css/style.css';
	        if ( file_exists($styleFile) && $this->settings['use_default_style']) {
	            wp_register_style('vmfb_stylesheet', $styleUrl);
	            wp_enqueue_style( 'vmfb_stylesheet' );
	        }
		}
		
		/**
		 * Add javascript 
		 * 
		 */
		public function add_plugin_scripts() {
			// Include vmfb scripts
			wp_register_script('vmfb_script',
		   		plugins_url('js/vmfb.js', __FILE__),
		       	array('jquery'),
		       	'1.0');
			wp_enqueue_script('vmfb_script');			
		}
		
		/**
		 * Init Facebook. Load it asyncronously and use cookie and custom channel url.
		 * Facebook script should be loaded only once and within <body> tag. Preferably
		 * load this immediately after <body> start tag so facebook gets loaded parallel
		 * to the actual website. You may consider this good or bar.. Anyway, just hook
		 * this function to an action that runs within <body>, for example wp_footer. 
		 * 
		 * Tries to use the Verkkomuikku-front-end-user plugin facebook utility.
		 * 
		 */
		public function load_facebook() {
			global $vmfeu_fb_connect;
			
			if ($this->facebook_script_loaded)
				return;
						
			if ($vmfeu_fb_connect) {
				$vmfeu_fb_connect->load_facebook();
				return;	
			}
			
			// Else load it here...
			
			// All Facebook functions should be included
			// in this javacsript function, or at least initiated from here
?>
<div id="fb-root"></div>
<script>(function(d, s, id) {
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) return;
	js = d.createElement(s); js.id = id;
	js.src = "//connect.facebook.net/<?php echo $this->fb_script_locale(); ?>/all.js#xfbml=1&appId=<?php echo $this->app_id; ?>";
	fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php 			
/*	Old way?
			?>
<div id="fb-root"></div>
<script type="text/javascript">
window.fbAsyncInit = function() {
	FB.init({
		appId: '<?php echo $this->app_id; ?>', 
		status: true, 
		cookie: true,
		xfbml: true,
		channelUrl: '<?php echo $this->channel_url; ?>'
	});
};
(function() {
	var e = document.createElement('script'); e.async = true;
	e.src = document.location.protocol +
	'//connect.facebook.net/<?php echo $this->fb_script_locale(); ?>/all.js';
	document.getElementById('fb-root').appendChild(e);
}());
</script>
<?php */
						
			// Set this script included so it won't get included again.
			$this->facebook_script_loaded = true;
			return;			
		}
		
		/**
		 * Return locale for Facebook script.
		 * Locales at Facebook aren't the same as we use here at WP / qTranslate
		 *
		 * @return string $fb_locale
		 */
		public function fb_script_locale() {
		
			$locale = get_locale();
			if ($locale == "fi")
				$locale = "fi_FI";
			elseif ($locale == "sv_SE")
			$locale = "sv_SE";
			else
				$locale = "en_US";
		
			return $locale;
		}		
		
		/**
		 * Fix Facebook sharing buttons not showing with IE8
		 * See: https://developers.facebook.com/docs/reference/plugins/like/
		 * 
		 * @param string $language_attributes
		 * 
		 * @return string $language_attributes
		 */
		public function facebook_xmlns($language_attributes) {
			return $language_attributes." xmlns:fb=\"http://ogp.me/ns/fb#\"";
		}
		
		/**
		 * Get Facebook userdata from Verkkomuikku Front end user plugin
		 * and save to class variable.
		 * 
		 * There is filter available so you can supply your own facebook user object if you liked.
		 * 
		 */
		public function set_facebook_user() {
			global $vmfeu_fb_connect;
			
			if ($vmfeu_fb_connect) {
				// Get Facebook user using Verkkomuikku front end user plugin Facebook Connect extension
				$fb_user = $vmfeu_fb_connect->get_facebook_userdata();
			} 

			$fb_user = apply_filters('vmfb_get_facebook_user', $fb_user);
			
			if ($fb_user) {
				$this->fb_user = $fb_user["fb_user"];
				$this->fb_user_status = $fb_user["status"];
			} else {
				$this->fb_user = null;
				$this->fb_user_status = null;
			}
				
			return;
		}
		
		/**
		 * Echo open graph meta tags into <head>
		 * This function might be called twice if you have Yoast SEO plugin
		 * enabled. Outputing og meta tags twice is avoided by using the og_meta_ok flag..
		 * 
		 */
		public function include_open_graph_meta_tags() {
			global $q_config;
			
			// Opengraph meta tags already outputed or not all conditions are met to output them
			if ($this->og_meta_ok || !$this->open_graph_meta_tags_ok())
				return;
			
			// Yoast SEO plugin does some of the tags better!
			$skip = $this->skip_open_graph_meta_tags();
				
			foreach ($this->settings['open_graph_meta_tags'] as $tag => $value) {
				// <meta property="og:type" content="website"/>
				
				// Skip some tags if necessary
				if (in_array($tag, $skip))
					continue;
					
				// og:locale, check out if qtranslate online, otherwise use wp locale
				if ($tag == 'locale') {
					$available_locales = array();
					
					if (!empty($q_config) && isset($q_config['enabled_languages'])) {
						foreach ($q_config['enabled_languages'] as $language)
							$available_locales[] = $language;
					} else {
						$available_locales[] = get_locale();
					}
					
					foreach($available_locales as $al) {
						$locale = '';
						if (substr($al, 0,2) == 'fi')
							$locale = 'fi_FI';
						elseif(substr($al,0,2) == 'en')
							$locale = 'en_US';
						elseif(substr($al,0,2) == 'sv')
							$locale = 'sv_SE';
						elseif(substr($al,0,2) == 'ru')
							$locale = 'ru_RU';
						if ($locale) {
							?><meta property="og:locale<?php echo count($available_locales > 1) ? ':alternate':''; ?>" content="<?php echo $locale; ?>"/><?php
						} 
					}
				} else {
					// Don't echo empty meta tags
					$value = trim($value);
					if (!$value)
						continue;
											
					$tag = ($tag == "app_id" || $tag == "admins") ? "fb:".$tag : "og:".$tag;  
					?><meta property="<?php echo $tag; ?>" content="<?php echo $value; ?>"/><?php
				}
			}
			
			// Set flag
			$this->og_meta_ok = true;
		}
		
		/**
		 * Let other plugins to insert og meta tags instead. 
		 * For example Yoast SEO plugin does better tags!
		 * 
		 * @return array $skip open graph meta tags that are not echoed by vmfb plugin
		 */
		public function skip_open_graph_meta_tags() {
			$skip = array();
			
			// Yoast SEO plugin can set many open graph tags. Lets see which ones it is 
			// going to output...
			if (class_exists('WPSEO_OpenGraph')) {
				
				$wpseo_options = get_option( 'wpseo_social' );
				
				// If there is checkbox "Use Opengraph meta tags" checked, Yoast SEO will
				// output og:title, og:url etc automatically based on current content.
				// See wp-content/plugins/wordpress-seo/frontend/class-opengrapgh.php
				if ( isset($wpseo_options['opengraph']) && 'on' == $wpseo_options['opengraph'] ) {
					$skip = array("title", "url", "type", "image", "description", "site_name", "email", "image");
				}

			}
			
			return apply_filters('vmfb_skip_open_graph_meta_tags', $skip);
		}

		
		/**
		 * Check if conditions required to include meta tags, to use like button etc. are ok
		 * 
		 * @return boolean $ok to use functionality that requires open graph meta tags.
		 */
		public function open_graph_meta_tags_ok() {
			// Don't include
			if (!$this->settings['use_site_open_graph'])
				return false;
				
			// Also don't include if all required are not filled
			// See required OG tags from http://ogp.me/
			/*
			$tags = $this->settings['open_graph_meta_tags'];
			if ($tags['url'] && $tags['title'] && $tags['image'] && $tags['type'])
				return true;
			else
				return false;
			*/
			return true;
		}
		
		/**
		 * Will display Facebook like button, if there are all required open graph meta tags
		 * and also like buttons enabled.
		 * 
		 * Automatically checks if we are on a post or a page. Don't include in vmfeu pages,
		 * 
		 * @param unknown_type $width
		 * @param unknown_type $height
		 */
		public function like_button($width = "", $height = "") {
			if (!$this->open_graph_meta_tags_ok())
				return;
				
			// Check if we are liking a post
			
			// Check if we are liking a page
			$url = $this->settings['open_graph_meta_tags']['url'];
			?>
			<fb:like href="<?php echo $url; ?>" <?php echo $width ? 'width="'.$width.'"' : '';?> <?php echo $height ? 'height="'.$height.'"' : '';?>/>
			<?php
		}
	}
}

$vmfb = new Verkkomuikku_Fb_Plugin();

// Facebook Wall
include_once("includes/vmfb-fb-wall.php");

// Sharing buttons
include_once("includes/vmfb-sharing.php");

?>