<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Class to handle the lost/retrieve password form
 * 
 * @author verkkomuikku
 *
 */
class Vmfeu_Lostpassword {

	/**
	 * Status for lost password 
	 * 
	 * @var boolean
	 */
	public $lost_password = false;
	
	/**
	 * Messages for user feedback
	 * 
	 * @var array
	 */
	public $messages = array();
	
	public function __construct() {
		
		add_action('template_redirect', array(&$this, 'lost_password'));
		
		add_action('init', array(&$this, 'generate_messages'));
	}
	
	/**
	 * Check request and do password reset 
	 * 
	 * Similar to what wp-login.php does.
	 * 
	 * @uses vmfeu_retrieve_password()
	 * @uses vmfeu_check_password_reset_key()
	 * @uses vmfeu_reset_password()
	 * 
	 */
	public function lost_password() {
		global $wp_query, $vmfeu; 
		
		// Return if not in lost password page
		if (!$wp_query || !$wp_query->queried_object || "page" != $wp_query->queried_object->post_type || $vmfeu->lost_password_page != $wp_query->queried_object->ID)
			return;	
		
		// No password retrieval for logged in users
		if (is_user_logged_in() && !current_user_can('create_users')) {
			wp_redirect(home_url());
			exit;
		}
		
		$http_post = ( 'POST' == $_SERVER['REQUEST_METHOD'] );
		$action = $_REQUEST['action'];
		
		switch ( $action ) {
			case 'lostpassword' :
			case 'retrievepassword' :
	
				if ( $http_post ) {
					$errors = vmfeu_retrieve_password();
					if ( !is_wp_error( $errors ) ) {
						$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : $this->get_current_url( 'checkemail=confirm' );
						wp_safe_redirect( $redirect_to );
						exit();
					}
				}
				
				if ( isset( $_REQUEST['error'] ) && 'invalidkey' == $_REQUEST['error'] )
					$errors = new WP_error( 'invalidkey', __( 'Sorry, that key does not appear to be valid.', 'verkkomuikku' ) );
					
				if (is_wp_error($errors)) {
					$this->lost_password = $errors;
					owelapro_add_message($errors, 'error');
				}
				
				break;
			case 'resetpass' :
			case 'rp' :
	
				$user = vmfeu_check_password_reset_key($_GET['key'], $_GET['login']);
			
				if ( is_wp_error($user) ) {
					
					// Show user feedback
					owelapro_add_message($errors, 'error');
					owelapro_transfer_messages();
					
					wp_redirect( site_url('wp-login.php?action=lostpassword&error=invalidkey') );
					exit;
				}
			
				$errors = '';
			
				if ( isset($_POST['pass1']) && $_POST['pass1'] != $_POST['pass2'] ) {
					$errors = new WP_Error('password_reset_mismatch', __('The passwords do not match.'));
				} elseif ( isset($_POST['pass1']) && !empty($_POST['pass1']) ) {
					vmfeu_reset_password($user, $_POST['pass1']);
					
					$redirect_to = $this->get_current_url( 'resetpass=complete' );
					wp_safe_redirect( $redirect_to );
					exit;
				}
	
				if (is_wp_error($errors)) {
					$this->lost_password = $errors;
					owelapro_add_message($errors, 'error');
				}
								
				break;
			default:
				break;
		}	
	}
	
	/**
	 * Helper function to get current url
	 * 
	 * @param string $query
	 * 
	 * @return string $url
	 */
	public function get_current_url( $query = '' ) {
		$url = remove_query_arg( array( 'instance', 'action', 'checkemail', 'error', 'loggedout', 'registered', 'redirect_to', 'updated', 'key', '_wpnonce', 'reauth', 'login' ) );
		if ( !empty( $query ) ) {
			$r = wp_parse_args( $query );
			foreach ( $r as $k => $v ) {
				if ( strpos( $v, ' ' ) !== false )
					$r[$k] = rawurlencode( $v );
			}
			$url = add_query_arg( $r, $url );
		}
		return $url;
	}
	
	/**
	 * Check if certain parameters are as argument in the url to display user feedback.
	 * 
	 */
	public function generate_messages() {

		$messages = array();
		if (isset($_GET['checkemail']) && "confirm" == $_GET['checkemail']) {
			$messages[] = __("Check your email for password change confirmation link.", 'verkkomuikku');
			
		} elseif (isset($_GET['resetpass']) && "complete" == $_GET['resetpass']) {
			$messages[] = __("Password changed! You may now log in with your new password.", 'verkkomuikku');
		}
		
		$this->messages = $messages;
		
		return;
	}
	
