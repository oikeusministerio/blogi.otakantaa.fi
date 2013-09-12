<?php
/*
Plugin Name: Verkkomuikku front end user profile plugin 
Plugin URI: http://www.verkkomuikku.fi
Description: Enables user login, registration and profile edits in front end. Inspiration from Profile Builder and Theme My Login WordPress Plugins.
Author: Teemu Muikku verkkomuikku@gmail.com
Version: 1.3
Author URI: http://www.verkkomuikku.fi
*/

/**
* TODO: 
* - Hyväksyn käyttöehdot eijo checkattuna jos menee editoimaan profiilia
* - gpl lisenssi
* - ssl tuki - kaho theme-my-login plugaria
* - uninstall, pistä tekee do_action('vmfeu_uninstall'), ni esim fb connect lisäys poistuu samalla
* - taustatietokenttien luonti hallintapaneeliin
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Define plugin url
if (!defined('VMFEU_PLUGIN_URL')) {
	if (is_multisite()) {
		define('VMFEU_PLUGIN_URL', WPMU_PLUGIN_URL.'/verkkomuikku-front-end-user-plugin');	
		define('VMFEU_PLUGIN_DIR', WPMU_PLUGIN_DIR.'/verkkomuikku-front-end-user-plugin');
	} else {
		define('VMFEU_PLUGIN_URL', WP_PLUGIN_URL.'/verkkomuikku-front-end-user-plugin');
		define('VMFEU_PLUGIN_DIR', WP_PLUGIN_DIR.'/verkkomuikku-front-end-user-plugin');
	}
}

// Load translations
load_plugin_textdomain("verkkomuikku", PLUGINDIR."/verkkomuikku-front-end-user-plugin");

// Enable / disable ajax in registration / reset password form
if (!defined('VMFEU_AJAX'))
	define('VMFEU_AJAX', false);

	
require_once('includes/vmfeu-field-base-classes.php');
require_once('includes/vmfeu-widgets.php');	
require_once('includes/vmfeu-avatar.php');
require_once('includes/vmfeu-ajax.php');
require_once('includes/vmfeu-wp-functions.php');	

if (is_admin()) {
	require_once('includes/vmfeu-admin-settings.php');
} else {
	
	require_once('includes/vmfeu-front-end-register.php');
	
	require_once('includes/vmfeu-front-end-profile.php');
	
	require_once('includes/vmfeu-front-end-login.php');
	
	require_once('includes/vmfeu-front-end-lost-password.php'); 

}
	
	
// Global to hold userinfo fields
$uifieldset = false;

if (!class_exists('Verkkomuikku_Front_End_User_Plugin')) {
	
	class Verkkomuikku_Front_End_User_Plugin {
		
		var $login_page;
		var $profile_page;
		var $registration_page;
		var $lost_password_page;
		var $restrict_access;
		var $current_page;
		var $is_vmfeu_page = null;
		var $edit_user_id;
		var $settings;
		
		/**
		 * Capability required to get access to wp-admin.
		 * Default is Editor, but lower for authors if necessary (Osallistymisymparisto)
		 *
		 * @var string
		 */
		var $admin_pages_access_capability = 'edit_posts';
		
		/**
		 * Capability required to get the admin bar showing
		 *
		 * @var string
		 */
		var $show_admin_bar_capability = 'edit_posts';		
		
		var $table_name; // name for login log table
		
		// Variables for errors and registartion/login/reset password process state
		var $user_registered = false;
		var $registration_mail_status = NULL;
		var $registration_errors = array();
		
		// Message stack for admin warnings
		var $admin_messages = array();

		public function __construct () {

			add_action('plugins_loaded', array(&$this, 'load_text_domain'));
			
			// Set attributes
			// Attributes need to be set as soon as possible
			add_action('init', array(&$this, 'init_attributes'), 1);
			
			// Redirect Multisite signup to registration page (wp-login.php?action=register)
			add_filter('wp_signup_location', array(&$this, 'filter_multiuser_signup_location'));
			
			// Redirect from wp-signup.php if multisite to custom login / registartion / lostpassword pages
			// Has to be lower priority than with the init_attributes
			add_action('init', array(&$this, 'redirect_from_wp_signup'), 100);			

			// Redirect from wp-login.php page to custom login / registartion / lostpassword pages
			// Has to be lower priority than with the init_attributes			
			add_action('init', array(&$this, 'redirect_from_wp_login'), 100);
			
			// Redirect from admin pages
			// Has to be lower priority than with the init_attributes			
			add_action('admin_init', array(&$this, 'redirect_from_admin_pages'), 100);			

			// Redirect wp-login.php queries to custom pages
			add_filter('site_url', array(&$this, 'filter_wp_login_url'), 1, 3);
			
			// Redirect registration url to our custom page
			add_filter('register_url', array(&$this, 'filter_wp_registration_url'), 10, 1);
			
			
			// Set bot check for first login
			add_action('vmfeu_new_user', array(&$this, 'set_first_login_flag'), 1, 2);
			
			// Provide bot check after first login
			add_action('template_redirect', array(&$this, 'bot_check_on_first_login'));
			
			// Prevent login if user account is not activated (given that activation is required)
			add_filter('authenticate', array( &$this, 'disable_login_if_account_not_activated' ), 100, 3 );

			// Prevent password reset if user account is not activated
			add_filter('allow_password_reset', array( &$this, 'filter_disable_password_reset_if_account_not_activated'), 101, 2);
			
			// You could also force to the profile and not let in at all if non valid fields
			// This will check validity on every page and might slower the performance
			add_action('init', array(&$this, 'force_redirect_to_profile'), 100);
			
		    // Track logins
		    global $wpdb;
		    $this->table_name = $wpdb->prefix.'login_log';
		    add_action('wp_login', array(&$this, 'log_login'));
			add_action('wp_dashboard_setup', array(&$this, 'display_login_log_dashboard_widget'));

			add_shortcode('vmfeu-register', 'vmfeu_front_end_register');			
			
			if ( is_admin() ){
				
				// Register the settings for the menu only display sidebar menu for a user with a certain capability, in this case only the "admin"
				add_action('admin_init', array(&$this, 'register_settings'));
				
				// Content for the admin page
				add_action('admin_menu', array(&$this, 'create_admin_page'));
				
				// Include additional fields and link to front end edit into admin user profile ( wp-admin/user-edit.php )
				add_action('personal_options', array(&$this, 'show_vmfeu_fields_in_dashboard_profile'), 1, 1);
				
				// Show warning messages
				add_action('admin_notices', array(&$this, 'show_admin_messages'));
 							
			} else {
				
				// Remember current page
				add_action('template_redirect', array(&$this, 'set_current_page'), 1);
				
				// Add / remove some filters / actions that don't belong to login/registration etc. pages
				add_action('template_redirect', array(&$this, 'plugin_compatibility'), 101);
				
				// If editing a user (profile page), save user_id that is being edited
				add_action('template_redirect', array(&$this, 'set_edit_user_id'), 101);
				
				// Do WordPress actions that normally fire on wp-login.php page
				add_action('template_redirect', array(&$this, 'do_wp_actions'));
				
				// Restrict access
				add_action('template_redirect', array(&$this, 'restrict_access'));
				add_filter('wp_nav_menu_objects', array(&$this, 'filter_menu'), 1, 2);
								
				// Remove Verkkomuikku sharing buttons from login etc. pages
				add_filter('vmfb_show_sharing_buttons', array(&$this, 'filter_sharing_buttons'), 50);
								
				// Include the standard style-sheet or specify the path to a new one
			    add_action('wp_print_styles', array(&$this, 'add_plugin_stylesheet'));
			    
			    // Include Javascript
			    add_action('init', array(&$this, 'add_plugin_scripts'));
			    
			    // No robots on vmfeu pages
			    add_action('wp_head', array(&$this, 'no_robots'));

				add_shortcode('vmfeu-edit-profile', 'vmfeu_front_end_profile_info');
				
				add_shortcode('vmfeu-login', 'vmfeu_front_end_login');
				
				// Include the menu file for the lost password and reset password screen
				// Lost password function displays and handles the reset password form also
				       			
				add_shortcode('vmfeu-lost-password', 'vmfeu_front_end_lost_password');				
				
				// Set the front-end admin bar to show/hide
				add_filter( 'show_admin_bar' , array(&$this, 'show_admin_bar'));				
				
				// Point edit profile url to our own template page
				add_filter('edit_profile_url', array(&$this, 'filter_edit_profile_url'), 1, 3);

				// Same for bbPress plugin profile urls
				add_filter( 'bbp_get_user_edit_profile_url', array(&$this, 'filter_edit_profile_url'), 1, 3);
				
				// Change login page title and url in nav menu
				add_filter( 'wp_setup_nav_menu_item', array(&$this, 'filter_login_nav_menu_item' ) );
				
				// Generate placeholder for thirdparty login functions such as Facebook connect
				add_action( 'login_form', array(&$this, 'thirdparty_login_buttons'));
				add_action('comment_form_must_log_in_after', array(&$this, 'thirdparty_login_buttons'));
			}
			
			// Widgets
			add_action( 'widgets_init', create_function( '', 'return register_widget("Vmfeu_Sidebar_Login");' ) );
			
			// Users display name
			add_filter('vmfeu_new_user_userdata', array(&$this, 'filter_user_display_name'), 1, 1);
			
			// Load custom user info field classes after plugins are loaded. 
			// This enables other plugins to override them if necessary
			// TODO: Is it a security threat? noooo, can't be :)
			add_action('plugins_loaded', array(&$this, 'load_custom_classes'), 101);
		}

		/**
		 * Load gettext files for the plugin
		 * 
		 */
		public function load_text_domain() {
			// Load text domain
			//load_plugin_textdomain("verkkomuikku", false, VMFEU_PLUGIN_DIR);
			load_muplugin_textdomain("verkkomuikku", preg_replace('#^'.WPMU_PLUGIN_DIR.'#', '', VMFEU_PLUGIN_DIR));
		}
		
		/**
		 * Load user info field classes after plugins are loaded.
		 * This enables plugin developers to override fields if necessary.
		 * 
		 */
		public function load_custom_classes() {
			require_once('includes/vmfeu-field-custom-classes.php');
		}

		/**
		 * Check if user can see the page
		 * Redirect to login page if user is not logged in.
		 */
		public function restrict_access() {
			global $wp_query;
			
			// Filter pages only
			if (!$wp_query || !$wp_query->queried_object || "page" != $wp_query->queried_object->post_type)
				return;
			
			$page_id = $wp_query->queried_object_id;

			// Check if page has
			if (isset($this->restrict_access[$page_id])) {
				
				// If not logged in redirect to "login" page
				if (!is_user_logged_in()) {
					$redirect_to = get_permalink($this->login_page);
					$redirect_to = add_query_arg(array("info" => urlencode("Ole hyvä ja kirjaudu sisään ensin.")), $redirect_to);
					wp_redirect($redirect_to);
					exit();
				}

				if (!current_user_can($this->restrict_access[$page_id])) {
					wp_redirect(home_url());
					exit();			
				}
			}
		}
		
		/**
		 * Change WP edit profile url function to point into front end edit profile page
		 * 
		 * TODO: bbPress edit profile url is also filtered by this function
		 * arguments for that are: $url, $user_id, $user_nicename. Create own function if scheme or user_nicename is needed
		 * @param string $url
		 * @param int $user
		 * @param string $scheme
		 */
		public function filter_edit_profile_url($url, $user, $scheme) {
			
			$url =  get_permalink($this->profile_page);
			
			// Admin trying to edit other user
			if ($user != get_current_user_id() && current_user_can('edit_users'))
				$url = add_query_arg(array("edit_id" => $user), $url);
			
			return $url;
		}
		
		/**
		 * Filter menus, unset pages if user is not allowed to see them 
		 * Pages can be restricted by user role
		 * 
		 * @param unknown_type $sorted_menu_items
		 * @param unknown_type $args
		 */
		public function filter_menu($sorted_menu_items, $args ) {
			if (current_user_can('edit_pages'))  
				return $sorted_menu_items;

			foreach ($sorted_menu_items as $key => $mi) {
				// Remove from menu if current user doesn't have required capability
				if ($mi->object == "page" && (isset($this->restrict_access[$mi->object_id]) && !current_user_can($this->restrict_access[$mi->object_id])))
					unset($sorted_menu_items[$key]);
			}
			
			return $sorted_menu_items;
		}
		
		/**
		 * Rewrites URL's containing wp-login.php created by site_url()
		 *
		 * @param string $url The URL
		 * @param string $path The path specified
		 * @param string $orig_scheme The current connection scheme (HTTP/HTTPS)
		 * 
		 * @return string The modified URL
		 */
		public function filter_wp_login_url( $url, $path, $orig_scheme ) {
			global $pagenow;

			$parsed_url = parse_url( $url );
			
			// Only affect wp-login.php urls
			if (!preg_match("#wp-login.php$#", $parsed_url["path"]))
				return $url;

			// Extract the query string
			if ( isset( $parsed_url['query'] ) ) {
				wp_parse_str( $parsed_url['query'], $query_args );
				foreach ( $query_args as $k => $v ) {
					if ( strpos( $v, ' ' ) !== false )
						$query_args[$k] = rawurlencode( $v );
				}
			}

			if (isset($query_args['action']) && $query_args['action'] == "register") {
				$url = get_permalink($this->registration_page);
			} else if (isset($query_args['action']) && ($query_args['action'] == "lostpassword" || $query_args['action'] == "retrievepassword" || $query_args['action'] == "resetpass" || $query_args['action'] == "rp")) {
				$url = get_permalink($this->lost_password_page);
			} else {
				$url = get_permalink($this->login_page);
			}

			if ( 'https' == strtolower( $orig_scheme ) )
				$url = preg_replace( '|^http://|', 'https://', $url );
			
			// These actions are no longer needed
			$exclude = array("register", "login");
			if ( $query_args ) {
				if (isset($query_args['action']) && in_array($query_args['action'], $exclude))
					unset($query_args['action']);
				$url = add_query_arg( $query_args, $url );
			}

			return $url;
		}
		
		/**
		 * Filters the default registration page url, returns our
		 * custom registration page.
		 * 
		 * @param string $url
		 * 
		 * @since WP 3.6
		 */
		public function filter_wp_registration_url($url) {
			return get_permalink($this->registration_page);	
		}
		
		/**
		 * Add filter for Vmfeu pages to not to display
		 * sharing buttons on these pages.
		 * 
		 * @param boolean $show_sharing_buttons
		 * 
		 * @return boolean $show_sharing_buttons
		 */
		public function filter_sharing_buttons($show_sharing_buttons) {
			
			// Return false if we are on a login / registration / profile / lost password pages
			if ($this->is_vmfeu_page() !== false)
				return false;
				
			return $show_sharing_buttons;
		}
		
		/**
		 * Set usermeta vmfeu_first_login when user is registered.
		 * This flag is used to redirect the user to bot check page.
		 * 
		 * @param boolean $new_user
		 * @param object $userdata
		 */
		public function set_first_login_flag($new_user, $userdata) {
			if ($this->settings['use_bot_check_on_first_login']) {
				update_usermeta($userdata->ID, "vmfeu_first_login", true);
			}
		}
		
		/**
		 * Check if user logs in first time and redirect to a bot check.
		 * Utilize login page for bot check.
		 * 
		 */
		public function bot_check_on_first_login() {
			if ($this->settings['use_bot_check_on_first_login'] && is_user_logged_in()) {
				$user = wp_get_current_user();
				if (get_usermeta($user->ID, "vmfeu_first_login") && (!is_page($this->login_page) || !isset($_GET['vmfeu_check']))) {
					// Redirect
					// The userdata vmfeu_first_login is removed when user is
					// convinces not to be a bot
					$url = add_query_arg(array("vmfeu_check" => "1"), get_permalink($this->login_page));
					wp_safe_redirect($url);
					exit();
				}
			}
		}
		
		/**
		 * Modify user password and meta to indicate this account needs activation
		 * Replace password with random word and generate activation key
		 * 
		 * @param int $new_user ID of the new user
		 * @param array $userdata
		 * @return boolean $account_activation_required
		 */
		public function require_account_activation($new_user, $userdata) {
			global $wpdb;
			
			// Return false if activation is not needed or an admin created the user
			if (!$this->settings["require_account_activation"]  || current_user_can('edit_users'))
				return false;
			
			// Save hashed plaintext pass temporarily to db 
			$hashed_password = wp_hash_password(stripslashes($userdata['user_pass']));
			update_user_meta($new_user, 'vmfeu_password', $hashed_password);

			// Disable password reset for user
			update_user_meta($new_user, 'vmfeu_no_password_reset', true);
			
			// Generate random password
			$temp_password = wp_generate_password(12, true, true);
			
			// Generate random activation key
			$key = wp_generate_password( 20, false );

			// Replace user password with random password and insert activation key
			$wpdb->update( $wpdb->users, array( 'user_pass' => $temp_password, 'user_activation_key' => $key ), array( 'ID' => $new_user ) );
									
			return true;
		}
		
		/**
		 * Generate link that user can use to activate the account
		 * 
		 * TODO error checking needed?
		 * 
		 * @param array|int|object $userdata
		 * @return string $url Link (or url) to the address where activation is done.
		 */
		public function get_account_activation_link($userdata) {
			global $wpdb;
			
			if (is_array($userdata))
				$userdata = get_user_by('login', $userdata["user_login"]);
			elseif ( is_numeric($userdata) )
				$userdata = get_userdata($userdata);

			if (!$userdata->user_activation_key) {
				// TODO send verkkomuikku email
			}
			
			// Generate link
			$url = get_permalink($this->login_page);
			$url = add_query_arg(array("vmfeu_a" => urlencode($userdata->user_login), "key" => $userdata->user_activation_key), $url);
			
			return $url;
		}
		
		/**
		 * Activate user account.
		 * Get activation key from GET. Delete activation key, and user meta values, 
		 * and put the correct password back to it's place.
		 * 
		 * @return boolean|WP_error $status Return true if all ok, or WP_error if there was an error
		 */
		public function activate_user_account() {
			global $wpdb;
			
			// Parameters should come with $_GET
			$activate = stripslashes($_GET["vmfeu_a"]);
			$key = stripslashes($_GET["key"]);
			
			// This shouldn't happen (if you check there are key and account args before calling this function)
			if (empty($activate) || empty($key)) {
				// TODO Send verkkomuikku admin email
				return new WP_error("vmfeu_no_activation_key", __("User account activation argument(s) missing..."));
			}
			
			// Also we could check that the account needs activation.. This is not needed though
			// since there shoudln't
			// wpdb->prepare escapes $_GET parameters
			$sql = $wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_login = %s AND user_activation_key = %s LIMIT 1", $activate, $key);
			$user_id = $wpdb->get_var($sql);
			
			// Found it!
			if ($user_id) {
				// Get hashed password from usermeta and save it to original position
				$user_pass = get_user_meta($user_id, 'vmfeu_password', true);
				// Remove activation key as well
				$wpdb->update( $wpdb->users, array( 'user_pass' => $user_pass, 'user_activation_key' => '' ), array( 'ID' => $user_id ) );
				
				// Enable password reset
				delete_user_meta($user_id, 'vmfeu_no_password_reset');
				delete_user_meta($user_id, 'vmfeu_password');

				return $user_id;
				
			// User might already been activated (double click, slow connection etc. issues)..
			} else {
				$sql = $wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_login = %s AND user_activation_key = '' LIMIT 1", $activate);
				$user_id = $wpdb->get_var($sql);
				if ($user_id)
					return new WP_error('vmfeu_user_already_activated', __("The user you tried to activate is already activated.", "verkkomuikku"));				
			}
			
			// Wrong key most propably
			return new WP_error('vmfeu_wrong_activation_key', __("The activation key was wrong.", "verkkomuikku"));
		}
		
		/**
		 * Return WP_error if a user tries to log in with account that is not activated
		 * yet.
		 * 
		 * @param object $user
		 * @param string $username
		 * @param string $password
		 * 
		 * @return object|WP_error user or WP_error
		 */
		public function disable_login_if_account_not_activated( $user, $username, $password ) {

			// Check if user account is not activated
			if (!$this->is_user_account_activated($username)) {
				return new WP_error('vmfeu_login_account_not_activated', __("You have to activate your account before you can log in. Check your email for an activation link. Your account is activated when you click the activation link.", "verkkomuikku")."<br/>".$this->request_new_activation_email_link($username));
			} 
			
			return $user;
		}
		
		/**
		 * Don't let users to reset their passwords if account is not activated.
		 * 
		 * Return WP_error if user account hasn't been activated.
		 * 
		 * @param boolean $allow
		 * @param int $user_id
		 * @return boolean|WP_error 
		 */
		public function filter_disable_password_reset_if_account_not_activated($allow, $user_id) {
			$user = get_userdata($user_id);
			if (!$this->is_user_account_activated($user->user_login)) {
				return new WP_error('vmfeu_lostpassword_account_not_activated', __("You cannot change your password before your account is activated. Check your email for an activation link. Your account is activated when you click the activation link.", "verkkomuikku")."<br/>".$this->request_new_activation_email_link($user->user_login));
			}
			return $allow;
		}
		
		/**
		 * When user logs in or tries to reset password we need to check that 
		 * user account is activated.
		 * 
		 * @param string|object $user_login or $user object
		 * 
		 * @return boolean $activated Wheteher user account is activated or not
		 */
		public function is_user_account_activated($user) {
			
			if (is_object($user))
				$userdata = $user;
			else
				$userdata = get_user_by('login', $user);
	
			// Check if there is an activation key.
			// NOTE: The activation key is used for reseting password as well!
			// we have to check the meta value vmfeu_no_password_reset not to block
			// users logging in when a passwodr reset was asked
			if ('' != $userdata->user_activation_key && get_user_meta($userdata->ID, 'vmfeu_no_password_reset')) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Return full link that will send new activation email to user.
		 * Use hash so people won't use this for spam.
		 * 
		 * @param $user_id
		 * @return string $link the whole <a href... thing
		 */
		public function request_new_activation_email_link($user_login) {
			
			// Simple hash
			// Hash user_login and email and userid
			$user = get_user_by('login', $user_login);
			
			// Use same hash as in email_new_activation_link function
			$hash = wp_hash($user->ID."je".$user->user_email."ok".$user->user_login);
			
			$url = get_permalink($this->login_page);
			$url = add_query_arg(array("vmfeu_a" => $user->user_login, "send_activation_link" => $hash), $url);
			$link_text = __("Resend activation link.", "verkkomuikku");
			
			return '<a href="'.$url.'">'.$link_text.'</a>';
		}
		
		/**
		 * Send activation key to user.
		 * 
		 * @param int $user_id
		 */
		public function email_new_activation_link($user_login, $request_hash) {
			global $wpdb;
			
			$user_login = stripslashes($user_login);
			$user = get_user_by('login', $user_login);
			
			$user_email = stripslashes($user->user_email);
			
			// Generate hash similarly as in the request_new_activation_email_link function
			$hash = wp_hash($user->ID."je".$user->user_email."ok".$user->user_login);

			// Prevent spam
			if ($hash != $request_hash)
				return false;
					
			$key = $user->user_activation_key;
			
			// If there wasn't a key, generate new one
			if ( empty( $key ) ) {
				$key = wp_generate_password( 20, false );
				$wpdb->update( $wpdb->users, array( 'user_activation_key' => $key ), array( 'ID' => $user->ID ) );
			}
	
			$blogname = get_option( 'blogname' );
	
			$subject = sprintf( __( "%s - Activate your user account", "verkkomuikku" ), $blogname );
			$subject = apply_filters('vmfeu_resent_email_subject_activation_required', $subject);
		
			$url = $this->get_account_activation_link($user);
			
			$message = sprintf( __( 'Thanks for registering at %s! To complete the registration process of your account please click the following activation link: ', 'verkkomuikku' ), $blogname ) . "\r\n\r\n";
			$message .= $url . "\r\n";
			$message = apply_filters('vmfeu_resent_email_message_activation_required', $message, $user, $url);
			
			return wp_mail( $user_email, $subject, $message );
		}
		
		/**
		 * Redirect user to profile always if there are invalid background info fields.
		 * This redirect can be set of from dashboard. Then user will be redirected to
		 * profile only when they log in (given there are invalid fields).
		 * 
		 */
		public function force_redirect_to_profile() {
			global $current_user;

			// Not logged in
			if (!is_user_logged_in())
				return;
			
			// Option is not set
			if (!$this->settings['force_to_profile'])
				return;
			
			// Admins
			if (current_user_can('edit_users'))
				return;
				
			if (!$current_user->ID)
				$current_user = wp_get_current_user();
			
			// Set edit user id to current user for $uifieldset->populate to work properly.
			// Edit user id is set again at template_redirect
			$this->set_edit_user_id($current_user->ID);
			
			// Prevent too many $uifieldset->populate requests...
			// $uifieldset->populate will set user meta tag vmfeu_all_ok true or false
			// with the Vmfeu_User_Info_Fieldset::update_all_fields_ok()
			if (!Vmfeu_User_Info_Fieldset::check_all_fields_ok($current_user->ID))
				$this->redirect_to_profile();
		}
		
		/**
		 * When user logs in redirect to profile if there is fields
		 * that are not filled in yet. This might occur when there are new
		 * user profile fields or if user registers by thirdparty system such as Facebook.
		 * 
		 */
		public function redirect_to_profile() {
			global $uifieldset;

			// Not for AJAX!
			if (defined('DOING_AJAX') && true == DOING_AJAX)
				return;
				
			// Prevent infinite loop. Allow user to log out.
			$vmfeu_page = $this->is_vmfeu_page();
			if ($vmfeu_page == "profile" || $vmfeu_page == "login") {
				return;
			}
			
			// Get vmfeu fields and userdata
			if (!$uifieldset)
				$uifieldset = new Vmfeu_User_Info_Fieldset();
				
			// There is non valid field(s)
			if ($uifieldset->populate() == false) {
				
				if (apply_filters('vmfeu_dont_force_to_profile', false))
					return;
					
				$profile_url = get_permalink($this->profile_page);
				// Add query arg for profile page to display proper feedback message to user
				$profile_url = add_query_arg(array("vmfeu_p_e" => 1), $profile_url);
				wp_safe_redirect($profile_url);
				exit();
			}
		}
		
		/**
		 * Disable wp-admin from others but editors and admins
		 * 
		 */ 
		public function redirect_from_admin_pages() {
			global $pagenow;
			
			// Allow admin ajax
			if (defined('DOING_AJAX') && DOING_AJAX)
				return;
			
			// Allow to media upload (if wp_editor() is used in the frontend)
			if ($pagenow == "media-upload.php")
				return;

			// Also required for media-upload.php
			if ($pagenow == "async-upload.php")
				return;				
				
			// Disable users access
			if (!current_user_can($this->admin_pages_access_capability)) {
				// If logged in guide to frontpage, otherwise to login page
				if (is_user_logged_in()) {
					$redirect_to = get_bloginfo('siteurl');
				} else {
					$redirect_to = get_permalink($this->login_page);
					$redirect_to = add_query_arg(array("info" => urlencode("Ole hyvä ja kirjaudu sisään ensin.")), $redirect_to);
				}
				wp_redirect($redirect_to);
				exit();
			}
		}
		
		/**
		 * Redirect from wp-login to custom login/registration/lost password pages
		 *
		 * This method should be hooked to init action
		 */
		public function redirect_from_wp_login() {
			global $pagenow;
			
			if ($pagenow != "wp-login.php")
				return;
						
			$url = site_url('wp-login.php?'.$_SERVER["QUERY_STRING"]);
			
			wp_safe_redirect($url);
			exit();
		}
		
		/**
		 * Filter the multisite signup url which normally points 
		 * to main site wp-signup.php file. Instead, guide to 
		 * vmfeu plugin registration page URL.
		 * 
		 * @param string $multisite_signup_url
		 */
		public function filter_multiuser_signup_location($multisite_signup_url) {
			// site_url('wp-login.php...') is filtered to point vmfeu pages
			return site_url('wp-login.php?action=register');
		}
		
		/**
		 * WP Multisite redirects users to register from ./wp-signup.php
		 * Lets just skip it altogether and redirect the user to the 
		 * registration form that is same as WP normal (no multisite)
		 * wp-login.php?action=register
		 * 
		 * NOTE: wp-signup.php provides way to establish new blogs (the
		 * traditional WP multisite functionality). Disabling the wp-signup.php
		 * prohibits the ability to create new blogs from the front end. It is
		 * possible to create new blogs from the network admin.
		 * 
		 */
		public function redirect_from_wp_signup() {
			global $pagenow;
			
			if ($pagenow != "wp-signup.php")
				return;
			
			// Redirect to normal WP registration procedure.
			// site_url() is filtered by vmfeu plugin to
			// redirect to our custom registration page.
			$url = site_url('wp-login.php?action=register');
			
			wp_safe_redirect($url);
			exit();
		}
		
		/**
		 * General init tasks 
		 */
		public function init_attributes() {

			// Load settings
			$this->settings = apply_filters('vmfeu_settings', get_option('vmfeu_settings'));
			$this->login_page = get_option('verkkomuikku_login_page');
			$this->profile_page = get_option('verkkomuikku_profile_page');
			$this->registration_page = get_option('verkkomuikku_registration_page');
			$this->lost_password_page = get_option('verkkomuikku_lost_password_page');
				
			// List pages that require logging in / certain user role
			// Set manually, use page number as key and capability as value
			$restrict_access = array( );
			
			// If users cannot register, add registration page to restricted
			if (!get_option( 'users_can_register' ))
				$restrict_access[$this->registration_page] = "edit_users";
				
			// Disable lost password page
			if (!$this->settings['allow_password_reset'])
				$restrict_access[$this->lost_password_page] = "edit_users";
				
			// Set pages that users without capability are redirected from to login page
			$this->restrict_access = apply_filters('vmfeu_restrict_access_pages', $restrict_access);			
			
			// Init settings if they aren't yet init
			// TODO: possibility to reset settings
			if (!$this->settings) {
				$this->settings = array();
				$this->settings['use_default_style'] 			= 1;
				$this->settings['allow_password_reset'] 		= 1;
				$this->settings['use_bot_check_on_first_login'] = 0;
				$this->settings['require_account_activation'] 	= 1;
				$this->settings['force_to_profile'] 			= 1;
				$this->settings['registration_transient']		= 1;
				$this->settings['terms_page']					= "";
				
				update_option('vmfeu_settings', $this->settings);
			}
			
			// Create pages for login, registration, lost password and profile editing

			// Checks if the page doesn't exist
			if (!$this->login_page || false == $this->check_page($this->login_page)) {
				require_once ( ABSPATH.'wp-admin/includes/theme.php' );
				$args = apply_filters('vmfeu_page_args', array("post_status" => "publish", "post_type" => "page", "post_title" => _q("Login", "verkkomuikku", true), "post_content" => "[vmfeu-login]", "comment_status" => "closed"));
				$page_id = wp_insert_post( $args );
				update_option('verkkomuikku_login_page', $page_id);
				$this->login_page = $page_id;
			}
			
			if (!$this->profile_page || false == $this->check_page($this->profile_page)) {
				require_once ( ABSPATH.'wp-admin/includes/theme.php' );
				$args = apply_filters('vmfeu_page_args', array("post_status" => "publish", "post_type" => "page", "post_title" => _q("Profile", "verkkomuikku", true), "post_content" => "[vmfeu-edit-profile]", "comment_status" => "closed"));
				$page_id = wp_insert_post( $args );
				update_option('verkkomuikku_profile_page', $page_id);
				$this->profile_page = $page_id;
			}
					
			if (!$this->registration_page || false == $this->check_page($this->registration_page)) {
				require_once ( ABSPATH.'wp-admin/includes/theme.php' );
				$args = apply_filters('vmfeu_page_args', array("post_status" => "publish", "post_type" => "page", "post_title" => _q("Register", "verkkomuikku", true), "post_content" => "[vmfeu-register]", "comment_status" => "closed"));
				
				$page_id = wp_insert_post( $args );
				update_option('verkkomuikku_registration_page', $page_id);
				$this->registration_page = $page_id;
			}
			
			if (!$this->lost_password_page || false == $this->check_page($this->lost_password_page)) {
				require_once ( ABSPATH.'wp-admin/includes/theme.php' );
				$args = apply_filters('vmfeu_page_args', array("post_status" => "publish", "post_type" => "page", "post_title" => _q("Retrieve password", "verkkomuikku", true), "post_content" => "[vmfeu-lost-password]", "comment_status" => "closed"));
				$page_id = wp_insert_post( $args );
				update_option('verkkomuikku_lost_password_page', $page_id);
				$this->lost_password_page = $page_id;
			}
				

			

		}

		/**
		 * Helper to check if page still exists
		 * 
		 * @return string|boolean $post_status or boolean false if the post don't exist
		 */
		public function check_page($page_id) {
			$status = get_post_status($page_id);
			
			// If the page is in trash display warning!
			if ("trash" == $status) {
				$page = get_post($page_id);
				$title = apply_filters('the_title', $page->post_title);
				$this->add_admin_message(sprintf(__('Verkkomuikku front end user plugin %1$s page is in, the plugin doesn\'t work without the page. Please restore the page from trash or empty the trashbin and new %1$s page will be created automatically.', 'verkkomuikku'), $title));
			}
				
			return $status;
		}
		
		/**
		 * Dashboard settings page
		 * 
		 */
		function create_admin_page(){
			add_submenu_page('users.php', __('Front-end user plugin', 'verkkomuikku'), 'Front-end user', 'delete_users', 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_settings_page');
		}
		
		/**
		 * WP admin settings register
		 * TODO: Add all settings, basic fields etc
		 * 
		 */
		function register_settings() { 	// whitelist options, you can add more register_settings changing the second parameter
			
			add_settings_section('general_settings', __('General Settings', 'verkkomuikku'), array(&$this, 'general_settings_section'), 'VerkkomuikkuFrontEndUserSettings');
			add_settings_field('use_default_style', __('Use plugin CSS', 'verkkomuikku'), array(&$this, 'sf_use_default_style'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');			
			add_settings_field('allow_password_reset', __('Allow lost password / reset password', 'verkkomuikku'), array(&$this, 'sf_allow_password_reset'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');
			add_settings_field('use_bot_check_on_first_login', __('Show bot check question after first log in.', 'verkkomuikku'), array(&$this, 'sf_use_bot_check_on_first_login'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');
			add_settings_field('require_account_activation', __('Require account activation (user may not log in until he has clicked link in his email to activate the account.', 'verkkomuikku'), array(&$this, 'sf_require_account_activation'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');
			add_settings_field('force_to_profile', __('Redirect user to profile if there is invalid userinfo fields. User is not allowed anywhere else on the page but profile and logout.', 'verkkomuikku'), array(&$this, 'sf_force_to_profile'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');
			add_settings_field('registration_transient', __('Users are allowed to create one user account per hour. Admins can create accounts as they wish.', 'verkkomuikku'), array(&$this, 'sf_registration_transient'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');
			add_settings_field('terms_page', __('If you require users to accept terms and conditions, give terms and conditions page id.', 'verkkomuikku'), array(&$this, 'sf_terms_page'), 'VerkkomuikkuFrontEndUserSettings', 'general_settings');
			register_setting( 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_settings', array(&$this, 'validate_settings' ) );
		}
		
		/**
		 * Display guide text for the general settings section
		 * 
		 */
		function general_settings_section() {
			
			
		}
		
		/**
		 * Echo settings field: Include plugin default style sheet
		 * 
		 */
		function sf_use_default_style() {
			$use = $this->settings["use_default_style"];
			?>
				<input type="checkbox" name="vmfeu_settings[use_default_style]" id="use_default_style" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}

		/**
		 * Echo settings field: Allow users to reset their password
		 * 
		 */
		function sf_allow_password_reset() {
			$use = $this->settings["allow_password_reset"];
			?>
				<input type="checkbox" name="vmfeu_settings[allow_password_reset]" id="allow_password_reset" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}

		/**
		 * Echo settings field: Allow users to reset their password
		 * 
		 */
		function sf_use_bot_check_on_first_login() {
			$use = $this->settings["use_bot_check_on_first_login"];
			?>
				<input type="checkbox" name="vmfeu_settings[use_bot_check_on_first_login]" id="use_bot_check_on_first_login" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}		
		
		/**
		 * Echo settings field: Require new account activation ( user gets email that has the activation key/link )
		 * 
		 */
		function sf_require_account_activation() {
			$use = $this->settings["require_account_activation"];
			?>
				<input type="checkbox" name="vmfeu_settings[require_account_activation]" id="require_account_activation" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}	

		/**
		 * Echo settings field: Force user to his profile if there are any invalid userinfo fields.
		 * 
		 */
		function sf_force_to_profile() {
			$use = $this->settings["force_to_profile"];
			?>
				<input type="checkbox" name="vmfeu_settings[force_to_profile]" id="force_to_profile" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}		

		/**
		 * Echo settings field: Allow users to register one account per hour. Prevents spam
		 * 
		 */
		function sf_registration_transient() {
			$use = $this->settings["registration_transient"];
			?>
				<input type="checkbox" name="vmfeu_settings[registration_transient]" id="registration_transient" value="1" <?php echo checked( 1, $use, false ) ?>/>
			<?php 
		}		

		/**
		 * Echo settings field: Terms and conditions page id. 
		 * 
		 */
		function sf_terms_page() {
			$value = intval($this->settings["terms_page"]);
			?>
				<input type="text" name="vmfeu_settings[terms_page]" id="terms_page" value="<?php echo $value; ?>" size="5"/>
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
			if ($input['terms_page'])
				$input['terms_page'] = intval($input['terms_page']);
				
			return $input; // return validated input
		}		
		
		/**
		 * Include basic stylesheet for login, registration and profile forms
		 * 
		 */
		public function add_plugin_stylesheet() {
			
			// Load styles always on the frontend since
			// the login widget etc. can be on any page..
			//if (!$this->is_vmfeu_page())
			//	return;
				
	        $styleUrl = VMFEU_PLUGIN_URL.'/css/style.css';
	        $styleFile = VMFEU_PLUGIN_DIR.'/css/style.css';

	        if ( file_exists($styleFile) && $this->settings['use_default_style']) {
	            wp_register_style('vmfeu_stylesheet', $styleUrl);
	            wp_enqueue_style( 'vmfeu_stylesheet' );
	        }
		}
		
		/**
		 * Add javascript 
		 * 
		 */
		public function add_plugin_scripts() {

			// Load scripts always on the frontend since
			// the login widget etc. can be on any page..			
			// if (!$this->is_vmfeu_page())
			//	return;
				
			// Include vmfeu scripts
			wp_register_script('vmfeu_script',
		   		plugins_url('js/vmfeu.js', __FILE__),
		       	array('jquery'),
		       	'1.0');
			wp_enqueue_script('vmfeu_script');

			// Pass variables for javascript
			wp_localize_script( 'vmfeu_script', 'VMFEUAjax', array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'pluginurl' => VMFEU_PLUGIN_URL,
				'ajax_enabled' => VMFEU_AJAX
			));
		}
		
		/**
		 * We don't need login / regitration / profile pages
		 * to be listed by search engines
		 * 
		 */
		public function no_robots() {
			if ($this->is_vmfeu_page()) { 
				?><meta name='robots' content='noindex,nofollow' /><?php 
			}
		}
		
		/**
		 * Show admin bar for admins and editors only
		 * 
		 * @param $show_admin_bar
		 */
		function show_admin_bar($show_admin_bar){
			if (!current_user_can($this->show_admin_bar_capability)) // edit posts
				return false;
			else 
				return true;
		}
				
		/**
		 * Login tracking
		 * 
		 */ 
		public function log_login($user_login) {
			global $wpdb;
			
			$this->init_logs();
			
			$user = get_user_by('login', $user_login);
			
			$ip = preg_replace( '/[^0-9a-fA-F:., ]/', '',$_SERVER['REMOTE_ADDR'] );
			
			// TODO utilize wpdb->prepare
			$sql = "INSERT INTO {$this->table_name} (user_id, user_login, ip)
				VALUES ({$user->ID}, '{$user->user_login}', '{$ip}')";
			
			$wpdb->query($sql);
		}
		
		/**
		 * Generate database table for login tracking
		 * 
		 */
		public function init_logs() {
		    global $wpdb;
    
		    if ($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
		      $sql = "CREATE TABLE $this->table_name (
				ID INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				user_id INT( 11 ) NOT NULL,
				user_login VARCHAR(100) NOT NULL,
				ip VARCHAR(100) NOT NULL,
				login_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
				)";
		      
		      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		      dbDelta($sql);
		    }
		    
		    return;	
		}
		
		/**
		 * Dashboard widget for login tracker
		 */
		public function display_login_log_dashboard_widget() {
			global $wp_meta_boxes;
		  	
			$title = "Sisäänkirjautumiset";
			
			wp_add_dashboard_widget('login_log_widget', $title, array(&$this, 'login_log_widget'));
		
			// Get the regular dashboard widgets array 
			// (which has our new widget already but at the end)
			$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
			
			// Backup and delete our new dashbaord widget from the end of the array
			$bu = array('login_log_widget' => $normal_dashboard['login_log_widget']);
			unset($normal_dashboard['login_log_widget']);
		
			// Merge the two arrays together so our widget is at the beginning
			$sorted_dashboard = array_merge($bu, $normal_dashboard);
		
			// Save the sorted array back into the original metaboxes 
			$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
		}
		
		public function login_log_widget() {
		  	global $wpdb;
		   	
			$sql = "SELECT * FROM {$this->table_name} order by login_time DESC LIMIT 20";  
		  	$twenty_last = $wpdb->get_results($sql);
		  	
		  	$sql = "SELECT * FROM {$this->table_name} WHERE login_time > DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY user_id";
		   	$active_today = $wpdb->get_results($sql);
		
		  	$sql = "SELECT * FROM {$this->table_name} WHERE login_time > DATE_SUB(NOW(), INTERVAL 1 WEEK) GROUP BY user_id";
		   	$active_week = $wpdb->get_results($sql);
		
		  	$sql = "SELECT * FROM {$this->table_name} WHERE login_time > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY user_id";
		   	$active_month = $wpdb->get_results($sql);   	
		   	
		  	?>
		
			<p>
			<strong><?php _e('20 last logins', 'verkkomuikku')?></strong>
			<?php 
				foreach ($twenty_last as $i => $u) {
					echo "<br/>".mysql2date("d.m.Y H:i:s", $u->login_time)." - ";
					if (current_user_can('edit_users')) { 
						echo '<a href="user-edit.php?user_id='.$u->user_id.'">'.$u->user_login.'</a>';
					} else {
						echo $u->user_login;
					}
				}
			?>	
			</p>
			<p>
			<strong><?php _e("Last day", "verkkomuikku") ?></strong><br/>
			<?php 
			if (empty($active_today))
				echo "-";
			else {
				foreach ($active_today as $i => $u) {
					if ($i > 0)
						echo ", ";
					if (current_user_can('edit_users')) { 
						echo '<a href="user-edit.php?user_id='.$u->user_id.'">'.$u->user_login.'</a>';
					} else {
						echo $u->user_login;
					}
				}
			}
			?>
			</p>
			<p>
			<strong><?php _e("Last week", "verkkomuikku") ?></strong><br/>
			<?php 
			if (empty($active_week))
				echo "-";
			else {
				foreach ($active_week as $i => $u) {
					if ($i > 0)
						echo ", ";
					if (current_user_can('edit_users')) { 
						echo '<a href="user-edit.php?user_id='.$u->user_id.'">'.$u->user_login.'</a>';
					} else {
						echo $u->user_login;
					}
				}
			}
			?>
			</p>
			<p>
			<strong><?php _e("Last month", "verkkomuikku") ?></strong><br/>
			<?php 
			if (empty($active_month))
				echo "-";
			else {
				foreach ($active_month as $i => $u) {
					if ($i > 0)
						echo ", ";
					if (current_user_can('edit_users')) { 
						echo '<a href="user-edit.php?user_id='.$u->user_id.'">'.$u->user_login.'</a>';
					} else {
						echo $u->user_login;
					}
				}
			}
			?>
			</p>
			<?php 	
		}
		
		/**
		 * Set current page
		 *  - this is here since get_the_id() may return post id or 
		 *    forum id when called from sidebar template
		 */
		public function set_current_page() {
			global $wp_query;
			if (!$wp_query || !$wp_query->queried_object || "page" != $wp_query->queried_object->post_type)
				$this->current_page = false;
			
			$this->current_page = $wp_query->queried_object_id;
		}

		/**
		 * This function is to remove / add filters / actions of thirdparty 
		 * plugins. 
		 * 
		 */
		public function plugin_compatibility() {
			// We don't want commenting on vmfeu pages
			add_filter('comments_open', array(&$this, 'disable_commenting'), 2, 100);
			
			// Jetpack plugin has Sharedaddy sharing plugin. We don't want sharing 
			// buttons on vmfeu pages!
			if (function_exists('sharing_display'))
				add_filter( 'sharing_show', array(&$this, 'remove_sharedaddy_sharing'), 2, 100 );
		}
		
		/**
		 * WP template function filter that determines whether or not to show
		 * comments template. We don't want commenting on vmfeu pages...
		 * 
		 * @param boolean $open
		 * @param int $post_id
		 * 
		 * @return boolean $open
		 */
		public function disable_commenting($open, $post_id) {
			
			// Post id might be null
			if (!$post_id)
				$post_id = $this->current_page;
				
			if ($this->is_vmfeu_page($post_id))
				return false;
			
			return $open;
		}
		
		/**
		 * If we are on a login/registration/profile/forgotpassword pages,
		 * don't display sharing buttons. Sharedaddy plugin is part of Jetpack plugin.
		 * 
		 * @param boolean $show
		 * @param object $post current post
		 * 
		 * @return boolean $show
		 */
		public function remove_sharedaddy_sharing($show, $post) {
			
			if (!$show)
				return $show;
			
			if ($this->is_vmfeu_page($post))
				return false;
				
			return $show;
		}
		
		/**
		 * If we are on login/registration/profile/forgotpassword pages.
		 * 
		 * @param int $post_id
		 * 
		 * @return boolean $return
		 */
		public function is_vmfeu_page($post = "") {
			
			if (is_admin())
				return false;
				
			// Already checked for the request
			if ($this->is_vmfeu_page !== null)
				return 	$this->is_vmfeu_page;
			
			// Check if we got a post object or post ID
			if (is_numeric($post)) {
				$post_id = $post;
			} elseif (isset($post->ID)) {
				if ($post->post_type != "page") {
					$this->is_vmfeu_page = false;
					return false;
				} else {
					$post_id = $post->ID;
				}
			} elseif ($this->current_page) {
				$post_id = $this->current_page;
			} 
			
			// If we have a page id
			if ($post_id == $this->login_page)
				$this->is_vmfeu_page = "login";
			elseif($post_id == $this->registration_page)
				$this->is_vmfeu_page = "registration";
			elseif($post_id == $this->profile_page)
				$this->is_vmfeu_page = "profile";
			elseif($post_id == $this->lost_password_page) 
				$this->is_vmfeu_page = "lostpassword";
			
			if ($this->is_vmfeu_page !== null)
				return $this->is_vmfeu_page;
				
			// Ok, we are so early on WP initialization that no page id available yet
			// Compare REQUEST_URI to vmfeu page urls.
			$urls = array(
				"login" 		=> get_permalink($this->login_page),
				"registration" 	=> get_permalink($this->registration_page),
				"profile" 		=> get_permalink($this->profile_page),
				"lostpassword" 	=> get_permalink($this->lost_password_page)
			);
			
			foreach ($urls as $page => $url) {
				// Cut out protocol
				$tmp = explode("://", $url);
				$url = $tmp[1];
				
				// Vmfeu page url shoud now match pretty much $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]
				if (preg_match("#".$url."#", $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"])) {
					$this->is_vmfeu_page = $page;
					return $page;
				}
			}
			
			// Ok not on a vmfeu page
			$this->is_vmfeu_page = false;
			return false;
		}
		
		/**
		 * Save user id that is being edited.
		 * Uses query argument edit_id, If in vmfeu profile page 
		 * Alternatively user id can be set with argument. 
		 *  - Dashboard profile edit page shows user info fields and link
		 *    to front end profile edit page.
		 *
		 * @param int $user_id - optional
		 */
		function set_edit_user_id($user_id = 0) {
			global $current_user;

			// By default user is editing her self
			$edit_user_id = $current_user->ID;
			
			// When editing a profile
			if (is_page($this->profile_page) && isset($_REQUEST["edit_id"])) {
				$edit_user_id = intval($_REQUEST["edit_id"]);
					
			// manually set edit_user_id
			// Utilized to set edit user id in dashboard profile edit
			} else if ($user_id > 0) {
				$edit_user_id = $user_id;
			}
			
			// Save the user id that is being edited.
			// Either current user or if admin edits another user
			if (current_user_can('edit_users') || $current_user->ID == $edit_user_id)
				$this->edit_user_id = $edit_user_id;
			else
				$this->edit_user_id = $current_user->ID;
		}
		
		/**
		 * Returns fields filled with userdata.
		 * 
		 * @param int $user_id
		 * 
		 * @return array $userdata
		 */
		public function get_userdata($user_id) {
			global $uifieldset;
			
			if (!$uifieldset)
				// Get vmfeu fields and userdata
				$uifieldset = new Vmfeu_User_Info_Fieldset();
				
			$this->set_edit_user_id($user_id);
			$uifieldset->populate();
			
			return $uifieldset->get_fields();
		}
		
		/**
		 * Do some WP actions that should be done in login/registration/lostpassword pages
		 * Note: Doesn't apply for sidebar login widget.
		 * 
		 */
		public function do_wp_actions() {
			
			$vmfeu_page = $this->is_vmfeu_page();
			
			// Only for vmfeu pages
			if (!$vmfeu_page)
				return false;

			switch ($vmfeu_page) {
				case "login":
					$this->do_wp_login_head();
					$this->do_wp_login_footer();
					break;
				case "registration":
					$this->do_wp_login_head();
					$this->do_wp_login_footer();
					break;
				case "lostpassword":
					$this->do_wp_login_head();
					$this->do_wp_login_footer();
					break;
				case "profile":
					
					break;
				default: 
					break;
					
			}
			
			$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
			
			if ( isset($_GET['key']) )
				$action = 'resetpass';
				
			if ( in_array($action, array('logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login'))) {
				do_action('login_form_' . $action);
			}			
		}
		
		/** 
		 * Do actions that should be done in login head
		 * 
		 */
		public function do_wp_login_head() {
			// Don't index any of these forms
			add_filter( 'pre_option_blog_public', '__return_zero' );
			add_action( 'login_head', 'noindex' );
			add_action('wp_head', create_function('', 'do_action("login_enqueue_scripts"); do_action("login_head");'));
		}
		
		/**
		 * Do actions that should be done in login footer
		 * 
		 */
		public function do_wp_login_footer() {
			add_action('wp_footer', create_function('', 'do_action("login_footer");'));
		}

		/**
		 * Set display name to first_name last_name
		 * WP sets display name the same as user_login 
		 * if display_name is not present in userdata
		 * 
		 * @param Array $userdata
		 */
		public function filter_user_display_name($userdata) {
			// Leave empty, and wp_insert_user will make the user_login as the display_name
			//$userdata['display_name'] = ''; 
			// You could use the First name last name here or whatever.
			$userdata['display_name'] = $userdata['first_name']." ".$userdata["last_name"];
			return $userdata;
		}
		
		/**
		 * Change login menu item title and url
		 *
		 * Callback for "wp_setup_nav_menu_item" hook in wp_setup_nav_menu_item()
		 *
		 * @param object $menu_item The menu item
		 * @return object The (possibly) modified menu item
		 */
		function filter_login_nav_menu_item( $menu_item ) {
			if ( 'page' == $menu_item->object && $this->login_page == $menu_item->object_id ) {
				if (is_user_logged_in()) {
					$menu_item->title = __("Log out", "verkkomuikku");
					$menu_item->url = wp_logout_url();
				}
			}
			return $menu_item;
		}
		
		/**
		 * Echo placeholder for thirdparty login buttons such as Facebook connect
		 * 
		 */
		public function thirdparty_login_buttons() {
			// Check if any action hooks would like to echo some login buttons / other stuff
			if (has_action('vmfeu_login_buttons')) :
				?>
				<div class="vmfeu_extra_login_wrapper"><?php do_action('vmfeu_login_buttons'); ?></div>
				<?php 
			endif;
		}
		
		/**
		 * Echo extra fields that are not part of basic WP user background fieldset on top
		 * of the profile page. Also provide link for admins to click intp vmfeu profile
		 * editor that can edit these extra fields.
		 * 
		 * @param object $profile_user
		 */
		public function show_vmfeu_fields_in_dashboard_profile($profile_user) {
			global $uifieldset;
			$userdata = $profile_user->data;

			if (!$uifieldset)
				// Get vmfeu fields and userdata
				$uifieldset = new Vmfeu_User_Info_Fieldset();
				
			$this->set_edit_user_id($userdata->ID);
			$uifieldset->populate();
			
			$fieldsets = $uifieldset->get_fieldsets();
			$fields = $uifieldset->get_fields();
			
			$vmfeu_edit_url = $this->get_edit_user_url($userdata->ID);
			
			// profile_update action is called inside a table
			echo '</table>';
			?>
			
			<h3><?php _e("Front-end user plugin", "verkkomuikku") ?></h3>
			<p><?php printf(__('You might not be able to see and edit all user information fields from dashboard. To edit custom user info fields <a href="%s">click here</a>', 'verkkomuikku'), $vmfeu_edit_url);?></p>
			<table class="form-table" style="background-color: white; border: 1px solid #ccc; ">
				<tbody>
				<?php 
				foreach($fieldsets as $fieldset) {
					foreach ($fieldset["fields"] as $field_name) {
						if ($field_name == "user_pass")
							continue;
							
						// If the field is not set, the fieldname is 
						// in the fieldset by human error ;)
						if (!isset($fields[$field_name])) {
							echo '<tr><th colspan="2">'.sprintf(__("Vmfeu: Warning, field %s is in the fieldset but not in the fields!", "verkkomuikku"), $field_name).'</th></tr>';
							continue;
						}
							
						// Crude way, just implode string value (just for visuals)
						$value = $fields[$field_name]->get_value();
						if (is_array($value))
							$value = implode(', ', $value);
							
						echo '<tr>';
							echo '<th>'.$fields[$field_name]->get_title().'</th>';
							echo '<td>'.$value.'</td>';
						echo '</tr>';
					}
				}
				do_action('vmfeu_dashboard_profile_custom_fields', $userdata);
				?>
				</tbody>
			<?php 
		}
		
		/**
		 * Get URL to the given users profile (in frontend)
		 * 
		 * @param int $user_id
		 * 
		 * @return string $url
		 */
		public function get_edit_user_url($user_id = 0) {
			
			if (0 == $user_id)
				$user_id = get_current_user_id();
				
			if (!$user_id)
				return '';
				
			$url = get_permalink($this->profile_page);
			$url = add_query_arg(array("edit_id" => $user_id), $url);

			return $url;
		}
		
		/**
		 * Show admin messages if any
		 * 
		 */
		public function show_admin_messages() {

			if (empty($this->admin_messages) || !current_user_can('edit_pages'))
				return;

			foreach ((array)$this->admin_messages as $type => $messages) {
				
				if ('error' == $type) { echo '<div id="message" class="error">'; }
				else { echo '<div id="message" class="updated fade">'; }
			
				foreach ($messages as $message) {
					echo "<p><strong>".$message."</strong></p>";
				}

				echo "</div>";
			}
							
		}
		
		/**
		 * Add message to admin message stack that will display
		 * on the dashboard
		 * 
		 * @param string $message
		 * @param string $type 'error' type will show in red background
		 */
		public function add_admin_message($message, $type = 'error') {
			
			if (!is_array($this->admin_messages[$type]))
				$this->admin_messages[$type] = array($message);
			else
				$this->admin_messages[$type][] = $message; 
		}
		
		/**
		 * Return current url but remove query args
		 * 
		 * @param boolean $return you can return the url instead of echoing it
		 * @return string $url to current page
		 */
		public function current_url($return = false) {
			$url = $_SERVER["REQUEST_URI"];
			/*
			foreach ($_GET as $key => $val) {
				if (in_array($key, array('logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login', 'key', 'vmfeu_fb_connect', 'activate')))
					$url = remove_query_arg($key, $url);
			}*/
			$pos = strpos($url, "?");
			if ($pos !== false)
				$url = substr($url, 0, $pos);
			
			if ($return) 
				return $url;
			
			echo $url;
			return;
		}
	}
}

$vmfeu = new Verkkomuikku_Front_End_User_Plugin();
?>