<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Class to handle the login form
 * 
 * @author verkkomuikku
 *
 */
class Vmfeu_Login {

	/**
	 * Status for login
	 * 
	 * @var boolean
	 */
	public $login = false;
	
	/**
	 * Status for account activation
	 * 
	 * @var boolean
	 */
	public $activate = false;
	
	/**
	 * Status for account activation email
	 * 
	 * @var boolean
	 */
	public $activation_email = null;
	
	/**
	 * Flag that is used not to display the
	 * login form twice for any request
	 * 
	 * @var boolean
	 */
	public $form_printed = false;
	
	/**
	 * Feedback messages
	 * For example failed login attempt etc.
	 * 
	 * Associative array("messages" => array("login_error"...), "message_class" => array("login_error"...);
	 * 
	 * @var array
	 */
	public $messages = array();


	public function __construct() {
		add_action('init', array(&$this, 'signon'));
		add_action('plugins_loaded', array(&$this, 'signout'));
		add_action('init', array(&$this, 'user_activation'));
		
		add_action('init', array(&$this, 'generate_messages'));
		
		add_filter('logout_url', array(&$this, 'filter_logout_url'), 100, 2);
	}
	
	/**
	 * Sign user in
	 * 
	 * wp_signon can only be executed before anything is outputed in the page
	 */
	public function signon(){
		
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'log-in' && wp_verify_nonce($_POST['login_nonce_field'],'verify_true_login')) {
			global $vmfeu;
			
			/* TODO: login transient system is not very well thought out and not working that good..
			// Check how many times user has tried to log in within interval
			// if too many times, validate bot check field.
			// Bot check field should be printed one attempt before this
			if (Vmfeu_User_Info_Fieldset::check_user_login_transient() > 3) {
				$e = new WP_error();
				Vmfeu_User_Info_Fieldset::validate_bot_check($e);
				
				// If bot check validate returns errors
				if ($e->get_error_message()) {
					$this->login = new WP_error($e->get_error_code(), $e->get_error_message());
				}
			}*/
			
			// All ok, try to log in
			if (!is_wp_error($this->login))
				$this->login = wp_signon( array( 'user_login' => $_POST['user-name'], 'user_password' => $_POST['password'], 'remember' => $_POST['remember-me'] ), false );
				
			if ( !is_wp_error($this->login) ) {
				// Reset transient for this user
				//Vmfeu_User_Info_Fieldset::reset_login_transient();
				
				if ( isset( $_REQUEST['redirect_to'] ) )
					$redirect_to = $_REQUEST['redirect_to'];
	
				$redirect_to = apply_filters('login_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user);
				
				if ("" == $redirect_to)
					$redirect_to = home_url();
									
				wp_safe_redirect($redirect_to);
				exit;
			}
		}
	}


	/**
	 * Sign out user
	 * 
	 */
	public function signout() {
		
		// If doing with wp-login.php, do it here already
		if (isset($_REQUEST["action"]) && "logout" == $_REQUEST["action"] && check_admin_referer('log-out')) {
			
			wp_logout();
		
			$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : home_url();
			
			wp_safe_redirect( $redirect_to );
			exit();
		}
		
	}
	
	/**
	 * Add random parameter to the logout URL. Sometimes the cahced login page
	 * prevents from logging out!
	 * 
	 * @param string $url
	 * @param string $redirect
	 * 
	 * @return string $url
	 */
	public function filter_logout_url($url, $redirect) {
		return add_query_arg(array('rand' => substr(md5(time()), 0, 5)), $url);
	}	

	/**
	 * Activate user account.
	 * 
	 */
	function user_activation() {
		
		if (!isset($_GET["vmfeu_a"]))
			return;
			
		global $vmfeu;
		
		// Catch activation from request attributes
		if (isset($_GET["vmfeu_a"]) && !empty($_GET["vmfeu_a"]) && isset($_GET["key"]) && !empty($_GET["key"])) {
			$this->activate = $vmfeu->activate_user_account();
		}
		
		// Send user the activation link again via email.
		if (isset($_GET["vmfeu_a"]) && !empty($_GET["vmfeu_a"]) && isset($_GET["send_activation_link"]) && !empty($_GET["send_activation_link"])) {
			$this->activation_email = $vmfeu->email_new_activation_link($_GET["vmfeu_a"], $_GET["send_activation_link"]);
		}
	}
	