	/**
	 * Lost password form - copied from wp-login.php
	 * 
	 */
	public function lost_password_template() {
		
		// Flag used for checking if login and register links should be shown
		$show_nav = false;
		
		// Display message to check email if user already sent the lostpassword form.
		if (!empty($this->messages)) {
			if (isset($this->messages["resetpass"]))
				$show_nav = true;
			$messages = $this->messages;
		} else { ?>
			<div class="lost-password-form">
				<form name="lostpasswordform" id="lostpasswordform" action="<?php echo site_url('wp-login.php?action=lostpassword', 'login_post') ?>" method="post">
					<label class="input">
						<span><?php _e('Your username or e-mail', 'verkkomuikku') ?></span>
						<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr($user_login); ?>" size="30" tabindex="<?php echo apply_filters('vmfeu_tabindex', 1); ?>" />
					</label>
					<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Get New Password'); ?>" tabindex="<?php echo apply_filters('vmfeu_tabindex', 2); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
					<?php do_action('lostpassword_form'); ?>
				</form>
			</div>
		<?php } // endif; ?>

				
		<?php if ($show_nav || !empty($messages)) : ?>
			<div class="messages">
				<?php 
				if (!empty($messages)) {
					foreach ($messages as $message)
						echo '<p class="success">'.$message.'</p>';		
				}
				?>
				<?php if ($show_nav) : ?>
					<p id="nav">
					<a href="<?php echo site_url('wp-login.php', 'login') ?>"><?php _e('Log in', 'verkkomuikku') ?></a>
					<?php if (get_option('users_can_register')) : ?>
					 | <a href="<?php echo site_url('wp-login.php?action=register', 'login') ?>"><?php _e('Register', 'verkkomuikku') ?></a>
					<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endif;
	}
	
	/**
	 * Reset password form template - copied from wp-login.php
	 * 
	 */
	public function reset_password_template() {
		?>
		<div class="reset-password-form">
			<form name="resetpassform" id="resetpassform" action="<?php echo site_url('wp-login.php?action=resetpass&key=' . urlencode($_GET['key']) . '&login=' . urlencode($_GET['login']), 'login_post') ?>" method="post">
				<input type="hidden" id="user_login" value="<?php echo esc_attr( $_GET['login'] ); ?>" autocomplete="off" />
				
				<?php echo sprintf(__("Change password for user %s", 'verkkomuikku'), htmlspecialchars($_GET['login'], ENT_COMPAT, 'UTF-8')); ?>
				<label class="input">
					<span><?php _e('New password', 'verkkomuikku') ?></span>
					<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" tabindex="<?php echo apply_filters('vmfeu_tabindex', 1); ?>"/>
				</label>
				
				<label class="input">
					<span><?php _e('Confirm new password', 'verkkomuikku') ?></span>
					<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" tabindex="<?php echo apply_filters('vmfeu_tabindex', 2); ?>"/>
				</label>
					
				<?php /*?>
					<div id="pass-strength-result" class="hide-if-no-js"><?php _e('Strength indicator', 'verkkomuikku'); ?></div>
					<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'verkkomuikku'); ?></p>
				<?php */ ?>
				<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php _e('Change password', 'verkkomuikku'); ?>" tabindex="<?php echo apply_filters('vmfeu_tabindex', 3); ?>" />
			</form>
		</div>	
		<?php
	}	
	
}
global $vmfeu_lostpassword; 
$vmfeu_lostpassword = new Vmfeu_Lostpassword();

/**
 * Shortcode handler to display lost password / reset password form.
 * 
 * @param array $atts - standard WP shortcode atts, no use here
 * @param string $content - standard WP shortcode $content, no use here
 * 
 * @return string $form HTML
 */
function vmfeu_front_end_lost_password($atts = array(), $content = ''){
	global $vmfeu_lostpassword;
	
	$defaults = array(
		"show_errors" => true,
	);
	
	extract(wp_parse_args($atts, $defaults), EXTR_SKIP);
	
	if (!is_a($vmfeu_lostpassword, "Vmfeu_Lostpassword"))
		$vmfeu_lostpassword = new Vmfeu_Lostpassword();
		
	$action = $_REQUEST['action'];
	
	// This is shortcode so return the form, not echo
	ob_start();
	?>
	<div class="vmfeu_wrapper" id="vmfeu_lostpassword">
	<?php 
	
	// Display errors
	if ($show_errors && is_wp_error($vmfeu_lostpassword->lost_password)) {
		if (is_wp_error($vmfeu_lostpassword->lost_password)) { 
			?>
			<div class="messages">
				<p class="error">
					<?php echo implode('<br/><br/>', $vmfeu_lostpassword->lost_password->get_error_messages());  ?>
				</p>
			</div>
			<?php 
		} 
	}
	
	// Display lost password or reset password form
	// Lost password form template is shown for confirmation, check email etc. 
	if (empty($action) || $action == 'lostpassword' || $action == 'retrievepassword') 
		$vmfeu_lostpassword->lost_password_template();
	else if ($action == 'resetpass' || $action == 'rp')
		$vmfeu_lostpassword->reset_password_template();
	?>
	</div>
	<?php 
	
	$form = ob_get_contents();
	ob_end_clean();
	
	return $form;
}