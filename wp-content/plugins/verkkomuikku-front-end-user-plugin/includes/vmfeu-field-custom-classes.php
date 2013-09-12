<?php
/**
 * Verkkomuikku add on to profile builder
 * Input same fields into vmfeu-front-end-profile as are in vmfeu-front-end-register
 * 
 * TODO:
 * -password strenght indicator
 * -bot test
 * bugs
 * stripslashes thingy in usermeta related fields ( at least)
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * WP User_login field
 * 
 * @author teemu
 */
if (!class_exists('Vmfeu_Userlogin_Field')) {
class Vmfeu_Userlogin_Field extends Vmfeu_Text_Field {
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"name" 			=> "user_login", 
			"id" 			=> "user_login", 
			"title" 		=> __("Username", "verkkomuikku"), 		
			"help_text"		=> array("profile_update" => __("Username cannot be changed.", "verkkomuikku")),
			"mandatory"		=> true,
			"updateable"	=> false,
			"feedback" 		=> array("mandatory" => __("Please enter an username.", "verkkomuikku"), "invalid" => __("The username contains illegal characters. Allowed characters are letters, numbers and ' ', '_', '.'.", "verkkomuikku")),
			"filter_callback" => "sanitize_user", // if the value should be int, float etc, 
		);	
	}
	
	function validation_function($sanitized_user_login, &$errors) {
		$valid = true;
		
		$username_user_id = username_exists( $sanitized_user_login );
		// From wp-login.php register_new_user()
		// Check the username
		if ( $sanitized_user_login == '' ) {
			$errors->add( 'empty_username', __( 'Please enter an username.', 'verkkomuikku' ) );
			$valid = false;
		} elseif ( ! validate_username( $sanitized_user_login ) ) {
			$errors->add( 'invalid_username', __( 'The username is invalid because it uses illegal characters. Please enter a valid username.', 'verkkomuikku' ) );
			$valid = false;
		} elseif ( $username_user_id ) {
			global $vmfeu;
			// If user login is someone elses than user that is being edited
			if ($vmfeu->edit_user_id != $username_user_id ) {
				$errors->add( 'username_exists', __( 'This username is already registered, please choose another one.', 'verkkomuikku' ) );
				$valid = false;
			}
		}

		return $valid;
	}
}
}

/**
 * WP User_email field
 * 
 * @author teemu
 */
if (!class_exists('Vmfeu_Useremail_Field')) {
class Vmfeu_Useremail_Field extends Vmfeu_Text_Field {
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"name" 			=> "user_email", 
			"id" 			=> "user_email", 
			"title" 		=> __("Email", "verkkomuikku"), 		
			"mandatory"		=> true,
			"feedback" 		=> array("mandatory" => __("Please enter your email address.", "verkkomuikku"), "invalid" => __("Email address appears to be invalid.", "verkkomuikku")),
			"filter_callback" => "sanitize_user", // if the value should be int, float etc, 
		);	
	}
	
	function validation_function($user_email, &$errors) {
		$valid = true;
		
		$email_user_id = email_exists( $user_email );
		
		// From wp-login.php register_new_user()
		// Check the email
		if ( $user_email == '' ) {
			$errors->add( 'empty_email', __( 'Please type your e-mail address.', 'verkkomuikku' ) );
			$valid = false;
		} elseif ( ! is_email( $user_email ) ) {
			$errors->add( 'invalid_email', __( 'The email address isn&#8217;t correct.', 'verkkomuikku' ) );
			$valid = false;
		} elseif ( $email_user_id ) {
			global $vmfeu;
			// If email is some one elses than user that is being edited
			if ($vmfeu->edit_user_id != $email_user_id ) {
				$errors->add( 'email_exists', __( 'This email is already registered, please choose another one.', 'verkkomuikku' ) );
				$valid = false;
			}
		}

		return $valid;
	}
}
}

/**
 * WP Password field
 *  - special, generates and validates also Confirm password field
 * 
 * @author teemu
 */
