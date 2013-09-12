<?php
/**
 * Here are some functions from wp-login.php. These functions
 * are needed when reseting password.
 * These functions are straight copies from wp-login.php
 *
 * reset_password
 * retrieve_password
 * check_password_reset_key
 * 
 * tested <= WP version 3.4.1
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

global $wp_version; 

// Display warning for admin
/* TODO Doesn't quite work...
if ($wp_version != "3.1.1" && current_user_can('edit_users'))
die("Verkkomuikku Front end users plugin: Update plugin file vmfeu-wp-functions.php, check the file for more info.");
*/

/**
 * Handles sending password retrieval email to user.
 *
 * @uses $wpdb WordPress Database object
 *
 * @return bool|WP_Error True: when finish. WP_Error on error
 */
function vmfeu_retrieve_password() {
	global $wpdb, $current_site;

	$errors = new WP_Error();

	if ( empty( $_POST['user_login'] ) ) {
		$errors->add('empty_username', __('Please enter a username or e-mail address.', 'verkkomuikku'));
	} else if ( strpos( $_POST['user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
		if ( empty( $user_data ) )
			$errors->add('invalid_email', __('There is no user registered with that email address.', 'verkkomuikku'));
	} else {
		$login = trim($_POST['user_login']);
		$user_data = get_user_by('login', $login);
	}

	do_action('lostpassword_post');

	if ( $errors->get_error_code() )
		return $errors;

	if ( !$user_data ) {
		$errors->add('invalidcombo', __('Invalid username or e-mail.', 'verkkomuikku'));
		return $errors;
	}

	// redefining user_login ensures we return the right case in the email
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;

	do_action('retreive_password', $user_login);  // Misspelled and deprecated
	do_action('retrieve_password', $user_login);

	$allow = apply_filters('allow_password_reset', true, $user_data->ID);

	if ( ! $allow )
		return new WP_Error('no_password_reset', __('Password reset is not allowed for this user', 'verkkomuikku'));
	else if ( is_wp_error($allow) )
		return $allow;

	$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
	if ( empty($key) ) {
		// Generate something random for a key...
		$key = wp_generate_password(20, false);
		do_action('retrieve_password_key', $user_login, $key);
		// Now insert the new md5 key into the db
		$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
	}
	$message = __('Someone requested that the password be reset for the following account:', 'verkkomuikku') . "\r\n\r\n";
	$message .= site_url() . "\r\n\r\n";
	$message .= sprintf(__('Username: %s', 'verkkomuikku'), $user_login) . "\r\n\r\n";
	$message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'verkkomuikku') . "\r\n\r\n";
	$message .= __('To reset your password, visit the following address:', 'verkkomuikku') . "\r\n\r\n";
	$message .= site_url("wp-login.php?action=rp&key={$key}&login=" . rawurlencode($user_login), 'login') . "\r\n";
	
	if ( is_multisite() )
		$blogname = $GLOBALS['current_site']->site_name;
	else
		$blogname = get_option('blogname');

	$title = sprintf( __('%s - Password Reset', 'verkkomuikku'), $blogname );

	// WordPress filters
	$title = apply_filters('retrieve_password_title', $title);
	$message = apply_filters('retrieve_password_message', $message, $key);
	
	// Vmfeu filters for more options
	$title = apply_filters('vmfeu_retrieve_password_title', $title);
	$message = apply_filters('vmfeu_retrieve_password_message', $message, $key, $user_data);
	
	if ( $message && !wp_mail($user_email, $title, $message) )
		wp_die( __('The e-mail could not be sent.', 'verkkomuikku') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', 'verkkomuikku') );

	return true;
}

/**
 * Retrieves a user row based on password reset key and login
 *
 * @uses $wpdb WordPress Database object
 *
 * @param string $key Hash to validate sending user's password
 * @param string $login The user login
 *
 * @return object|WP_Error
 */
function vmfeu_check_password_reset_key($key, $login) {
	global $wpdb;

	$key = preg_replace('/[^a-z0-9]/i', '', $key);

	if ( empty( $key ) || !is_string( $key ) )
		return new WP_Error('invalid_key', __('Invalid key', 'verkkomuikku'));

	if ( empty($login) || !is_string($login) )
		return new WP_Error('invalid_key', __('Invalid key', 'verkkomuikku'));

	$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));

	if ( empty( $user ) )
		return new WP_Error('invalid_key', __('Invalid key', 'verkkomuikku'));

	return $user;
}

/**
 * Handles resetting the user's password.
 *
 * @uses $wpdb WordPress Database object
 *
 * @param string $key Hash to validate sending user's password
 */
function vmfeu_reset_password($user, $new_pass) {
	do_action('password_reset', $user, $new_pass);

	wp_set_password($new_pass, $user->ID);

	wp_password_change_notification($user);
}