	/**
	 * Generate feedback messages to be displayed
	 * 
	 */
	public function generate_messages() {

		$messages = array();
		$message_class = array();
		
		if (!empty( $_POST['action'] ) && $_POST['action'] == 'log-in' && is_wp_error($this->login) && count($this->login->get_error_messages()) > 0) {
			$messages["login_error"] = implode('<br/><br/>', $this->login->get_error_messages());
			$message_class["login_error"] = "error";
		}

		if (is_wp_error($this->activate)) {
			$messages["activation_error"] = __("User account activation didn't work:", "verkkomuikku")."<br/>";
			$messages["activation_error"] .= implode('<br/><br/>', $this->activate->get_error_messages()); 
			$messages["activation_error"] .= apply_filters('vmfeu_account_activation_error', '');
			$message_class["activation_error"] = "error";
			
		} elseif ($this->activate == true) {
			$messages["activation_success"] = sprintf( __('Your account has been activated! You may now log in!', 'verkkomuikku'));
			$messages["activation_success"] .= apply_filters('vmfeu_account_activation_success', '');
			$message_class["activation_success"] = "success";
			
		} elseif ($this->activation_email === false) {
			$messages["activation_email_error"]	= __("Error sending email.", "verkkomuikku");
			$message_class["activation_email_error"] = "error";
			
		} elseif ($this->activation_email === true) {
			$messages["activation_email_success"] = sprintf( __('New user account activation email sent.', 'verkkomuikku'));
			$message_class["activation_email_success"] = "success";
		}

		
		$this->messages["messages"] = $messages;
		$this->messages["message_class"] = $message_class;
		
		do_action('vmfeu_login_messages', $this->messages);
		
		return;
	}

	/**
	 * Shortcode handler to display login form.
	 * 
	 * @param array $atts - standard WP shortcode atts
	 * @param string $content - standard WP shortcode $content, no use here
	 * 
	 * @return string $form
	 */
	public function login_template($atts = array(), $content = ''){
		global $uifieldset, $vmfeu;
		
		// Don't print twice on a page!
		if ($this->form_printed)
			return "";
		
		$defaults = array(
			"messages_position"	 	=> "after", // before | after, the login form
			"show_messages"			=> true,
			"show_links"			=> true,
			"show_on_login_page"	=> true,
			"link_separator"	 	=> "<br/>",
			"submit_button_class" 	=> "button-primary login-button",
			"remember_me"			=> true, 
		);
		
		$args = wp_parse_args($atts, $defaults);
		extract($args, EXTR_SKIP);

		// Return if not wanted to display on login page (if the form is called for sidebar for example)
		if (false == $show_on_login_page && "login" == $vmfeu->is_vmfeu_page())
			return;
				
		// Init fieldset only once
		if (!$uifieldset)
			$uifieldset = new Vmfeu_User_Info_Fieldset();
		
		// Class for the wrapper depending if logged in or not
		$wrapper_class = is_user_logged_in() ? "sign-out" : "sign-in";
	
		// Since this is shortcode, we should return the form, not echo it
		ob_start();
		?>
		<div class="vmfeu_wrapper <?php echo $wrapper_class; ?>" id="vmfeu_login">
		
			<?php 
			
			// Feedback for the user
			$messages = $this->messages["messages"];
			$message_class = $this->messages["message_class"];
			
			// Links
			$links = array();
			if ($vmfeu->settings['allow_password_reset'] && !isset($messages["login_error"])) {
				$links[] = '<a href="'.site_url('/wp-login.php?action=lostpassword').'" id="lostpassword" rel="nofollow">'.__('Lost password?', 'verkkomuikku').'</a>';
			}
				
			$url = wp_registration_url();
			$registration_link = apply_filters('vmfeu_show_registration_link_on_login_page', true);
			if ($registration_link && $url && get_option( 'users_can_register' ) && !is_page($vmfeu->registration_page)) {
				$links[] = '<a href="'.$url.'" title="'.$uifieldset->get_text("login_label_create_account").'" id="register" rel="nofollow">'.$uifieldset->get_text("login_label_create_account").'</a>';
			}
			
			// Messages and links into same wrapper
			$messages_html = '';
			if ($show_links || $show_messages) {
				$messages_html = '<div class="messages">'; 
				if ($show_messages) {
					foreach ($messages as $name => $message) {
						$class = isset($message_class[$name]) ? $message_class[$name] : '';
						$messages_html .= '<p class="'.$class.'">'.$message.'</p>';
					}
				}
				if ($show_links && !empty($links)) {
					$messages_html .= '<div class="links">'; 
					$messages_html .= implode($link_separator, $links);
					$messages_html .= '</div>';
				} 
				$messages_html .= '</div>';			
			}
								
			// Not logged in, display the loginform and messages
			if ( !is_user_logged_in()) {
			
					// Stay on same page 
					$login_form_url = $vmfeu->current_url(true);
					
					// If we are in vmfeu pages, on successfull login, redirect to frontpage
					// on error redirect to login page (send this form to login page
					$vmfeu_page = $vmfeu->is_vmfeu_page();
					if ($vmfeu_page)
						$login_form_url = get_permalink($vmfeu->login_page); 
	
					// Echo messages and links
					if ("before" == $messages_position) {
						echo $messages_html;
					}
				?>
				<div class="sign-in-form">	
					<form action="<?php echo $login_form_url ?>" method="post" class="sign-in">
					
							<label for="user-name" class="input username">
								<span><?php echo $uifieldset->get_text("login_label_username"); ?></span>
								<input type="text" name="user-name" id="user-name" class="text-input" value="<?php echo wp_specialchars( strip_tags($_POST['user-name']), 1 ); ?>" />
							</label>
							
							<label for="password" class="input password">
								<span><?php _e('Password', 'verkkomuikku'); ?></span>
								<input type="password" name="password" id="password" class="text-input" />
							</label>
							
							<?php if ($remember_me) : ?>
							<label for="remember-me" class="remember-me">
								<input class="remember-me checkbox" name="remember-me" id="remember-me" type="checkbox" checked="checked" value="forever" />
								<span><?php _e('Remember me', 'verkkomuikku'); ?></span>
							</label>
							<?php else : ?>
								<input name="remember-me" id="remember-me" type="hidden" value="" />
							<?php endif; ?>
							
							
							<input type="submit" name="submit" id="login-submit" class="<?php echo $submit_button_class; ?>" value="<?php _e('Log in', 'verkkomuikku'); ?>" />
		
						<?php 
							if ( isset( $_REQUEST['redirect_to'] ) ) {
								$redirect_to = $_REQUEST['redirect_to'];
							} else {
								$redirect_to = home_url();
							}
						?>
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
						<input type="hidden" name="action" value="log-in" />
						<?php wp_nonce_field('verify_true_login','login_nonce_field'); ?>
						<?php do_action('login_form'); ?>
					</form>
				</div>
				<?php 
				// Echo messages and links
				if ("after" == $messages_position) {
					echo $messages_html;
				}
				
				echo $this->logged_in_log_out_template();
								
			} else { // Else User not logged in
				echo $this->logged_in_log_out_template();
			} // Endif User not logged in
			?>
		</div>
		<?php
		
		$form = ob_get_contents();
		ob_end_clean();
	
		// Set flag not to output the template twice on a page!
		$this->form_printed = true;
		return $form;
	}
	
