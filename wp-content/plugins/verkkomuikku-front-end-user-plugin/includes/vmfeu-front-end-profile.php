<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Vmfeu_Profile {

	/**
	 * Flag to show if profile was updated
	 * 
	 * @var boolean
	 */
	public $profile_updated = false;
	
	/**
	 * Flag to show if password was updated
	 * 
	 * @var boolean
	 */
	public $password_updated = NULL;
	
	/**
	 * Flag if email was sent
	 * 
	 * @var boolean
	 */
	public $profile_update_mail_status = NULL;
	
	/**
	 * Errors that happened during profile update
	 * 
	 * @var array
	 */
	public $profile_update_errors = array();
	
	/**
	 * Contains user feedback messages
	 * 
	 * @var array
	 */
	public $messages = array();	
	
	public function __construct() {
		add_action('template_redirect', array(&$this, 'update_profile'), 102);
		
		add_action('template_redirect', array(&$this, 'generate_messages'), 103);
	}
	
	/**
	 * Update user data. Update in template_redirect, since then we should know we are 
	 * on profile page and all vmfeu attributes are set.
	 * 
	 * 
	 */
	public function update_profile(){
		global $uifieldset, $vmfeu;
	
		// Check we are on the profile page
		if (!is_page($vmfeu->profile_page))
			return;
			
		
		// If user is not logged in redirect to login page
		if (!is_user_logged_in()) {
			owelapro_add_message(__("Please log in first to see your profile.", "verkkomuikku"));
			$key = owelapro_transfer_messages();
			$url = add_query_arg(array('mskey' => $key, "redirect_to" => get_permalink($vmfeu->profile_page)), get_permalink($vmfeu->login_page));
			wp_redirect($url);
			exit();
		}
	
		// If form was submited, update profile 
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'update-user' && wp_verify_nonce($_POST['edit_nonce_field'],'verify_edit_user') ) { 
	
			// Init fieldset only once
			if (!$uifieldset)
				$uifieldset = new Vmfeu_User_Info_Fieldset();
						
			// Validates and resets values from $_POST
			$this->profile_update_errors = $uifieldset->validate();
			
			$edit_user_id = $vmfeu->edit_user_id;
	
			if (!is_wp_error($this->profile_update_errors)) {
				$this->profile_update_mail_status = NULL;
				$this->password_updated = NULL;
				
				$user = $uifieldset->update_user($edit_user_id, $this->profile_update_mail_status, $this->password_updated);
				
				if (is_wp_error($user)) {
					$this->profile_update_errors = $user;
				} else {
					$this->profile_updated = true;
				}
			}
		}
	}
	
	/**
	 * Generate userfeedback messages
	 * 
	 */
	public function generate_messages() {
		global $vmfeu;
		
		// Don't bother
		if (!is_page($vmfeu->profile_page)) {
			return;
		}
		
		$messages = array();
		$message_class  = array();
		
		// Check a flag if user was redirected to the profile.
 		if (isset($_GET["vmfeu_p_e"]) && $_GET["vmfeu_p_e"] == 1) {
 			// This user redirect to profile shouldn't happen that often,
 			// only if there are new background info fields, or if user
 			// registered to the site using Facebook or other registration process.
 			$messages["profile_redirect_nag"] = 
 				__("You have been redirected to your profile to fill some missing information.", "verkkomuikku").'<br/>'.
 				__("You may browse the site after you update your profile. Thank you!", "verkkomuikku");
 
 			$messages["profile_redirect_nag"] = apply_filters('vmfeu_redirected_to_profile_message', $messages["profile_redirect_nag"]);
 			$message_class["profile_redirect_nag"] = "success";	
 		}
	 		
	 		
		if ( is_wp_error($this->profile_update_errors) ) {
			$messages["profile_updated"] = implode('<br/>', $this->profile_update_errors->get_error_messages());
			$message_class["profile_updated"] = "error";
		} elseif ($this->profile_updated) {
			$messages["profile_updated"] = __('Profile updated.', 'verkkomuikku');
			$message_class["profile_updated"] = "success";
		}
			
		if($this->password_updated){
			$messages["password_updated"] = __("Password changed.", "verkkomuikku");
			$message_class["password_updated"] = "success";
			
			if (isset($_POST['send_password'])) {
				
				if (true === $this->profile_update_mail_status) {
					
					if ($vmfeu->edit_user_id != get_current_user_id()) {
						$messages["password_updated"] .= " ".__('An email containing new password was sent to the user.', 'verkkomuikku');
					} 
					/* Owela filters replace the standard email which doesn't contain the password nomore.
					else {
						$messages["password_updated"] .= " ".__('An email containing your new password was sent to your email address.', 'verkkomuikku');	
					}*/
					
				} elseif (false === $this->profile_update_mail_status) {

					if ($vmfeu->edit_user_id != get_current_user_id()) {
						$message_class["password_updated"] = "error";
						$messages["password_updated"] .= " ".__('We tried to send the notification email but an error occured. Please notify the user that her password has changed (with your email client) or try again.', 'verkkomuikku');

					} 
					/* Owela filters replace the standard email which doesn't contain the password nomore.
					else {
						$messages["password_updated"] .= " ".__('We tried to send you a notification email but an error occured.', 'verkkomuikku');
					}
					*/
					
					// Show admin error
					$userdata = get_userdata($vmfeu->edit_user_id);
					owelapro_add_message(sprintf(__("Couldn't send 'password changed' email to user %s, email %s!", "verkkomuikku"), $userdata->user_login, $userdata->user_email), 'admin_error');
				}
			} else {
				
				// Add the notification for admin. The user should always get the new password as email (cannot opt out).
				if ($vmfeu->edit_user_id != get_current_user_id()) {
					$messages["password_updated"] .= " ".__('Notification of password change was NOT sent to the user via email. If you wish to send the password, please check the checkbox at the bottom of the profile form and change the password again.', 'verkkomuikku');
				}
			
			}
		}
				
		$this->messages["messages"] = $messages;
		$this->messages["message_class"] = $message_class;
		
		foreach ($messages as $name => $message) {
			$class = isset($message_class[$name]) ? $message_class[$name] : 'notice';
			owelapro_add_message($message, $class);
		}				

	}
	
	/**
	 * Display the profile template
	 *  
	 * @param array $atts
	 * @param string $content
	 * 
	 * @return string $form
	 */
	public function profile_template($atts = array(), $content = '') {
		global $uifieldset, $vmfeu, $current_user;
	
		// Get user info. 
		if (!$current_user)
			$current_user = wp_get_current_user();

		// Die if not logged in.
		if (!$current_user) {
			wp_die(__("Log in first to access profile..", "verkkomuikku"));
		}
		
		$defaults = array(
			"show_messages" => false,
		);
		
		extract(wp_parse_args($atts, $defaults), EXTR_SKIP);		
		
		// Init fieldset only once
		if (!$uifieldset)
			$uifieldset = new Vmfeu_User_Info_Fieldset();
		
		// Populate from database - checks if current user can edit 
		$uifieldset->populate();
		
	 	// This is shortcode, we should return the output, not echo it
		ob_start();
	?>
		<div class="vmfeu_wrapper" id="vmfeu_profile">
			<?php do_action('vmfeu_profile_page'); ?>
			
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
			</div>
			<div class="profile-update-form">
				<form method="post" id="edituser" class="user-forms" action="<?php the_permalink(); ?>">
					<?php do_action('vmfeu_profile_form'); ?>
					<?php $mandatory_nag = $uifieldset->print_fields(); ?>
					
					<?php 
					// Ask admin if he wants user password to be sent via email
					if ( current_user_can( 'delete_users' ) ) : ?>
						<dl>
							<dd>&nbsp;</dd>
							<dt>
								<input id="send_password" type="checkbox" name="send_password" value="1" <?php echo isset($_POST['send_password']) ? 'checked="checked"' : ''; ?>/>
								<label for="send_password"><?php _e(' Send these credentials via email.', 'verkkomuikku') ?></label>
							</dt>
						</dl>
					<?php 
					// Always send if user updates themselves
					else: ?>
						<input type="hidden" name="send_password" value="1"/>
					<?php endif; ?>
									
					<dl>
						<dd><?php echo $mandatory_nag; ?></dd>
						<dt>
							<input name="updateuser" type="submit" id="updateuser" class="submit button" value="<?php _e('Update', 'verkkomuikku'); ?>" />
							<input name="action" type="hidden" id="action" value="update-user" />
						</dt>
					</dl>
					<?php 
					// If editing other user (admin), add the edit_id as hidden field
					if ($vmfeu->edit_user_id != $current_user->ID) : ?>
						<input type="hidden" name="edit_id" id="edit_user_id" value="<?php echo $vmfeu->edit_user_id; ?>"/>
					<?php endif; ?>
					<?php wp_nonce_field('verify_edit_user','edit_nonce_field'); ?>
				</form>
			</div>
		</div>
	<?php
		$form = ob_get_contents();
		ob_end_clean();
		
		return $form;
	}	
	
}

global $vmfeu_profile;
$vmfeu_profile = new Vmfeu_Profile();

/**
 * Shortcode handler that shows my profile editing form
 * 
 * @param array $atts
 * @param string $content
 * 
 * @return string $form
 */
function vmfeu_front_end_profile_info($atts = array(), $content = '') {
	global $vmfeu_profile;
	
	if (!is_a($vmfeu_profile, 'Vmfeu_Profile'))
		$vmfeu_profile = new Vmfeu_Profile();
		
	return $vmfeu_profile->profile_template($atts, $content);
}

?>