if (!class_exists('Vmfeu_Userpassword_Field')) {
class Vmfeu_Userpassword_Field extends Vmfeu_Text_Field {
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"name" 			=> "user_pass", 
			"id" 			=> "user_pass",
			"title" 		=> __("Password", "verkkomuikku"), 
			"help_text"		=> array("registration" => "", "profile_update" => __("Write new password if you want to change it. Otherwise leave empty.", "verkkomuikku")),
			"mandatory"		=> true,
		);	
	}
	
	function is_mandatory() {
		if ($this->is_update)
			return false;
		return true;	
	}
		
	function validation_function($user_password, &$errors) {
		$valid = true;
		
		if ($this->is_update) {
			
			// Only check if both are the same, when either one is NOT empty
			if ((!empty($_POST[$this->name]) || !empty($_POST[$this->name."2"])) && $_POST[$this->name] != $_POST[$this->name."2"]) {
				$errors->add('password_mismatch', __('The passwords do not match.', 'verkkomuikku'));
				$valid = false;
			}
			
		} else {
			// Check that both password fields are filled and the same
			if (( empty( $_POST[$this->name] ) || empty( $_POST[$this->name."2"] )) || $_POST[$this->name] != $_POST[$this->name."2"]) {
				$errors->add('password_mismatch', __('The passwords do not match.', 'verkkomuikku'));
				$valid = false;
			}
		}
		
		return $valid;
	}
	
	function print_label_and_field($wrapper_html) {
		
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), $this->class);
		$class .= $this->is_valid ? "" : "not_valid";
		
		if ($this->has_help_text()) {
				echo $wrapper_html["label"]["start"];
					echo "&nbsp;";
				echo $wrapper_html["label"]["end"];		
				echo $wrapper_html["input"]["start"];				
				echo $wrapper_html["help"]["start"];
				echo $this->get_help_text();
				echo $wrapper_html["help"]["end"];
				echo $wrapper_html["input"]["end"];			
		}		
		
		echo $wrapper_html["label"]["start"];
			?><label for="<?php echo $this->id?>"><?php _e("Password", "verkkomuikku") ?><?php echo $this->mandatory ? '*':''; ?></label><?php 
		echo $wrapper_html["label"]["end"];		
		echo $wrapper_html["input"]["start"];
			?><input type="password" name="<?php echo $this->name ?>" id="<?php echo $this->id?>" class="<?php echo $class ?>" value=""/><?php 
		echo $wrapper_html["input"]["end"];
		echo $wrapper_html["label"]["start"];
			?><label for="<?php echo $this->id."2"?>"><?php _e("Confirm password", "verkkomuikku") ?><?php echo $this->mandatory ? '*':''; ?></label><?php 
		echo $wrapper_html["label"]["end"];
		echo $wrapper_html["input"]["start"];
			?><input type="password" name="<?php echo $this->name."2" ?>" id="<?php echo $this->id."2" ?>" class="<?php echo $class ?>" value=""/><?php 
		echo $wrapper_html["input"]["end"];				

		return true; 
	}
}
}

/**
 * WP Bot check field
 * 
 * @author teemu
 */
if (!class_exists('Vmfeu_Botcheck_Field')) {
class Vmfeu_Botcheck_Field extends Vmfeu_Text_Field {
	
	var $answer;
	var $key;
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"name" 			=> "botcheck",
			"value"			=> "",
			"title" 		=> "",
			"help_text"		=> "", // Use help text as question
			"answer"		=> "", // Use answer as correct answer, validation function should do normalling etc.
			"key"			=> "", // Key is hourly changing hash, used to get same field again for validation (this field is not persistent)
			"mandatory"		=> true,
			"feedback" 		=> array("mandatory" => __("Please answer the equation.", "verkkomuikku"), "invalid" => __("Please answer the equation.", "verkkomuikku")) 
		);	
	}
	
	public function print_label_and_field($wrapper_html) {
		echo $wrapper_html["field"]["start"];
			echo $wrapper_html["label"]["start"];
				?>
				<label for="<?php echo $this->id ?>"><?php echo $this->get_title(); ?></label>
				<?php 
			echo $wrapper_html["label"]["end"]; 
			if ($this->has_help_text()) {
				echo $wrapper_html["input"]["start"];						
				echo $wrapper_html["help"]["start"];
					echo $this->get_help_text();
				echo $wrapper_html["help"]["end"];
				echo $wrapper_html["input"]["end"];			
			}			
			echo $wrapper_html["input"]["start"];
				$this->print_field();	
			echo $wrapper_html["input"]["end"];

		echo $wrapper_html["field"]["end"];
	}
	
	public function validation_function($value, &$errors) {
		$valid = true;

		// Check if value is accepted answer
		$value = intval(trim($value));

		// No answer
		if (empty($value)) {
			$errors->add( 'vmfeu_empty_answer', $this->feedback["mandatory"] );
			$valid = false;			
		}
		
		// Wrong answer
		if ($value !== $this->answer) {
			$errors->add( 'vmfeu_wrong_answer', $this->feedback["invalid"] );
			$valid = false;
		}
		
		return $valid;
	}
	
	function get_key() { return $this->key; }
	function set_key($key) { $this->key = $key; }
}
}