	/**
	 * HTML for logout link instead ofthe login form.
	 * "You are logged in as %s, Logout"
	 * 
	 * @return string $html
	 */
	public function logged_in_log_out_template($separator = ' | ') {
		
		// Not logged in, return empty
		if (!is_user_logged_in())
			return '';
			
		global $user_ID;
		
		$vmfeu_user = get_userdata( $user_ID );
		
		$display_name = apply_filters('vmfeu_user_display_name', $vmfeu_user->display_name, $vmfeu_user);
		$user_profile_url = apply_filters('vmfeu_profile_url', esc_url(get_author_posts_url($vmfeu_user->ID))); //get_edit_profile_url($vmfeu_user->ID);

		$logged_in_log_out = '<p>';
		$logged_in_log_out .= sprintf( __('You are currently logged in as %1$s.', 'verkkomuikku'), '<a href="'.$user_profile_url.'" title="'.__("Edit profile", "verkkomuikku").'">'.$display_name.'</a>' );
		$logged_in_log_out .= "<br/>";
		$logged_in_log_out .= '<a href="'.wp_logout_url( home_url() ).'" title="'.__('Log out of this account', 'verkkomuikku').'">'.__('Log out &raquo;', 'verkkomuikku').'</a>';
		$logged_in_log_out .= '</p>';		
		return apply_filters('vmfeu_logged_in_log_out', $logged_in_log_out, $vmfeu_user, $user_profile_url, $args);
	}
}
global $vmfeu_login;
$vmfeu_login = new Vmfeu_Login();

/**
 * Returns the Login form template
 * 
 * @param array $args
 * @param string $content
 * 
 * @return string $html
 */
function vmfeu_front_end_login($args = array(), $content = "") {
	global $vmfeu_login;
	
	if (!is_a($vmfeu_login, 'Vmfeu_Login'))
		$vmfeu_login = new Vmfeu_Login();
		
	return $vmfeu_login->login_template($args, $content);
}