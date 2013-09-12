<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Class to handle user registration
 * 
 * @author verkkomuikku
 *
 */
class Vmfeu_Register {
	
	/**
	 * Contains user feedback messages
	 * 
	 * @var array
	 */
	public $messages = array();
	
	/**
	 * Constructor
	 * 
	 */
	public function __construct() {
		add_action('template_redirect', array(&$this, 'register_user'), 102);
		
		add_action('template_redirect', array(&$this, 'generate_messages'), 105);
	}
	
	/**
	 * Do registration on init
	 * 
	 * Registration is done before page loads.
	 * We need to pass some errors and other info forward until the form template is shown.
	 * 
	 * If Multisite, the whole multisite signup thing is bypassed, no multisite 
	 * activation etc. See wp-includes/ms-functions.php wpmu_activate_signup()
	 * and wpmu_create_user() for updates.
	 * WP 3.4.1
	 * 
	 * @uses owelapro_transfer_messages()
	 */
	function register_user() {
		global $uifieldset, $vmfeu;
	
		// Check that we are on registration page
		if (!is_page($vmfeu->registration_page) && !VMFEU_AJAX)
			return;
			
		// Form was sent with user info.
		// Create new user if all fields validate.
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'vmfeu_add_user' && wp_verify_nonce($_POST['register_nonce_field'],'verify_true_registration') ) {
			
			// Init fieldset only once
			if (!$uifieldset)
				$uifieldset = new Vmfeu_User_Info_Fieldset();
			
			// Validate fields
			$vmfeu->registration_errors = $uifieldset->validate();
			
			if (!is_wp_error($vmfeu->registration_errors)) {
				// Ok, no validation errors. Try to insert user
				$vmfeu->registration_mail_status = NULL;
				$vmfeu->user_registered = $uifieldset->insert_user($vmfeu->registration_mail_status);
				
				if (is_wp_error($vmfeu->user_registered)) {
					$vmfeu->registration_errors = $vmfeu->user_registered;
				
				// Redirect to login page, unless an admin just added a user.
				} elseif (!is_user_logged_in()) {
					
					if (apply_filters('vmfeu_show_login_link_after_registration', true)) {

						// Will add to owelapro messages to display the "Success" for the user
						$this->generate_messages();
						
						// Transfers the notifications over the redirect
						$key = owelapro_transfer_messages();
						
						// If user is not logged in (as is the case here), append the argument to be able
						// to trasnfer the messages.
						$url = add_query_arg(array('mskey' => $key), get_permalink($vmfeu->login_page));
						
						wp_redirect($url);
						exit();
					}
					
				}
			}
		}	
	}
	
	/**
	 * Generate userfeedback messages
	 * 
	 */
	public function generate_messages() {
		global $vmfeu, $uifieldset;

		// Don't bother
		if (!is_page($vmfeu->registration_page)) {
			return;
		}
		
		$messages = array();
		$message_class  = array();
		
		if ( is_user_logged_in() && !current_user_can( 'create_users' ) ) {
				
				// Display message for logged in users 
				// if they wind up into registration site (except admins)
				global $user_ID;
				$user = get_userdata( $user_ID );
				$display_name = apply_filters('vmfeu_user_display_name', $user->display_name, $user);
				
				$messages["already_logged_in"] = sprintf( __('You are logged in as <a href="%1$s" title="%2$s" rel="nofollow">%2$s</a>.  You don\'t need another account.', 'verkkomuikku'), get_edit_profile_url( $user->ID ), $display_name ).' <a href="'.wp_logout_url( get_permalink() ).'" title="'.__('Log out of this account', 'verkkomuikku').'" rel="nofollow">'.__('Logout &raquo;', 'verkkomuikku').'</a>';
				$message_class["already_logged_in"] = "logged-in";
				
		// Registration was successfull
		} elseif ( !is_wp_error($vmfeu->registration_errors) && $vmfeu->user_registered ) {

			// New user was created
			$user = get_userdata($vmfeu->user_registered);

			$display_name = apply_filters('vmfeu_user_display_name', $user->display_name, $user);
			
			if ( current_user_can( 'create_users' ) ) {
				$messages["registration_success"] = sprintf($uifieldset->get_text("registration_success_admin"), $display_name );
				$registration_url = wp_registration_url(); // get_permalink($vmfeu->registration_page);
				$messages["registration_success"] .= '<br/><a href="'.$registration_url.'" rel="nofollow">'.__("Add another user", 'verkkomuikku').'</a>';			
				
			// Display notice, that account must be activated before they can log in.
			} elseif($vmfeu->settings["require_account_activation"]) {
				$messages["registration_success"] = sprintf($uifieldset->get_text("registration_success_user_activation_required"), $display_name );
			} else {
				$messages["registration_success"] = sprintf($uifieldset->get_text("registration_success_user"), $display_name );
				
				// With Owela, the user is redirected to the login page so no need for the link
				$messages["registration_success"] .= '<br/>'.__("You may now log in with your username and password.", "verkkomuikku");
				/* 
				if (apply_filters('vmfeu_show_login_link_after_registration', true)) { 
	 				$login_url = get_permalink($vmfeu->login_page);
					$messages["registration_success"] .= '<br/><a href="'.$login_url.'" rel="nofollow">'.__("Log in with your new user account", 'verkkomuikku').'</a>';
				} else {
					$front_page_url = get_bloginfo('home');
					$messages["registration_success"] .= '<br/><a href="'.$front_page_url.'" rel="nofollow">'.__("To frontpage", 'verkkomuikku').'</a>';					
				}
				*/
				
			}
			$message_class["registration_success"] = "success";


			if(isset($_POST['send_password'])){
				if (true === $vmfeu->registration_mail_status) {
					
					if (current_user_can('create_users'))
						$messages["password_sent"] =  $uifieldset->get_text("registration_email_sent_admin");
						
					// No need for this I believe
					//else
					//	$messages["password_sent"] =  $uifieldset->get_text("registration_email_sent_user");
					
					$message_class["password_sent"] = "success";
					
				} elseif (false === $vmfeu->registration_mail_status) {			
					if (current_user_can('create_users'))
						$messages["password_sent"] = $uifieldset->get_text("registration_email_error_admin");
					else
						$messages["password_sent"] = $uifieldset->get_text("registration_email_error_user");

					$message_class["password_sent"] = "error";
				}
			}
			
		}		
		
		// Registration errors
		if ( is_wp_error($vmfeu->registration_errors) ) {
			$e = $vmfeu->registration_errors->get_error_messages();
			$messages["registration_errors"] = implode('<br/>', $e);
			$message_class["registration_errors"] = "error";
		}
		
		$this->messages["messages"] = $messages;
		$this->messages["message_class"] = $message_class;
		
		foreach ($messages as $name => $message) {
			$class = isset($message_class[$name]) ? $message_class[$name] : 'notice';
			owelapro_add_message($message, $class);
		}
	}
	
	/**
	 * Displays registration form
	 * 
	 * @param array $atts - standard WP shortcode atts, no use here
	 * @param string $content - standard WP shortcode $content. Put text that is displayed 
	 * above form, but not anymore when user gets feedback about the sent form.
	 * 
	 * @return string $form
	 */
	public function register_template($atts = array(), $content = ''){
		global $uifieldset, $vmfeu;
	
		$defaults = array(
			"show_messages" => false,
		);
		
		extract(wp_parse_args($atts, $defaults), EXTR_SKIP);
		
		// Let plugins change the $content
		$content = apply_filters('vmfeu_registration_welcome_message', $content);
		
		// Init fieldset only once
		if (!$uifieldset)
			$uifieldset = new Vmfeu_User_Info_Fieldset();
				
		// Check if users can register. 
		$registration = get_option( 'users_can_register' );			
		
		// This is shortcode so we should return the form instead of echoing
		ob_start(); 
	?>
	
		<div class="vmfeu_wrapper" id="vmfeu_register">
		
			<?php
			
			echo apply_filters('the_content', $content);
	
			// Start echoing the messages. The following notices are also
			// in the same <div wrapper
			?>
			<div class="messages">
			
				<?php 
				// Show messages
				if ($show_messages) {
					
					$messages = $this->messages["messages"];
					$message_class = $this->messages["message_class"];
					
					foreach ($messages as $name => $message) {
						$class = isset($message_class[$name]) ? $message_class[$name] : '';
						echo '<p class="'.$class.'">'.$message.'</p>';
					}

				}
				?>
			
				<?php if ( current_user_can( 'create_users' ) && $registration ) : ?>
					<p class="alert">
						<?php 
						_e('Users can register themselves or you can manually create users here.', 'verkkomuikku');
						if ($vmfeu->settings["require_account_activation"]) {
							echo "<br/>".__('Users have to activate their account before they can log in. Admin created accounts aren\'t required activation.', 'verkkomuikku');
						}
						?>
					</p>
				<?php elseif ( current_user_can( 'create_users' ) ) : ?>
					<p class="alert">
						<?php _e('Users cannot currently register themselves, but you can manually create users here.', 'verkkomuikku'); ?>
					</p>
				<?php elseif ( !current_user_can( 'create_users' ) && !$registration) : ?>
					<p class="alert">
						<?php _e('Only an administrator can add new users.', 'verkkomuikku'); ?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ((is_wp_error($vmfeu->registration_errors) || !$vmfeu->user_registered)) : ?>				
				<?php if ( ($registration && !is_user_logged_in()) || current_user_can( 'create_users' )) : ?>
					<div class="registration-form">	
						<?php $action_url = apply_filters('vmfeu_registration_action_url', wp_registration_url());  ?>
						<form method="post" id="vmfeu_add_user" class="user-forms" action="<?php echo $action_url; ?>">
							<?php $mandatory_nag = $uifieldset->print_fields(); ?>
							<?php 
							// Ask admin if he wants user password to be sent via email
							// Default is yes
							if ( current_user_can( 'delete_users' ) ) : ?>
								<dl>
									<dd>&nbsp;</dd>
									<dt>
										<input id="send_password" type="checkbox" name="send_password" value="1" <?php echo isset($_POST['send_password']) || !isset($_POST['vmfeu_add_user']) ? 'checked="checked"' : ''; ?>/>
										<label for="send_password"><?php _e(' Send these credentials via email.', 'verkkomuikku') ?></label>
									</dt>
								</dl>
							<?php 
							// Always send if user registers
							else: ?>
								<input type="hidden" name="send_password" value="1"/>
							<?php endif; ?>
								
							<dl>
								<dd><?php echo $mandatory_nag; ?></dd>
								<dt>
									<input name="vmfeu_add_user" type="submit" id="vmfeu_add_user_submit" class="submit button" value="<?php echo current_user_can( 'create_users' ) ? $uifieldset->get_text("registration_button_add_user") : $uifieldset->get_text("registration_button_register"); ?>" />
									<input name="action" type="hidden" id="action" value="vmfeu_add_user" />
								</dt>
							</dl>
							<?php wp_nonce_field('verify_true_registration','register_nonce_field'); ?>
						</form>
	 				</div>
				<?php endif; // current_user_can ('create_users') || $registration ?>
			<?php endif; // User has just registered ?>			
		</div>
	<?php
		$form = ob_get_contents();
		ob_end_clean();
		
		return $form;
	}	
	
}

global $vmfeu_register;
$vmfeu_register = new Vmfeu_Register();

/**
 * Shortcode handler to display registration form.
 * 
 * @param array $atts - standard WP shortcode atts, no use here
 * @param string $content - standard WP shortcode $content. Put text that is displayed 
 * above form, but not anymore when user gets feedback about the sent form.
 * 
 * @return string $form
 */
function vmfeu_front_end_register($atts = array(), $content = ''){
	global $vmfeu_register;
	
	if (!is_a($vmfeu_register, 'Vmfeu_Register'))
		$vmfeu_register = new Vmfeu_Register();
		
	return $vmfeu_register->register_template($atts, $content);

}
?>