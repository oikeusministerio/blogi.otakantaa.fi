<?php
/**
 * Verkkomuikku Front end user Facebook Connect integration
 * Note: Javascript authentication used here.
 * Facebook PHP SDK requires PHP cURL extension, which is not propably available on most web hosts.
 * 
 * TODO:
 * - Tee profiiliin kahvat, että saapiko kuvan näyttää, sekä kahva että saapiko olla "Connect with me in Facebook", eli linkki omaan profiiliin.
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Use Facebook Javascript SDK to provide login / registration
 * Facebook PHP SDK requires cURL PHP extension... Don't rely on that.
 * 
 * This add on is inspired by WP Facebook Connect http://wordpress.org/extend/plugins/wp-facebook-connect/
 * 
 * @uses Facebook Javascript SDK
 * @author teemu
 *
 */
if (!class_exists('Vmfeu_Fb_Connect')) {
	class Vmfeu_Fb_Connect {
		
		var $app_id;
		var $app_secret;
		var $app_access_token;
		var $channel_url;
		var $facebook_connect_enabled;
		var $facebook_script_loaded;
		
		/**
		 * Constructor
		 * 
		 */
		public function __construct() {
			$this->init();
		}
		
		private function init() {

			// Set options
			
			$this->set_options();
			
			// Register settings to dashboard options page
			add_action('admin_init', array(&$this, 'register_settings'));
			
			// Shortcode to display Facebook login button
			add_shortcode('vmfeu_fb_login', array($this, 'facebook_login_shortcode'));
			
			// Don't include scripts if facebook connect is enabled
			if (!$this->facebook_connect_enabled)
				return; 
			
			// add FB namespace to html tag
			add_filter('language_attributes', array(&$this, 'filter_facebook_namespace'));
			
			// add FB scripts
			// load_facbook should be called within <body> tag. The sooner the better.
			add_action('vmfeu_wp_body', array(&$this, 'load_facebook'));
			add_action('wp_footer', array(&$this, 'load_facebook'));
			add_action('admin_print_footer_scripts', array(&$this, 'load_facebook'));
			
			// perform login process
			add_action('init', array(&$this, 'facebook_login_user'));
			
			// Clear logged in with facebook cookie
			// The cookie is set when user logs in with fafebook connect. See function self::facebook_login_user();
			add_action('clear_auth_cookie', array(&$this, 'clear_logged_in_with_facebook_cookie'));
			
			// Replace avatar
			add_filter('get_avatar', array(&$this, 'filter_avatar'), 10, 5);

			// Replace login info and logout link
			add_filter('vmfeu_logged_in_log_out', array(&$this, 'filter_logged_in_log_out'), 1, 3);

			// Replace comment form "logged in as %s" with Facebook logout button
			// Parameters don't match with the function, but they are not used anyways.. Recucling :)
			add_filter('comment_form_logged_in', array(&$this, 'filter_logged_in_log_out'), 1, 3);
			
			// Show nag on my profile
			add_action('vmfeu_profile_page', array(&$this, 'profile_connected_with_facebook_nag'));
			
			// Replace message "you were redirected to profile due to invalid user info fields"
			add_filter('vmfeu_redirected_to_profile_message', array(&$this, 'filter_redirected_to_profile_message'));

			// Add custom fields to profile
			add_action('init', array(&$this, 'add_fb_related_user_info_fields'));
			
			// Add facebook login button to login form
			add_action('vmfeu_login_buttons', array(&$this, 'facebook_login_button'));
						
			// Uninstall
			// TODO There isnät vmfeu_uninstall action call, since there is no uninstall function yet
			add_action('vmfeu_uninstall', array(&$this, 'vmfeu_facebook_connect_uninstall'));
			
		}
		
		/**
		 * Set options
		 * 
		 */
		private function set_options() {
			
			$this->app_id = get_option('vmfeu_fb_app_id');
			$this->app_secret = get_option('vmfeu_fb_app_secret');
			
			if (!$this->app_id || !$this->app_secret)
				$this->facebook_connect_enabled = false;
			else {
				// Try to get access_token for the app.
				$this->app_access_token = $this->set_app_access_token();
				
				// Wrong ID / app_secret
				if (!$this->app_access_token)
					$this->facebook_connect_enabled = false;
					 
				$this->facebook_connect_enabled = get_option('vmfeu_fb_connect_enabled');
			}
			
			// Set channel url. Chanel url is a channel.php file that is used for cross domain communication.
			// See: http://developers.facebook.com/blog/post/530/
			$this->channel_url = plugins_url('facebook/channel.php' , dirname(__FILE__));
			
			// This is flag to test wether facebook script is already added into dom.
			$this->facebook_script_loaded = false;
			
		}
		
		/**
		 * Set Facebook application access token.
		 * See: http://developers.facebook.com/docs/authentication/ -> APP login
		 * 
		 * Save access token to database, or retrieve new if expired...
		 * This is blocking function and propably better to call with AJAX if 
		 * we don't have long lasting access token (see Facebook scope offline_access)
		 * 
		 * Timestamp of current access token is also saved to prevent too many queries
		 * to Facebook.. 
		 * 
		 */
		public function set_app_access_token($renew = false) {
			
			$access_token = get_option('vmfeu_fb_app_access_token');
			$access_token_timestamp = get_option('vmfeu_fb_app_access_token_timestamp');
			
			$valid = !empty($access_token) ? true : false;
			
			// Access tokens should be valid at least 2 hours (Check if Facebook has changed
			// this...)
			// Use stored timestamp to check if it is time to update the access_token
			$now = strtotime(date("Y-m-d H:i:s")); 
			$expires = strtotime("+2 hours", strtotime($access_token_timestamp));
			
			// Still valid...
			if ($access_token_timestamp && $expires > $now) {
				$valid = true;
			} else {
				$valid = false;
			}
			
			// Update or initialize
			if ($renew || !$valid) {
				
				$url = "https://graph.facebook.com/oauth/access_token?";
				$params = "client_id=".$this->app_id."&client_secret=".$this->app_secret."&grant_type=client_credentials";
				
				// Not sure if this will do anything. Offline_access (as per user authentication)
				// returns long lasting access_token. Not sure if it is the same with applications.
				// ps. Facebook documentation is very poor!  
				$params .= "&scope=offline_access";
				
				// Get access token
				// In case of bad request (wrong app_id or app_secret)
				// we'll get header 400? and empty response obviously.
				// http://codex.wordpress.org/Function_API/wp_remote_get
				$response = wp_remote_get($url.$params);
				
				// wp_remote_get will return WP_error in case of error
				if (is_wp_error($response) || $response['response']['code'] != 200) {
					// TODO display error
					// TODO the wp_remote_get will timeout in 5 seconds (default)
					// and is blocking so there could be wp_transient check to
					// disable this whole thing if several timeouts occur in a row.
					// Send verkkomuikku admin email in such event.
					return null;
				} 
									
				// Ok we got something
				$params = null;
				parse_str($response['body'], $params);
				$access_token = $params["access_token"];
				
				// Save to database
				update_option('vmfeu_fb_app_access_token', $access_token);
				// Update timestamp
				update_option('vmfeu_fb_app_access_token_timestamp', date('Y-m-d H:i:s'));
			}
			
			return $access_token;
		}
		
		/**
		 * Getter for app_access_token
		 * 
		 * @return string $app_access_token
		 */
		public function get_app_access_token() {
			return $this->app_access_token;
		}
		
		/**
		 * WP admin settings register
		 * 
		 */
		public function register_settings() { 	// whitelist options, you can add more register_settings changing the second parameter
			
			add_settings_section('facebook_connect_settings', __('Facebook Connect settings', 'verkkomuikku'), array(&$this, 'facebook_connect_settings_section'), 'VerkkomuikkuFrontEndUserSettings');
			add_settings_field('vmfeu_fb_app_id', __('Facebook Application ID', 'verkkomuikku'), array(&$this, 'sf_app_id'), 'VerkkomuikkuFrontEndUserSettings', 'facebook_connect_settings');			
			add_settings_field('vmfeu_fb_app_secret', __('Facebook Application secret key', 'verkkomuikku'), array(&$this, 'sf_app_secret'), 'VerkkomuikkuFrontEndUserSettings', 'facebook_connect_settings');
			add_settings_field('vmfeu_fb_connect_enabled', __('Enable Facebook Connect', 'verkkomuikku'), array(&$this, 'sf_enable_facebook_connect'), 'VerkkomuikkuFrontEndUserSettings', 'facebook_connect_settings');
			
			register_setting( 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_fb_app_id');
			register_setting( 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_fb_app_secret');
			register_setting( 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_fb_connect_enabled');
		}
		
		/**
		 * Display vmfeu_fb_app_id admin option field
		 * 
		 */
		public function sf_app_id() {
			?>
			<input type="text" size="20" name="vmfeu_fb_app_id" id="vmfeu_fb_app_id" value="<?php echo $this->app_id; ?>"/>
			<?php
		}

		/**
		 * Display vmfeu_fb_app_secret admin option field
		 * 
		 */
		public function sf_app_secret() {
			?>
			<input type="text" size="32" name="vmfeu_fb_app_secret" id="vmfeu_fb_app_secret" value="<?php echo $this->app_secret; ?>"/>
			<?php
		}
		
		/**
		 * Display vmfeu_facebook_connect_enabled admin option field
		 * 
		 */
		public function sf_enable_facebook_connect() {
			$checked = $this->facebook_connect_enabled ? ' checked="checked"' : '';
			?>
			<input type="checkbox" name="vmfeu_fb_connect_enabled" id="vmfeu_fb_connect_enabled" value="1" <?php echo $checked ?>/>
			<?php
		}
		
		/**
		 * Guide text for Facebook connect settings section
		 * 
		 */
		public function facebook_connect_settings_section() {
			$message = __("To provide facebook login / register functionality. Please provide Facebook app id and app secret, and check 'Facebook Connect Enabled' option.", "verkkomuikku");
			return '<p>'.$message.'</p>'; 
		}
		
		/**
		 * Add Facebook namespace into html tag attributes
		 * 
		 * @param string $attributes html language_attributes
		 */
		public function filter_facebook_namespace($attributes) {
			return $attributes.' xmlns:fb="https://www.facebook.com/2008/fbml"';
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
		 * Init Facebook. Load it asyncronously and use cookie and custom channel url.
		 * Facebook script should be loaded only once and within <body> tag. Preferably
		 * load this immediately after <body> start tag so facebook gets loaded parallel
		 * to the actual website. You may consider this good or bar.. Anyway, just hook
		 * this function to an action that runs within <body>, for example wp_footer. 
		 * 
		 */
		public function load_facebook(){
			
			// Don't load, already loaded
			if ($this->facebook_script_loaded)
				return;
				
			// All Facebook functions should be included 
			// in this javacsript function, or at least initiated from here
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
			<?php
			
			// Set this script included so it won't get included again.
			$this->facebook_script_loaded = true;
			return;
		}
		
		/**
		 * Allow other plugins to check if facebook script is loaded
		 * 
		 */
		public function is_facebook_loaded() {
			return $this->facebook_script_loaded;
		}
		
		/**
		 * Log user in, or create new user
		 * 
		 */
		public function facebook_login_user(){
			global $wpdb;
			
			//@todo: investigate: does this gets included doing regular request?
			// propably not after WP 3.1
			require_once( ABSPATH . 'wp-includes/registration.php' );

			// Check if user wants to connect with facebook
			if (!isset($_GET['vmfeu_fb_connect']) || 1 != $_GET["vmfeu_fb_connect"])
				return;
			
			// If user wants to log in there should be facebook user available (through cookie)
			$fb_user = $this->get_facebook_userdata();
			
		    // if user data is empty, then nothing will happen
		    if( $fb_user ){
		    	
		    	$fb_status = $fb_user["status"]; // connected | not_connected
		    	$fb_user = $fb_user["fb_user"];
		    	
		    	// If user is logged in (WP user), associate to Facebook account
		    	if( is_user_logged_in() ){
	    			global $current_user;
	    			
					get_currentuserinfo();
					
					// User is already logged in with Facebook
					if($fb_user_status == "connected")
						return true;
					
					// User has same email in WP and Facebook
					if( $fb_user->email == $current_user->user_email ) {
						$fb_uid = get_user_meta($current_user->ID, 'fb_uid', true);
						
						if( !$fb_uid )
							update_user_meta( $current_user->ID, 'fb_uid', $fb_user->id );
						return true;

					// else we need to set fb_uid in user meta, this will be used to identify this user
					} else {
						
						$fb_uid = get_user_meta($current_user->ID, 'fb_uid', true);
						if( !$fb_uid )
							update_user_meta( $current_user->ID, 'fb_uid', $fb_user->id );
						
						// Save Facebook email address
						$fb_email = get_user_meta($current_user->ID, 'fb_email', true);
						if( !$fb_uid )	
							update_user_meta( $current_user->ID, 'fb_email', $fb_user->email );
							
						// that's it, we don't need to do anything else, because the user is already logged in.
						return true;
					}
				
				// User is not logged in yet
		    	} else {
				    // check if user has account in the website. get id
				    $sql = 'SELECT DISTINCT `u`.`ID` FROM `' . $wpdb->users . '` `u` JOIN `' . $wpdb->usermeta . '` `m` ON `u`.`ID` = `m`.`user_id`  WHERE (`m`.`meta_key` = "fb_uid" AND `m`.`meta_value` = "' . $fb_user->id . '" ) OR user_email = "' . $fb_user->email . '" OR (`m`.`meta_key` = "fb_email" AND `m`.`meta_value` = "' . $fb_user->email . '" )  LIMIT 1 ';
				    $existing_user = $wpdb->get_var( $sql );
				    
				    // if the user exists - set cookie, do wp_login, redirect and exit
				    if( $existing_user > 0 ){
				    	
				    	$fb_uid = get_user_meta($existing_user, 'fb_uid', true);
				    	if( !$fb_uid )
				    		update_user_meta( $existing_user, 'fb_uid', $fb_user->id );
				    	
				    	$user_info = get_userdata($existing_user);
				    	
				    	// Set cookie that identifies the user logged in with facebook
						add_action('set_logged_in_cookie', array(&$this, 'set_logged_in_with_facebook_cookie'), 1, 5);
				    	
				    	// From function wp_signon
				    	wp_set_auth_cookie($existing_user, false, false);
				    	do_action('wp_login', $user_info->user_login);
						do_action('vmfeu_facebook_login', $user_info->user_login);
						
						// Set user to display Facebook avatar
						update_user_meta($existing_user, 'vmfb_fb_related', array("avatar" => 1));
						
				    	if (wp_get_referer()) {
							wp_redirect(wp_get_referer());
						} else {
							wp_redirect( $_SERVER['REQUEST_URI'] );
						}
				    	exit();
				    	
				    // if user don't exist - create one and do all the same stuff: cookie, wp_login, redirect, exit
					} else {
						
						// Allow username to be set by filter
						$username = apply_filters('vmfeu_fb_new_user_username', $fb_user->first_name." ".$fb_user->last_name, $fb_user);

						//sanitize username
						$username = sanitize_user($username, true);
		
						// check if username is taken
						// if so - add something in the end and check again
						$i='';
						while(username_exists($username . $i)){
							$i = absint($i);
							$i++;
						}
						
						// this will be new user login name
						$username = $username . $i;
						
						// put everything in nice array
						$userdata = array(
							'user_pass'		=>	wp_generate_password(12, true, true), // Gibberish password
							'user_login'	=>	$username,
							'user_nicename'	=>	$username,
							'user_email'	=>	$fb_user->email,
							'display_name'	=>	$fb_user->name,
							'nickname'		=>	$username,
							'first_name'	=>	$fb_user->first_name,
							'last_name'		=>	$fb_user->last_name,
							'role'			=>	apply_filters('vmfeu_fb_new_user_role', get_option('default_role'))
						);
						
						// Hook to add / remove / modify userdata
						$userdata = apply_filters('vmfeu_fb_new_user_userdata', $userdata, $fb_user);
						
						// Insert user
						$new_user = absint(wp_insert_user($userdata));
						
						do_action('vmfeu_fb_new_user', $new_user, $userdata, $fb_user);
						
						//if user created succesfully - log in and reload
						if( $new_user > 0 ){
							update_user_meta( $new_user, 'fb_uid', $fb_user->id );
							
							// Set user to display Facebook avatar
							update_user_meta($existing_user, 'vmfb_fb_related', array("avatar" => 1));
														
							$user_info = get_userdata($new_user);
							
					    	// Set cookie that identifies the user logged in with facebook
							add_action('set_logged_in_cookie', array(&$this, 'set_logged_in_with_facebook_cookie'), 1, 5);
							
							wp_set_auth_cookie($new_user, false, false);
							do_action('wp_login', $user_info->user_login);
							do_action('vmfeu_facebook_login', $user_info->user_login);
							
					    	wp_redirect(wp_get_referer());
					    	exit();
					    	
						} else {
							echo('Facebook Connect: Error creating new user!');
							// TODO Verkkomuikku admin email
						}
					}	    	
		    	}
		    }
		}

		/**
		 * Return user data available from Facebook based on current facebook cookie.
		 * Return array contains status and fb_user. 
		 * Status = connected if user is logged in with Facebook, not_connected when user is not logged in
		 * 
		 * TODO: can user be connected but not logged in, is there need to check this? 
		 * Now this will return status "connected" if user is logged in with Facebook.
		 * 
		 * @return boolean|array $fb_user Array ("status" => "connected"|"not_connected", "fb_user" => object, "cookie" => Facebook cookie) 
		 * user object or boolean false if there wasn't an Facebook user cookie set with 
		 * the user who is logged in to the site, or no Facebook user data available.
		 * 
		 */
		public function get_facebook_userdata() {
			global $current_user;
			
			// Return
			$return = false;
			
			// Check if there is a facebook cookie
			$cookie = $this->get_facebook_cookie();
			
			// if we have cookie, then try to get user data
			if ($cookie) {
				// get user data
				// TODO: need for caching this data? use transients if there is
				$response = wp_remote_get('https://graph.facebook.com/me?access_token=' . $cookie['access_token']);
				
				if (is_wp_error($response) || $response['response']['code'] != 200) {
					// TODO: set wp_transient to check if multiple errors occur, disable this 
					// functionality since it is blocking, and send verkkomuikku admin email notice
					return false;
				}
				
			    $fb_user = json_decode($response['body']);
			    
			    if ($fb_user && isset($fb_user->email) && !empty($fb_user->email)) {
			    	$return = array("status" => "not_connected", "fb_user" => $fb_user);	
			    }
			    
				// Just to be sure, current user should be there since we had the cookie
				if (!$current_user)
					$current_user = wp_get_current_user();
				
				// Return false or not connected fb user
				if (!$current_user->ID)
					return $return;
					
				// Check if user is connected to the site
				$fb_uid = get_user_meta($current_user->ID, 'fb_uid', true);
			
				// User is logged in with Facebook
				$return["status"] = $this->is_user_logged_in_with_facebook() ? "connected" : "not_connected";
				$return["cookie"] = $cookie;
			}
			
			return $return;
		}
		
		/**
		 * Show url to Facebook profile if user has accepted to display
		 * the link.
		 * 
		 * @param int $user_id Wordpress user id
		 * 
		 * @return string $url to users Facebook profile or false
		 */
		public function get_facebook_profile_url($user_id) {
			$fb_meta = get_user_meta($user_id, 'vmfb_fb_related', true);
			if ($fb_meta["profile_url"]) {
				$fb_uid = get_user_meta($user_id, "fb_uid", true);
				
				// Something is wrong, no fb_uid!?
				if (!$fb_uid)
					return false;
					
				return "http://www.facebook.com/".$fb_uid;
			}
			
			return false;
		}
		
		/**
		 * Set cookie that we can check the user logged in using Facebook Connect
		 * Copied from wp-includes/pluggable.php function wp_set_auth_cookie();
		 * Except, use only user id as cookie payload. This is meant to be just 
		 * simple check cookie not authentication related.
		 * 
		 * Secure connection not required. TODO investicate if it compromises security?
		 * 
		 * @param $logged_in_cookie
		 * @param $expire
		 * @param $expiration
		 * @param $user_id
		 * @param $status
		 * 
		 */
		public function set_logged_in_with_facebook_cookie($logged_in_cookie, $expire, $expiration, $user_id, $cookie_name) {
			// Generate cookie payload. Adapted from wp_generate_auth_cookie
			$user = get_userdata($user_id);
		
			$key = wp_hash("123".$user->user_login."jejeje" . '|' . $expiration, 'auth');
			$hash = hash_hmac('md5', $user->user_login . '|' . $expiration, $key);
			$logged_in_cookie = $user->user_login . '|' . $expiration . '|' . $hash;			
			
			if ( version_compare(phpversion(), '5.2.0', 'ge') ) {
				setcookie("vmfeu_fb_logged_in_cookie", $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, false, true);
				if ( COOKIEPATH != SITECOOKIEPATH )
					setcookie("vmfeu_fb_logged_in_cookie", $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, false, true);
			} else {
				$cookie_domain = COOKIE_DOMAIN;
				if ( !empty($cookie_domain) )
					$cookie_domain .= '; HttpOnly';	
				setcookie("vmfeu_fb_logged_in_cookie", $logged_in_cookie, $expire, COOKIEPATH, $cookie_domain, false);
				if ( COOKIEPATH != SITECOOKIEPATH )
					setcookie("vmfeu_fb_logged_in_cookie", $logged_in_cookie, $expire, SITECOOKIEPATH, $cookie_domain, false);				
			}		
		}
		
		/**
		 * Remove Facebook Connect logged in cookie
		 * Copied from wp-includes/pluggable.php function wp_clear_auth_cookie();
		 * 
		 */
		public function clear_logged_in_with_facebook_cookie() {
			setcookie("vmfeu_fb_logged_in_cookie", ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
			setcookie("vmfeu_fb_logged_in_cookie", ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
			return;
		}
		
		/**
		 * Check vmfeu_fb_logged_in_cookie
		 * Adapted from wp-includes/pluggable.php function wp_validate_auth_cookie()
		 * 
		 * @return boolean $logged_in_with_facebook
		 * 
		 */
		function is_user_logged_in_with_facebook() {
			
			$cookie = $_COOKIE["vmfeu_fb_logged_in_cookie"];
			$cookie_elements = explode('|', $cookie);
			if ( count($cookie_elements) != 3 )
				return false;
		
			list($username, $expiration, $hmac) = $cookie_elements;
			$cookie_elements = compact('username', 'expiration', 'hmac', 'scheme');
					
			extract($cookie_elements, EXTR_OVERWRITE);
		
			$expired = $expiration;
		
			// Allow a grace period for POST and AJAX requests
			if ( defined('DOING_AJAX') || 'POST' == $_SERVER['REQUEST_METHOD'] )
				$expired += 3600;
		
			// Quick check to see if an honest cookie has expired
			if ( $expired < time() )
				return false;
		
			$user = get_user_by('login', $username);
			if ( ! $user )
				return false;

			$key = wp_hash("123" . $username . "jejeje" . '|' . $expiration, 'auth');
			$hash = hash_hmac('md5', $username . '|' . $expiration, $key);
		
			if ( $hmac != $hash )
				return false;
		
			if ( $expiration < time() ) // AJAX/POST grace period set above
				$GLOBALS['login_grace_period'] = 1;

			// Then check there is any Facebook cookie
			if (!$this->get_facebook_cookie())
				return false;
				
			// Check if cookie is for current user
			$current_user = wp_get_current_user();
			return $user->ID == $current_user->ID;
		}	
		
		/**
		 * Use Facebook Avatars
		 * 
		 * @param $avatar
		 * @param $id_or_email
		 * @param $size
		 * @param $default
		 * @param $alt
		 * 
		 */
		public function filter_avatar($avatar, $id_or_email, $size, $default, $alt){
			global $wpdb;

			// this if/elseif/else statment is taken from 
			// wp-includes/pluggable.php, line 1610
			// http://phpxref.com/xref/wordpress/wp-includes/pluggable.php.source.html#l1610
			$id = 0;
			if ( is_numeric($id_or_email) ) {
				$id = (int) $id_or_email;
			} elseif ( is_object($id_or_email) ) {
				// No avatar for pingbacks or trackbacks
				$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
				if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
					return $avatar;
		
				if ( !empty($id_or_email->user_id) ) {
					$id = (int) $id_or_email->user_id;
				} elseif ( !empty($id_or_email->comment_author_email) ) {
					$id = $existing_user = $wpdb->get_var( 'SELECT DISTINCT `u`.`ID` FROM `' . $wpdb->users . '` `u` WHERE user_email = "' . $id_or_email->comment_author_email . '" LIMIT 1 ' );
				}
			} else {
				$id = $existing_user = $wpdb->get_var( 'SELECT DISTINCT `u`.`ID` FROM `' . $wpdb->users . '` `u` WHERE user_email = "' . $id_or_email . '" LIMIT 1 ' );
			}
			
			$fb_uid = get_user_meta($id, 'fb_uid', true);
			
			// Check also if user has accepted to view Facebook avatar
			$fb_meta = get_user_meta($id, 'vmfb_fb_related', true);
			
			if($fb_uid && $fb_meta["avatar"])
				// No height property on fb:profile-pic ! Just to fix Twenty-eleven theme not to squeeze the image
				return $this->get_facebook_profile_picture($fb_uid, $size); 
			else
				return $avatar;		
		}
		
		/**
		 * Return Facebook avatar 
		 * 
		 * @param string $fb_uid
		 * @param int $size
		 */
		public function get_facebook_profile_picture($fb_uid, $size) {
			return '<fb:profile-pic uid="' . $fb_uid . '" facebook-logo="true" class="avatar avatar-' . $size . ' photo fb-avatar" width="' . $size . '" linked="false"></fb:profile-pic>';
		}
		
		/**
		 * Filter comment form "Logged in as %s" text
		 * Return Facebook logout button
		 * 
		 * @param string $logged_in_log_out
		 * @param $user
		 * @param $user_profile_url
		 * 
		 * @return string $logged_in_log_out original text or Facebook logout button
		 * 
		 */
		public function filter_logged_in_log_out($logged_in_log_out, $user, $user_profile_url) {
			// Get avatar / facebook logout button
			$logout = $this->get_facebook_logout_button();
			if ($logout)
				return $logout;
				
			return $logged_in_log_out;
		}
		

		/**
		 * Return Facebook button lookalike that has user picture
		 * profile link and logout link.
		 * 
		 * @return string $button Facebook logout button.
		 * 
		 */
		public function get_facebook_logout_button() {
			
			$button = "";
			$user = wp_get_current_user();
			
			// No user - this shouldn't happen though
			if (!$user) {
				// TODO send verkkomuikku admin email
				return $button;
			}

			$user_profile_url =  get_edit_profile_url($user->ID);
			
			// Check if user is logged in with Facebook connect
			if ($this->is_user_logged_in_with_facebook()) {
				// Get avatar
				$fb_uid = $user->fb_uid;
				if (!$fb_uid)
					$fb_uid = get_user_meta($user->ID, "fb_uid", true);
					
				$facebook_avatar = $this->get_facebook_profile_picture($fb_uid, 32);
				
				$button = '<div class="vmfeu_fb_me">'.$facebook_avatar.
										'<p><a href="'.$user_profile_url.'" title="'.__("Edit your profile", "verkkomuikku").'" rel="nofollow">'.$user->first_name.' '.$user->last_name.'</a>'.
										'<br/>'.
										'<a href="'.wp_logout_url().'" title="'.__("Log out from this site", "verkkomuikku").'" rel="nofollow">'.__("Log out", "verkkomuikku").'</a>'.
										'</p>'.
									'</div>';
			}
			
			return $button;
		}
		
		/**
		 * Put info into my profile that you are connected with
		 * facebook and now editing local site profile settings.
		 * 
		 */
		public function profile_connected_with_facebook_nag() {
			global $vmfeu;
			
			$user = wp_get_current_user();
			
			// User should be logged in
			if($user->ID < 0) 
				return;
				
			// Don't display if admin is editing someone else
			if ($user->ID != $vmfeu->edit_user_id)
				return;
				
			// Get avatar / facebook logout button
			$logout = $this->get_facebook_logout_button();
							
			// If user is logged in with facebook there should be logout button in $logout
			if ($logout) {
				$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

				?>
				<div id="vmfeu_profile_facebook_nag">
					<?php echo $logout; ?>
					<p><?php echo sprintf(__("You are connected with Facebook and now editing your local profile at %s.", "verkkomuikku"), $blogname);?></p>
				</div>				
				<?php 
			}			
		}
		
		/**
		 * Change "you were redirected to your profile due to invalid background info" nag into
		 * something that takes account the user connected with facebook.
		 * 
		 * @param string $message Original nag
		 * @return string $message Facebook nag
		 * 
		 */
		public function filter_redirected_to_profile_message($message) {
			if ($this->is_user_logged_in_with_facebook()) {
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
				$message = '<p class="success">'.
						sprintf(__("Your user account at %s has been created. From now on you can log in with Facebook Connect or set up local password.", "verkkomuikku"), $blogname).
					'</p>'.
					'<p class="success">'.
						'<strong>'.__("Please fill the additional background information we require from our users that couldn't be fetched from Facebook.<br/>Thank you for connecting with us!", "verkkomuikku").'</strong>'.
					'</p>';
			}
			return $message;
		}
		
		/**
		 * Add some Facebook related user info fields into profile
		 * 
		 */
		public function add_fb_related_user_info_fields() {
			// Add fields only if user has connected with Facebook
			if ($this->is_user_logged_in_with_facebook()) {
				add_filter('vmfeu_user_info_fields', array(&$this,'filter_user_info_fields'));
				add_filter('vmfeu_user_info_fieldsets', array(&$this,'filter_user_info_fieldsets'));
			}			
		}
		
		/**
		 * Add checkboxes for user profile with what user can enable Facebook avatar
		 * and enable "Connect me in Facebook" link.
		 * 
		 * In order to actually show the link to facebook, use
		 * function vmfb_get_user_facebook_profile_link($user_id) in your theme files
		 * 
		 * @see verkkomuikku-front-end-user-plugin.php
		 *
		 * @param $fields - array of user info fields
		 * 
		 * @return $fields - user info fields added with Facebook related fields
		 * 
		 */
		public function filter_user_info_fields($fields) {
			
			$fields["vmfb_fb_related"] = new Vmfeu_Checkbox_Field(array(
									"name" 	=> "vmfb_fb_related",
									"title" => __("Facebook Connect", "verkkomuikku"),
									"value_label_pairs" => array(
											"avatar" => array("value" => "1", "label" => __("Use Facebook profile picture as my avatar", "verkkomuikku")),
											"profile_url" => array("value" => "1", "label" => __("Show link to my Facebook profile", "verkkomuikku")),
											),
									));

			return $fields;
		}
		
		/**
		 * Put Facebook Connect related user info fields into fieldset
		 * 
		 * @param array $fieldsets
		 * 
		 * @return array $fieldsets
		 * 
		 */
		public function filter_user_info_fieldsets($fieldsets) {
		
			$fieldsets["background"]["fields"][] = "vmfb_fb_related";
			
			return $fieldsets;
		}		

		/**
		 * Echo Facebook login button
		 * 
		 * @param array $atts shortcode attributes
		 * @param string $content no usage
		 * 
		 */
		public function facebook_login_button($atts = array(), $content = "") {
			echo do_shortcode($this->facebook_login_shortcode($atts, $content));
		}
		
		/**
		 * Shortcode to display login button
		 *  
		 * @param array $atts WP shortcode atts
		 * @param string $conetnt no usage
		 * 
		 * @return string $fb_login_button html to display Facebook login button
		 */
		public function facebook_login_shortcode($atts, $content){
			global $wpdb;
			
			// Facebook connect not enabled
			if (!$this->facebook_connect_enabled)
				return "";
			
			// Filter
			$atts = apply_filters('vmfeu_facebook_login_button', $atts);
			
			extract(shortcode_atts(array(
				'size' => 'medium',
				'login_text' => __('Login with Facebook', 'verkkomuikku'),
				'connect_text' => __('Connect with Facebook', 'verkkomuikku')
			), $atts));

			// Allow permissions to be filtered
			$scope = apply_filters('vmfeu_fb_connect_scope', array('email'));
			
			ob_start();
			?>
			<fb:login-button scope="<?php echo implode(',', $scope); ?>" size="<?php echo $size; ?>" onlogin="vmfeu.login_with_facebook();">
				<?php echo is_user_logged_in() ? $connect_text : $login_text; ?>
			</fb:login-button>
			<div class="fb_login_loader" style="display: none"><img src="<?php echo VMFEU_PLUGIN_URL."/image/ajax-loader.gif"; ?>" alt="Loading..."/></div>
			<?php
			
			$fb_button = ob_get_contents();
			ob_end_clean();
			
			return $fb_button;
		}

		/**
		 * Gets facebook cookie which is set when user is connected to the site with FB account 
		 * taken from here: http://developers.facebook.com/docs/guides/web
		 *
		 * @return array|null
		 *
		 */
		private function get_facebook_cookie() {
			$args = array();
			
			parse_str(trim($_COOKIE['fbs_' . $this->app_id], '\\"'), $args);
			ksort($args);
			$payload = '';
			
			foreach ($args as $key => $value) {
				if ($key != 'sig') {
			    	$payload .= $key . '=' . $value;
				}
			}
			
			if (md5($payload . $this->app_secret) != $args['sig']) {
				return null;
			}
			
			return $args;
		}
		
		/**
		 * Unistall
		 * TODO vmfeu plugin doesn't currently have uninstall activation hook.
		 * 
		 */
		public function vmfeu_facebook_connect_uninstall(){
			delete_option('vmfeu_fb_app_id');
			delete_option('vmfeu_fb_app_secret');
			delete_option('vmfeu_facebook_connect_enabled');
		}
	}
}
$vmfeu_fb_connect = new Vmfeu_Fb_Connect();

/**
 * Template function to show users Facebook profile link
 * 
 * @param int $user_id WP user id
 * @param string $style image, text, both
 * 
 * @return string $link link or empty string if user doesn't permit to display
 * url or user is not connected with Facebook
 * 
 */ 
function vmfb_get_user_facebook_profile_link($user_id, $style = 'both') {
	global $vmfeu_fb_connect;

	// Get profile url
	$profile_url = $vmfeu_fb_connect->get_facebook_profile_url($user_id);
	
	if (!$profile_url)
		return "";
		
	$userdata = get_userdata($user_id);
	
	$link_text = sprintf(__("%s on Facebook", "verkkomuikku"), $userdata->display_name);

	$facebook_logo = VMFEU_PLUGIN_URL."/image/facebook_f_logo.gif";
	
	if ($style == "image")	
		$link_text = '<img src="'.$facebook_logo.'" alt="'.$link_text.'"/>';
	elseif ($style == "both") 
		$link_text = '<img src="'.$facebook_logo.'" alt="'.$link_text.'" width="16" height="16"/>'.$link_text;
	
	$profile_link = '<a href="'.$profile_url.'" target="_blank" title="'.sprintf(__("%s on Facebook", "verkkomuikku"), $userdata->display_name).'">'.$link_text.'</a>';
	
	return apply_filters('vmfb_user_fb_profile_link', $profile_link, $userdata, $profile_url);
}
