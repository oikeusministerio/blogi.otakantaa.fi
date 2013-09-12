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
 * Class for a user info field, inherited by different field types
 * 
 * @author teemu
 *
 */
class Vmfeu_User_Info_Field {

	var $name;
	var $id;
	var $value;
	var $default_value;
	var $class;
	var $title;
	var $help_text;
	var $mandatory;
	var $updateable;
	var $feedback;
	var $filter_callback;
	var $validate_callback;
	var $validate_regex;
	var $menu_order;
	var $is_update;
	var $is_valid;
	var $include_in_email;
	var $other_input;
	
	function __construct() {}
	
	function default_args() {
		$defaults = array(
			"name" 				=> "",
			"id"				=> "",
			"value"				=> "",
			"class"				=> "",		
			"title" 			=> "Field", 	// Maybe string or array("registration" => "title for registering page", "profile_update" => "title for profile update page")
			"help_text"			=> "", 			// You can write help or guide text that displays besides the input field. You can use array("registration" => "", "profile_update" => "") similarly as with title
			"mandatory" 		=> false,
			"updateable"		=> true, 		// Make input field disabled if it cannot be edited when updating profile
			"feedback" 			=> array("mandatory" => __("Please enter %s.", "verkkomuikku"), "invalid" => __("%s contains illegal characters.", "verkkomuikku")),
			"filter_callback" 	=> "esc_attr", 	// Sanitization filter: if the value should be int, float etc, by defualt esc_attr is used
			"validate_callback" => "", 			// Validation callback, for example wp function is_email() 
			"validate_regex" 	=> "", 			// Validation regexp, for example phone or post number
			"menu_order" 		=> "", 			// Can override display order
			"is_update"			=> false,		// Set to true if in profile update page
			"is_valid"			=> true,		// Can be used to add css into input field if not valid
			"include_in_email"	=> array("registration" => false, "profile_update" => false), // Include this field in user emails
			"other_input"		=> array(),		// Slave input fields, such as radio select "Somethign else, what?" text field. Add the text field into the Vmfeu_fieldset fields as well, provide the name here into the array as value (key is the radio / checkbox value label pairs key)
			
		);
		
		return $defaults;
	}
	
	function set_attributes($args) {
		$args = wp_parse_args($args, $this->default_args());
		
		foreach ($args as $a => $value) {
			$this->$a = $value;
			
			// Set default value
			if ('value' == $a)
				$this->default_value = $value;
		}
		
		if (empty($this->name))
			wp_die("Error, no name set in user info field");
			
		// Use name as id
		if (empty($this->id))
			$this->id = $this->name;
			
		// Detect if page is profileupdate page
		// NOTE: Normally these Vmfeu_User_Info_Field objects are created 
		// withing page template (using shortcode) so is_page() should work. 
		// If fields are generated elsewhere (such as in wp-admin) 
		// is_page() doesn't work.
		global $vmfeu;
		
		$this->is_update = apply_filters('vmfeu_is_update_page', is_page($vmfeu->profile_page));
	}
	
	/**
	 * Validates field. 
	 * First do sanity check, then check if field is mandatory.
	 * After that call validation_function that can be overriden.
	 * Danger: before calling this function you should always check nonce
	 * 
	 * @param WP_error &$errors
	 * @return boolean $valid 
	 */
	function validate(&$errors, $value = "") {

		// Value comes by $_POST... 
		if ($value == "") {
			$value = $_POST[$this->name];
			
			// ...unless field updating is disabled
			// Just return true = valid
			if ($this->is_update && !$this->updateable) {
				return true;
			}

		}
		
		// First run sanity check filter, forexample intval, floatval etc. default is esc_attr
		// Danger! should always make a sanity check first!
		if (!empty($this->filter_callback))
			$value = call_user_func($this->filter_callback, $value);
		
		$value = trim($value);
		
		// Check if mandatory
		if ($this->is_mandatory() && empty($value)) {
			$errors->add($this->name.'_mandatory', sprintf(__("%s is mandatory", "verkkomuikku"), $this->get_title()));
			$this->is_valid = false;
			$this->value=""; // Might be populated from database, update tried to send empty
			return false; 
		}
		
		// Check if value is valid
		$valid = $this->validation_function($value, $errors);
		
		$this->is_valid = $valid;
		
		// set value
		$this->value = $value;
		
		return $valid;
	}
	
	/**
	 * Child classes can override this to provide different validation
	 * on profile update. Needed forexample for user password field
	 * 
	 * @param WP_error $errors
	 */
	function validate_on_profile_update(&$errors, $value = "") { return $this->validate($errors, $value); }
	
	/**
	 * Validate value with callback function or regexp or override in child class
	 * 
	 * @param $value - user submitted value
	 * @param $errors - $this->validation_function($value, $errors);
	 * @return boolean $valid
	 */
	function validation_function($value, &$errors) {
		$valid = true;
		
		// Use validation callback or regexp
		if (!empty($this->validate_callback)) {
			$valid = call_user_func($this->validate_callback, $value);
		} elseif (!empty($this->validate_regex)) {
			
			// Allow empty if the field is not mandatory
			if (!$this->mandatory && "" == trim($value))
				$valid = true;
			else
				$valid = preg_match($this->validate_regex, $value);
		}

		if (!$valid) {
			$message = isset($this->get_feedback["invalid"]) ? $this->get_feedback["invalid"] : sprintf(__("%s value is not valid.", "verkkomuikku"), $this->get_title());
			$errors->add($this->name.'_invalid', $message);
		}

		return $valid;		
	}
	
	/**
	 * Normally label and field are printed from Vmfeu_User_Info_Fieldset
	 * but you can output custom html by overriding print_label_and_field in child
	 * class.
	 * 
	 * @param array $wrapper_html
	 * 
	 * @return boolean $printed - return false and fields get printed as others, true if you have custom print function in child class
	 */
	function print_label_and_field($wrapper_html) { return false; }
	
	function print_field() { die("User Info fields: You should override this method."); } // Input field html

	function get_value() { return $this->value; }
	
	/**
	 * Allow plugins to modify value when sending user an email.
	 * @return $value
	 */
	function get_value_for_email() {
		return apply_filters('vmfeu_get_field_value_for_email_'.$this->name, $this->get_value(), $this);		
	}
	
	/**
	 * Sets value for this field. Value goes first
	 * into validation function. Validate function sets $this->value
	 * if value validates.
	 * 
	 * @param unknown_type $value
	 * @return boolean $valid Return true if value is valid
	 */
	function set_value($value) { return $this->validate(new WP_error(), $value); }
	
	/**
	 * Get field title. There might be different title for registration and update.
	 * Add mandatory marker and provide filters.
	 * 
	 * @return string $title
	 */
	function get_title() { 
		// Title may change depending if is registration or profile update
		$key = $this->is_update ? "profile_update" : "registration";
		$title = is_array($this->title) ? $this->title[$key] : $this->title;
		$title = apply_filters('the_title', $title);
		$mandatory_indicator = apply_filters("vmfeu_mandatory_indicator", '*');

		return $this->mandatory ? $title.$mandatory_indicator : $title; 
	}
	
	/**
	 * Allow plugins to modify title when sending user an email.
	 * 
	 * @return string $value
	 */	
	function get_title_for_email() {
		return apply_filters('vmfeu_title_for_email_'.$this->name, $this->get_title());
	}
	
	function get_name() { return $this->name; }
	
	function get_id() {	return $this->id; }
	
	/**
	 * Override this to set special conditions when field is not mandatory
	 *  - for example password is not mandatory when updating profile
	 *  
	 * @return boolean $is_mandatory
	 */
	function is_mandatory() { return $this->mandatory ? true : false; }
	
	function get_feedback($type) { return isset($this->feedback[$type]) ? $this->feedback[$type] : $this->feedback["mandatory"]; }
	
	/**
	 * Check if the field has a help text
	 * 
	 * @return boolean $has_help_text
	 */
	function has_help_text() {
		$key = $this->is_update ? "profile_update" : "registration";

		if (is_array($this->help_text)) { 
			if (isset($this->help_text[$key]) && !empty($this->help_text[$key])) 
				return true;
			
			return false;
		}
		
		if ($this->help_text != "")
			return true;

		return false;
	}
	
	/**
	 * Return help text for the field.
	 * Text may be different for registration and profile update
	 *
	 * @return string $help_text
	 */
	function get_help_text() { 
		$key = $this->is_update ? "profile_update" : "registration";
		$help_text = is_array($this->help_text) ? $this->help_text[$key] : $this->help_text;
		return $help_text; 
	}
}

/**
 * Textfield class
 * HTML input[type=text]
 * 
 * @author teemu
 *
 */
class Vmfeu_Text_Field extends Vmfeu_User_Info_Field {
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"value"			=> "",
			"title" 		=> "Text field", 
			"feedback" 		=> array("mandatory" => __("Please enter %s.", "verkkomuikku"), "invalid" => __("%s contains illegal characters.", "verkkomuikku")),
			"filter_callback" => "strip_tags", // if the value should be int, float etc, 
		);	
	}
	
	function print_field() {
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), "");
		$class .= $this->is_valid ? "" : "not_valid"; 
		?>
			<input type="text" name="<?php echo $this->name ?>" value="<?php echo $this->value ?>" id="<?php echo $this->id ?>" class="<?php echo $class ?>" <?php echo $this->is_update && !$this->updateable ? 'disabled="disabled"' : ''; ?>/>
		<?php 
	}
}

/**
 * Textarea class
 * HTML textarea
 * 
 * @author teemu
 *
 */
class Vmfeu_Textarea_Field extends Vmfeu_User_Info_Field {
	
	var $cols = 40;
	var $rows = 4;
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"title" 			=> "Textarea", 
			"filter_callback"	=> "wp_kses_data", 
		);	
	}
	
	function print_field() {
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), $this->class);
		$class .= $this->is_valid ? "" : "not_valid";
		
		$rows_n_cols = '';
		if ($this->rows)
			$rows_n_cols .= 'rows="'.intval($this->rows).'"';
		if ($this->cols)
			$rows_n_cols .= ' cols="'.intval($this->cols).'"';
			
		?>
			<textarea id="<?php echo $this->id ?>" name="<?php echo $this->name ?>" class="<?php echo $class ?>" <?php echo $rows_n_cols; ?>><?php echo $this->value ?></textarea>
		<?php 
	}
}

/**
 * Hidden field class
 * HTML input[type=hidden]
 * 
 * @author teemu
 *
 */
class Vmfeu_Hidden_Field extends Vmfeu_User_Info_Field {
	var $show_title;
	var $show_value;
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"value"			=> "",
			"title" 		=> "", 
			"feedback" 		=> "",
			"help_text"		=> "",
			"show_label"	=> array("registration" => false, "profile_update" => false), // Visual of the hidden field
			"show_value"	=> array("registration" => false, "profile_update" => false), // Visual of the hidden field
			"filter_callback" => "esc_attr", // if the value should be int, float etc, 
		);	
	}
	
	function print_field() {
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), "");
		?>
			<input type="hidden" name="<?php echo $this->name ?>" value="<?php echo $this->value ?>" id="<?php echo $this->id ?>" class="<?php echo $class ?>" />
		<?php 
	}
	
	function print_label_and_field($wrapper_html) {
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), $this->class);
		
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
		
		if ($this->is_label_visible()) {
			echo $wrapper_html["label"]["start"];
			?><label><?php echo apply_filters('the_title', $this->title) ?></label><?php
			echo $wrapper_html["label"]["end"];		
		}
		echo $wrapper_html["input"]["start"];
		if ($this->is_value_visible()) {
			?><p><?php echo $this->value ?></p><?php
		}
			$this->print_field(); 
		echo $wrapper_html["input"]["end"];

		return true; 
	}
	
	/**
	 * Label of a hidden field may be shown to users
	 * Can be set separately for registration and profile update.
	 * 
	 * @return boolean $show_label
	 */
	public function is_label_visible() {
		$key = $this->is_update ? "profile_update" : "registration";
		
		if (is_array($this->show_label)) {
			if (isset($this->show_label[$key]))
				return $this->show_label[$key];
			else
				return false;
		}
		
		return $this->show_label;
	}
	
	/**
	 * Value of a hidden field may be shown to users
	 * Can be set separately for registration and profile update.
	 * 
	 * @return boolean $show_value
	 */
	public function is_value_visible() {
		$key = $this->is_update ? "profile_update" : "registration";
		
		if (is_array($this->show_value)) {
			if (isset($this->show_value[$key]))
				return $this->show_value[$key];
			else
				return false;
		}
		
		return $this->show_value;
	}	
}

/**
 * Radio button field class
 * HTML input[type=radio]
 * 
 * @author teemu
 *
 */
class Vmfeu_Radio_Field extends Vmfeu_User_Info_Field {
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"value_label_pairs"	=> array(),
			"selected"			=> "", // Value of value label pairs
			"title" 			=> "",
			"feedback" 			=> "",
			"help_text"			=> "",
			"filter_callback" 	=> "esc_attr", // if the value should be int, float etc,
			"other_input"		=> array(), // Option 'something else, what?' array with the value as key to attach into any of the value_label_pairs
		);	
	}
	
	/**
	 * Print all radio values
	 * 
	 */
	function print_field() {
		global $uifieldset;
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), "");
		
		$keynum = 0;
		foreach ($this->value_label_pairs as $key => $vl) {
				$keynum++;
				$value = $vl["value"];
				$label = apply_filters("the_title", $vl["label"]);
				$id = $this->id."_".$key;
				
				// THe other input is a OwelaPro_Form_Field!
				if (isset($this->other_input[$key])) {
					$other_field = $uifieldset->get_field($this->other_input[$key]);
					ob_start();
					$other_field->print_field();
					$other_input = ob_get_contents();
					ob_end_clean();
				} else {
					$other_input = '';
				}
		?>
			<input type="radio" name="<?php echo $this->name ?>" value="<?php echo $value ?>" id="<?php echo $id ?>" class="<?php echo $class ?>" <?php echo $this->selected == $value ? 'checked="checked"' : ''?> <?php echo $this->is_update && !$this->updateable ? 'disabled="disabled"' : ''; ?>/>
			<label for="<?php echo $id ?>" <?php echo !$this->is_valid ? 'class="not_valid"' : '' ?>><?php echo $label ?></label><?php echo '' != $other_input ? '<div class="other">'.$other_input.'</div>' : ''; echo $keynum < count($this->value_label_pairs) ? "<br/>" : ""; ?>
		<?php
		}
	}
	
	/**
	 * Print both label and fields
	 * 
	 * @param array $wrapper_html
	 * @param object $form
	 */
	function print_label_and_field($wrapper_html) {
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), $this->class);
		
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
			?><label><?php echo $this->get_title() ?></label><?php
		echo $wrapper_html["label"]["end"];		
		echo $wrapper_html["input"]["start"];
			$this->print_field();
		echo $wrapper_html["input"]["end"];

		return true; 
	}
	
	/**
	 * Validate value, check that value is in value label pairs. 
	 * Mandatory check is done in parent class.
	 * 
	 * @param $value - user submitted value
	 * @param $errors - $this->validation_function($value, $errors);
	 * @return boolean $valid
	 */
	function validation_function($value, &$errors) {
		$valid = false;
		
		// Check that value is in value_label_pairs
		foreach ($this->value_label_pairs as $key => $vlp) {
			// Found it!
			if ($vlp["value"] == $value) {
				$valid = true;
				// Set as selected
				$this->selected = $value;

				// Check if the selected item has other_input,
				// if has, set the pther_field as mandatory.
				if (!empty($this->other_input) && isset($this->other_input[$key])) {
					global $uifieldset;
					$uifieldset->fields[$this->other_input[$key]]->mandatory = true;
				}
				break;
			}
		}
		
		if (!$valid) {
			$message = isset($this->get_feedback["invalid"]) ? $this->get_feedback["invalid"] : sprintf(__("%s value is not valid.", "verkkomuikku"), $this->get_title());
			$errors->add($this->name.'_invalid', $message);
		}

		return $valid;
	}

	/**
	 * Allow plugins to modify value when sending user an email.
	 * 
	 * @return $value
	 */
	function get_value_for_email() {
		
		// Checkbox values are arrays
		if (isset($this->value_label_pairs[$this->get_value()]))
			$value = $this->value_label_pairs[$this->get_value()]["label"];
		else
			$value = $this->get_value();
		
		return apply_filters('vmfeu_get_field_value_for_email_'.$this->name, $value, $this);
	}		
}

/**
 * Dropdown field class
 * HTML <select>
 * 
 * @author teemu
 *
 */
class Vmfeu_Dropdown_Field extends Vmfeu_User_Info_Field {
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"value_label_pairs"	=> array(),
			"selected"			=> "", // Value of value label pairs
			"title" 			=> "",
			"feedback" 			=> "",
			"help_text"			=> "",
			"filter_callback" 	=> "esc_attr", // if the value should be int, float etc, 
		);
	}
	
	/**
	 * Print the dropdown box
	 * 
	 */
	function print_field() {
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), "");
		$class .= $this->is_valid ? "" : "not_valid";
		?>
			<select id="<?php echo $id ?>" name="<?php echo $this->name ?>" class="<?php echo $class; ?>" <?php echo $this->is_update && !$this->updateable ? 'disabled="disabled"' : ''; ?>>
				<?php 
				foreach ($this->value_label_pairs as $key => $vl) :
						$value = $vl["value"];
						$label = apply_filters("the_title", $vl["label"]);
						$id = $this->id."_".$key;
				
				?>		
				<option value="<?php echo $value; ?>" <?php echo $this->selected == $value ? 'selected="selected"' : ''?>><?php echo $label; ?></option>			
				<?php endforeach; ?>
			</select>
		<?php 
	}
	
	/**
	 * Print both label and fields
	 * 
	 * @param array $wrapper_html
	 */
	function print_label_and_field($wrapper_html) {
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), $this->class);
		
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
			?><label><?php echo $this->get_title() ?></label><?php
		echo $wrapper_html["label"]["end"];		
		echo $wrapper_html["input"]["start"];
			$this->print_field();
		echo $wrapper_html["input"]["end"];

		return true; 
	}
	
	/**
	 * Validate value, check that value is in value label pairs. 
	 * Mandatory check is done in parent class.
	 * 
	 * @param $value - user submitted value
	 * @param $errors - $this->validation_function($value, $errors);
	 * @return boolean $valid
	 */
	function validation_function($value, &$errors) {
		$valid = false;
		
		// Check that value is in value_label_pairs
		foreach ($this->value_label_pairs as $vlp) {
			// Found it!
			if ($vlp["value"] == $value) {
				$valid = true;
				// Set as selected
				$this->selected = $value;
				continue;
			}
		}
		
		if (!$valid) {
			$message = isset($this->get_feedback["invalid"]) ? $this->get_feedback["invalid"] : sprintf(__("%s value is not valid.", "verkkomuikku"), $this->get_title());
			$errors->add($this->name.'_invalid', $message);
		}

		return $valid;
	}
	
	/**
	 * Allow plugins to modify value when sending user an email.
	 * 
	 * @return $value
	 */
	function get_value_for_email() {
		
		// Checkbox values are arrays
		if (isset($this->value_label_pairs[$this->get_value()]))
			$value = $this->value_label_pairs[$this->get_value()]["label"];
		else
			$value = $this->get_value();
			
		return apply_filters('vmfeu_get_field_value_for_email_'.$this->name, $value, $this);		
	}	
}

/**
 * Checkbox field class
 * HTML input[type=checkbox]
 * 
 * Checkbox fields are sent / validated / saved as arrays
 *  
 * @author teemu
 *
 */
class Vmfeu_Checkbox_Field extends Vmfeu_User_Info_Field {
	
	var $other_input;
	var $not_empty;
	
	function __construct($args) {
		$defaults = $this->get_defaults();
		$args = wp_parse_args( $args, $defaults );
		$this->set_attributes($args);
	}
	
	function get_defaults() {
		return array(
			"value_label_pairs"	=> array(), // Should be associative array since checkboxes will be sent as arrays eg. array("terms_ok" => array("value" => "1", "label" => "Accept terms"));
			"mandatory"			=> array(), // Give mandatory field keys from value_label_pairs array
			"not_empty"			=> false,	// Require that at least one box must be checked
			"selected"			=> array(), // Array of Keys of value label pairs
			"title" 			=> "",
			"feedback" 			=> "",
			"help_text"			=> "",
			"filter_callback" 	=> "esc_attr", // if the value should be int, float etc, 
			"other_input"		=> array(),
		);	
	}
	
	/**
	 * Get field title. There might be different title for registration and update.
	 * Checkboxes have mandatory marker at each checkbox, not in title.
	 * If at least one has to be cheked then there is the mandatory indicator.
	 * 
	 * @return string $title
	 */
	function get_title() { 
		// Title may change depending if is registration or profile update
		$key = $this->is_update ? "profile_update" : "registration";
		$title = is_array($this->title) ? $this->title[$key] : $this->title;
		$title = apply_filters('the_title', $title);
		$mandatory_indicator = apply_filters("vmfeu_mandatory_indicator", '*');

		return $this->not_empty ? $title.$mandatory_indicator : $title; 
	}
	
	/**
	 * Print all checkbox values.
	 * Keys should be strings instead of regular index (eg. accosiative array)
	 * Checkbox fields are sent / validated / saved as arrays
	 * 
	 */
	function print_field() {
		global $uifieldset;
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), "");

		$mandatory_indicator = apply_filters("vmfeu_mandatory_indicator", '*');
		
		$i = 0;
		foreach ($this->value_label_pairs as $key => $vl) {
				$value = $vl["value"];
				$label = apply_filters("the_title", $vl["label"]);
				$id = $this->id."_".$key;
				$mandatory = in_array($key, (array)$this->mandatory);
				
				// THe other input is a OwelaPro_Form_Field!
				if (isset($this->other_input[$key])) {
					$other_field = $uifieldset->get_field($this->other_input[$key]);
					ob_start();
					$other_field->print_field();
					$other_input = ob_get_contents();
					ob_end_clean();
				} else {
					$other_input = '';
				}			
		?>
			<input type="checkbox" name="<?php echo $this->name ?>[<?php echo $key ?>]" value="<?php echo $value ?>" id="<?php echo $id ?>" class="<?php echo $class ?>" <?php echo in_array($key, (array)$this->selected) ? 'checked="checked"' : ''?> <?php echo $this->is_update && !$this->updateable ? 'disabled="disabled"' : ''; ?>/>
			<label for="<?php echo $id ?>" <?php echo !$this->is_valid ? 'class="not_valid"' : '' ?>><?php echo $mandatory ? $mandatory_indicator." ": ""; echo $label ?></label><?php echo '' != $other_input ? '<div class="other">'.$other_input.'</div>' : ''; echo $i < (count($this->value_label_pairs)-1) ? "<br/>" : ""; ?>
		<?php
			$i++;
		} 
	}
	
	/**
	 * Print both label and fields
	 * 
	 * @param array $wrapper_html
	 */
	function print_label_and_field($wrapper_html) {
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), $this->class);
		$mandatory_indicator = apply_filters("vmfeu_mandatory_indicator", '*');
		
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
			?><label><?php echo $this->get_title(); ?></label><?php
		echo $wrapper_html["label"]["end"];		
		echo $wrapper_html["input"]["start"];
			$this->print_field();
		echo $wrapper_html["input"]["end"];

		return true; 
	}

	/**
	 * Validates field. 
	 * First do sanity check, then check if field is mandatory.
	 * After that call validation_function that can be overriden.
	 * Danger: before calling this function you should always check nonce
	 * 
	 * @param WP_error &$errors
	 * @param array $value
	 * @return boolean $valid 
	 */
	function validate(&$errors, $value = "") {
		// Value comes by $_POST... 
		if ($value == "") {
			$value = $_POST[$this->name];
			
			// ...unless field updating is disabled
			// Just return true = valid
			if ($this->is_update && !$this->updateable) {
				return true;
			}
		}
		
		// If all checkboxes are unchecked, value is empty
		if (!is_array($value)) {
			$value = array();
			
			if ($this->not_empty) {
				$errors->add($this->name.'_empty', sprintf(__("%s - Please select at least one option.", "verkkomuikku"), $this->get_title()));
				$this->is_valid = false;
			}
		}
			
			
		// First run sanity check filter, forexample intval, floatval etc. default is esc_attr
		// Also, drop all values that are not in value_label_pairs!
		// Danger! should always make a sanity check first!
		if (!empty($this->filter_callback)) {
			// We have an array so loop all values
			foreach ($value as $key => $val) {
				// If user tried to send stuff that is not in the actual field,
				// unset..
				if (!isset($this->value_label_pairs[$key])) {
					unset($value[$key]);
					continue;
				}
				$value[$key] = call_user_func($this->filter_callback, $val);
			}
		}
		
		// Check if mandatory
		// If checkbox is not checked, the value[$field] is not set
		foreach ($this->mandatory as $key) {
			if (!isset($value[$key])) {
				$errors->add($this->name.'_'.$key.'_mandatory', sprintf(__("%s is mandatory", "verkkomuikku"), $this->value_label_pairs[$key]["label"]));
				$this->is_valid = false;
			}
		}
		
		$this->value = $value; // Might be populated from database
		
		// Some empty mandatory fields
		if (!$this->is_valid)
			return false; 
		
		// Check if value is valid
		$valid = $this->validation_function($value, $errors);
		
		$this->is_valid = $valid;
		
		// set value
		$this->value = $value;
		
		return $valid;
	}
		
	/**
	 * Validate values, check that sent values are in value label pairs or empty. 
	 * 
	 * @param array $value - user submitted value, checkbox fields are always sent as arrays
	 * @param $errors - $this->validation_function($value, $errors);
	 * 
	 * @return boolean $valid
	 * 
	 */
	function validation_function($value, &$errors) {
		global $uifieldset;
		
		$valid = true;
		
		// Check that value is in value_label_pairs
		foreach ($this->value_label_pairs as $key => $vlp) {
			// Value may not be set at all. Mandatory
			// check has already validated mandatory fields.
			if (!isset($value[$key])) {
				continue;
				
			// Is set and is the value it should be
			} else if ($vlp["value"] == $value[$key]) {
				// Set as selected
				if (!is_array($this->selected))
					$this->selected = array($key);
				else
					$this->selected[] = $key;
					
				// Check if the selected item has other_input,
				// if has, set the pther_field as mandatory.
				if (!empty($this->other_input) && isset($this->other_input[$key])) {
					$uifieldset->fields[$this->other_input[$key]]->mandatory = true;
				}
					
				continue;

			// user tried to hoax
			} else {
				$valid = false;
				break;
			}
		}
		
		if (!$valid) {
			$message = isset($this->get_feedback["invalid"]) ? $this->get_feedback["invalid"] : sprintf(__("%s value is not valid.", "verkkomuikku"), $this->get_title());
			$errors->add($this->name.'_invalid', $message);
		}

		return $valid;
	}
	
	/**
	 * Allow plugins to modify value when sending user an email.
	 * 
	 * @return $value
	 */
	function get_value_for_email() {
		
		// Checkbox values are arrays
		$value = "";
		foreach ((array)$this->get_value() as $label => $val) {
			$value .= strip_tags($this->value_label_pairs[$label]["label"]).", ";
		}
		$value = trim($value, ", ");
		return apply_filters('vmfeu_get_field_value_for_email_'.$this->name, $value, $this);		
	}	
}

/**
 * Main class to display and validate user info fields
 * 
 * @author teemu
 *
 * TODO
 * Set/Get fields from database
 * Admin panel to set fields
 */
class Vmfeu_User_Info_Fieldset {
	var $fields; 		// Fields that are to be displayed and validated and inserted to userdata
	var $fieldsets; 	// Fieldsets bundle fields under a title / description
	var $wrapper_html; 	// Array of html tags that enclose fieldsets / fields / labels / inputs 
	var $texts;
	
	function __construct() {

		// Save values that go to usermeta
		add_action('vmfeu_new_user', array(&$this, 'save_usermeta'), 1, 2);
		add_action('vmfeu_update_user', array(&$this, 'save_usermeta'), 1, 2);
 
		if (did_action('plugins_loaded'))
			$this->init_fields();
		else 
			add_action('plugins_loaded', array(&$this, 'init_fields'), 6);

	}

	/**
	 * Set the user info fields in the plugins_loaded action hook
	 * for all the translations to work etc.
	 * 
	 */
	public function init_fields(){

		// Doesn't populate field values yet since user might be just updating
		// and values are taken from sent form
		$this->set_user_info_fields();
		
		// init html tags that enclose fieldsets / fields / labels / inputs
		$this->set_wrapper_html();
		
		$this->set_texts();
	}
	
	/**
	 * Set what user info fields are included in registration and profile pages.
	 * It is recommended that you use the vmfeu_user_info_fields and 
	 * vmfeu_fieldsets filters to manipulate the fields.
	 * 
	 */
	private function set_user_info_fields() {
		global $vmfeu;
		
		// Add fields in order you wan't them to be displayed.
		// Fields have menu_order index that can be used in database query
		$this->fields = array();
		
		// WP user info fields are required		
		$wp_fields = $this->get_wp_fields();

		$fields["user_email"] 	= $wp_fields["user_email"];	
		$fields["first_name"] 	= $wp_fields["first_name"];
		$fields["last_name"] 	= $wp_fields["last_name"];
		
		$fields["description"] 	= $wp_fields["description"];
		$fields["user_url"] 	= $wp_fields["user_url"];
		
		$fields["user_login"] 	= $wp_fields["user_login"];
		$fields["user_pass"] 	= $wp_fields["user_pass"];
		
		// Also, add terms and conditions mandatory field if 
		// terms and conditions page id has been given in dashboard settings
		$terms_page_id = $vmfeu->settings['terms_page'];
		if ($terms_page_id) {
			global $wpdb;
			$terms_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->posts} WHERE ID = {$terms_page_id} LIMIT 1");
			$terms_title = apply_filters('the_title', $terms_title);
			$terms_link = '<a href="'.get_permalink($terms_page_id).'" title="'.$terms_title.'" target="_blank">'.$terms_title.'</a>';

			$fields["terms"]		= new Vmfeu_Checkbox_Field(array(
										"name" 	=> "terms_and_conditions",
										"title" => __("Terms and conditions", "verkkomuikku"),
										"mandatory" => array("terms_ok"), // give values from value_label_pairs that are mandatory
										"value_label_pairs" => array(
												"terms_ok" => array("value" => "1", "label" => sprintf(__("I accept %s", "verkkomuikku"), $terms_link)),
												),
										));
		}

		// Provide filter
		$this->fields = apply_filters('vmfeu_user_info_fields', $fields);							
		
		// Add fields into fieldsets
		// fieldsets have description (html) and fields.
		// Fields that are in $this->fields but not in any fieldset will display 
		// after fieldsets. ( All fields must be displayed, otherwise validation 
		// may not let user to register)
		$fieldsets = array(
			"background" => array(
							"title" => __("My information", "verkkomuikku"), 
							"fields" => array("user_email", "first_name", "last_name", "description", "user_url")
							),
			"account" 	=> array(
							"title" => __("Login information", "verkkomuikku"),
							"fields" => array("user_login", "user_pass")
							)
		);
		
		// Append terms and conditions
		if ($terms_page_id)
			$fieldsets["account"]["fields"][] = "terms";
		
		$this->fieldsets = apply_filters('vmfeu_user_info_fieldsets', $fieldsets, $this->fields);

	}
	
	/**
	 * Default wrapper html tags that enclose fiedsets / fields / labels / inputs etc.
	 * To alter, use hook
	 * TODO: get from db? admin panel at least 
	 */
	private function set_wrapper_html() {
		$fieldset_wrapper = array("start" => "", "end", "");
		$fieldset_title	= array("start" => "<h3 class='fieldset_title'>", "end" => "</h3>");
		$fieldset_description	= array("start" => "<p class='fieldset_description'>", "end" => "</p>");
		$fieldset = array("start" => "<dl>", "end" => "</dl>");
		$field = array("start" => "", "end" => "");
		$label = array("start" => "<dd>", "end" => "</dd>");
		$input = array("start" => "<dt>", "end" => "</dt>");
		$help  = array("start" => '<span class="help">', "end" => "</span>");
		
		$wrapper_html = array(
			"fieldset_wrapper" 	=> $fieldset_wrapper,
			"fieldset_title"	=> $fieldset_title,
			"fieldset_description"	=> $fieldset_description,
			"fieldset"	=> $fieldset,
			"field" 	=> $field,
			"label" 	=> $label,
			"input" 	=> $input,
			"help" 		=> $help
		);
		
		$this->wrapper_html = apply_filters('vmfeu_wrapper_html', $wrapper_html);
	}
	
	/**
	 * There are numerous user feedback texts, some of which can be
	 * customized. This function will set these customizable texts.
	 * TODO: admin interface
	 */
	public function set_texts() {
		
		$texts = array(
			"registration_success_admin" 	=> __('A user account for %s has been created.', 'verkkomuikku'),
			"registration_success_user" 	=> __('Thank you for registering, %s.', 'verkkomuikku'),
			"registration_success_user_activation_required" => __('Thank you for registering, %s.<br/><b>Please check your email and click the activation link to finish registration process.</b>', 'verkkomuikku'),
			"registration_email_sent_admin"	=> __('An email containing the user credentials was successfully sent to user.', 'verkkomuikku'),
			"registration_email_sent_user" 	=> __('You got also an email containing login credentials.', 'verkkomuikku'),
			"registration_email_error_admin" => __('An error occured while trying to send the notification email to user.', 'verkkomuikku'),
			"registration_email_error_user"	=> __('An error occured while trying to send the notification email.', 'verkkomuikku'),
			"registration_button_add_user" 	=> __('Add User', 'verkkomuikku'),
			"registration_button_register"	=> __('Register', 'verkkomuikku'),
		
			"login_label_username"			=> __('Username', 'verkkomuikku'),
			"login_label_create_account" 	=> __('Create new user account!', 'verkkomuikku'),
			
		);
		
		$this->texts = apply_filters('vmfeu_feedback_texts', $texts);
	}
	
	/**
	 * Return feedback text
	 * 
	 * @param string $text_key - key to address certain feedback text
	 * @return string $feedback_text
	 */
	public function get_text($text_key) {
		// warning for admin
		if (!isset($this->texts[$text_key])) {
			if (current_user_can('edit_users'))
				die("vmfeu: no feedback text for ".$text_key."!");
			return "";
		}
		
		return $this->texts[$text_key];
	}
	
	/**
	 * Get Wordpress standard user info fields
	 * 	- user_login
	 *  - user_email
	 *  - password
	 *  - first_name
	 *  - last_name
	 *  - nickname
	 *  - contact methods
	 *  @return Vmfeu_User_Info_Fields array $fields
	 */
	function get_wp_fields() {
		$fields = array(
			'user_login' 	=> new Vmfeu_Userlogin_Field(array()),
			'user_pass'		=> new Vmfeu_Userpassword_Field(array()),
			'first_name' 	=> new Vmfeu_Text_Field(array(
								"name" 	=> "first_name",
								"title" => __("First name", "verkkomuikku"),
								"mandatory" => true 
								)),
			'last_name' 	=> new Vmfeu_Text_Field(array(
								"name" 	=> "last_name",
								"title" => __("Last name", "verkkomuikku"),
								"mandatory" => true 
								)),
			//'nickname' 		=> "",
			'user_email'	=> new Vmfeu_Useremail_Field(array()),
			'user_url' 		=> new Vmfeu_Text_Field(array(
								"name" 	=> "user_url",
								"title" => __("Website", "verkkomuikku"),
								)),
			//'aim' 			=> "",
			//'yim' 			=> "",
			//'jabber' 		=> "",
			'description' 	=> new Vmfeu_Textarea_Field(array(
								"name" 	=> "description",
								"title" => __("About me", "verkkomuikku"),
								))
		);

		return apply_filters('vmfeu_wp_fields', $fields);
	}
	
	/**
	 * Get fieldsets. Append fields that are not in any fieldsets.
	 * 
	 * @return array $fieldsets
	 */
	public function get_fieldsets($append_unused_fields = true) {
		
		$fields = $this->fields;
		$fieldsets = $this->fieldsets;

		// Loop through fieldsets, unset fields that are in fieldsets.
		if (!empty($fieldsets)) {
			foreach ($fieldsets as $fieldset) {
				
				foreach ($fieldset["fields"] as $field) {
					// Unset field
					if (isset($fields[$field]))
						unset($fields[$field]);
				}
			}
		}

		// If all fields were not in fieldsets append them		
		if ($append_unused_fields && !empty($fields)) {
			$fieldsets["unused"]["description"] = "";
			$fieldsets["unused"]["fields"] = array();
			foreach ($fields as $field_name => $field) {
				$fieldsets["unused"]["fields"][] = $field_name;
			}
		}	
		
		return $fieldsets;	
	}
	
	/**
	 * Loop user info fields and populate user values from database
	 * 
	 * @param string $field_name | array $field_name - populates all fields or certain field(s)
	 * @return boolean $everything_ok Return boolean if there was fields that didn't validate. Can be used to redirect user to profile...
	 */
	function populate($field_name = "") {
		global $current_user, $vmfeu_profile, $vmfeu;
		
		$all_ok = true;
		
		if (!$current_user)
			$current_user = wp_get_current_user();
		
		// edit_user_id should catch id of the user whos data is edited
		$edit_user_id = $vmfeu->edit_user_id;
		
		if (!$edit_user_id)
			$edit_user_id = $current_user->ID;
		
		// Check if user can edit this user
		if ($edit_user_id && (current_user_can('edit_users') || $edit_user_id == $current_user->ID)) {
			$userdata = get_userdata($edit_user_id);
			
			// Populate all fields or certain fields
			$all_fields = $this->fields;
			if ($field_name) {
				foreach ((array)$field_name as $fn) {
					if (isset($all_fields[$fn]))
						$fields[] = $all_fields[$fn];
				}
			} else {
				$fields = $all_fields;
			}
			
			// Check if we can populate from $_POST
			$nonce_ok = wp_verify_nonce($_POST['edit_nonce_field'],'verify_edit_user');
			
			foreach ((array)$fields as $field => $val) {
			
				// never populate password
				if ($field == "user_pass")
					continue;
					
				if (isset($userdata->$field)) {
					
					// Check if new values was just posted, but there was errors
					if ($nonce_ok && isset($_POST[$field]) && !empty($vmfeu_profile->profile_update_errors)) {
						$value = $_POST[$field];
						
					// Otherwise use value from database
					} else {
						$value = $userdata->$field;
					}

					// Strip slashes from value
					if (!is_array($value)) {
						$value = stripslashes($value);
					} else {
						foreach ($value as $key => $val)
							$value[$key] = stripslashes($val);
					} 						
					
					// Set_value does validation
					// If it returns false, we have at least one field that didn't validate.
					if (!$this->fields[$field]->set_value($value)) {
						$all_ok = false;
					}
 				
				// Field wasn't set in $userdata, but it might be mandatory!
 				// Set the value to default
 				} else {
 					$missing_data = $this->fields[$field]->default_value;
 					if (!$this->fields[$field]->set_value($missing_data))
 						$all_ok = false;
 						
 					unset($missing_data);
 				}
			}
		}

		// Set flag into usermeta that all fields were validated
		$this->update_all_fields_ok($edit_user_id, $all_ok);
		
		return $all_ok;
	}
	
	/**
	 * Update into user meta that all required fields are ok.
	 * 
	 * Can be set to false as well, just give false as argument.
	 * 
	 * @param boolean $status
	 */
	public function update_all_fields_ok($user_id = 0, $status = true) {
		
		if (0 == $user_id)	
			$user_id = get_current_user_id();
			
		if (!$user_id)
			wp_die("Vmfeu: Cannot update user all_ok, the user is 0!");
			
		// With multisite, use assosiative array with blog_id as the 
		// index.
		
		$all_ok = get_user_meta($user_id, 'vmfeu_all_ok', true);
		
		if (!is_array($all_ok))
			$all_ok = array();
				
		$blog_id = get_current_blog_id();
		
		$all_ok[$blog_id] = $status;
		
		update_user_meta($user_id, 'vmfeu_all_ok', $all_ok);
	}
	
	/**
	 * Check if all user info fields are ok for the user.
	 * 
	 * If WP multiuser, each blog has their own flag in usermeta
	 * 
	 * @param int $user_id
	 * 
	 * @return boolean true or false if the fields are ok. Returns null if the background fields are never checked
	 */
	public function check_all_fields_ok($user_id = 0) {
		
		if (0 == $user_id)
			$user_id = get_current_user_id();
			
		if (!$user_id)
			wp_die("Vmfeu: Cannot check user all_ok, the user is 0!");
			
		$all_ok = get_user_meta($user_id, 'vmfeu_all_ok', true);

		$blog_id = get_current_blog_id();
		
		// Return null if the background fields are never checked for the blog.
		if (!is_array($all_ok) || !isset($all_ok[$blog_id]))
			return null;

		return $all_ok[$blog_id] ? true : false; 
	}
	
	/**
	 * Validate all fields eg. get value from $_POST and if it is valid
	 * save it to the field object member attribute $value.
	 * 
	 * Profile update can have different validation method.
	 * 
	 * @return WP_error - validation errors
	 */
	function validate() {
		
		$errors = new WP_error();
		$is_update = false;
		foreach ($this->fields as $field) {
			if ($field->is_update)
				$field->validate_on_profile_update($errors);
			else
				$field->validate($errors);
		}
		
		// Return WP_error object if there is errors
		// Return value is easy to test with is_wp_error() function call
		if ($errors->get_error_code())
			return $errors;
		else
			return array();

	}
	
	/**
	 * Return all fields
	 * @return array that contains field objects
	 */
	function get_fields() {
		return $this->fields;
	}
	
	/**
	 * Return a field
	 * @param string $field_name
	 * @return single field object
	 */
	function get_field($field_name) {
		return isset($this->fields[$field_name]) ? $this->fields[$field_name] : false;
	}
	
	/**
	 * Print all user info fields
	 * 
	 * @return string $mandatory_nag
	 * 
	 */
	function print_fields() {
		$fields = $this->fields;
		$fieldsets = $this->get_fieldsets(false); // Don't show the fields not in any fieldset
		
		if (!$fields)
			die("No user info fields!");
		
		// Add mandatory nag if there is mandatory fields
		$mandatory_nag = "";
		foreach ($fields as $field) {
			if ($field->is_mandatory()) {
				$mandatory_nag = sprintf(__("Fields marked with %s are mandatory.", "verkkomuikku"), '(*)');
				break;
			}
		}
		
		// Print fields in fieldsets
		if (!empty($fieldsets)) {
			foreach ($fieldsets as $fieldset) {
				
				if (empty($fieldset["fields"]) || !is_array($fieldset["fields"]))
					continue;
				
				echo $this->wrapper_html['fieldset_wrapper']['start'];
					if ("" != $fieldset["title"]) {
						echo $this->wrapper_html['fieldset_title']['start'];
							echo $fieldset["title"];
						echo $this->wrapper_html['fieldset_title']['end'];
					}
					if ("" != $fieldset["description"]) {
						echo $this->wrapper_html['fieldset_description']['start'];
							echo $fieldset["description"];
						echo $this->wrapper_html['fieldset_description']['end'];
					}
				echo $this->wrapper_html["fieldset"]["start"];
				foreach ($fieldset["fields"] as $field) {
					$this->print_field($field);
				}
				echo $this->wrapper_html["fieldset"]["end"];
				echo $this->wrapper_html['fieldset_wrapper']['end'];
			}
		}
		
		return $mandatory_nag;
	}
	
	/**
	 * Print single field
	 * 
	 * @param string field_name | Vmfeu_User_Info_Field object
	 */
	function print_field($field) {
		if (is_string($field)) {
			if (isset($this->fields[$field]))
			$field = $this->fields[$field];
		}
		
		if (is_a($field, 'Vmfeu_User_Info_Field')) {
			// Field object might have custom print function (for whole set, label + input)
			if (!$field->print_label_and_field($this->wrapper_html)) {
				echo $this->wrapper_html["field"]["start"];
					if ($field->has_help_text()) {
						echo $this->wrapper_html["label"]["start"];
							echo "&nbsp;";
						echo $this->wrapper_html["label"]["end"]; 
						echo $this->wrapper_html["input"]["start"];						
						echo $this->wrapper_html["help"]["start"];
							echo $field->get_help_text();
						echo $this->wrapper_html["help"]["end"];
						echo $this->wrapper_html["input"]["end"];			
					}				
					echo $this->wrapper_html["label"]["start"];
						?>
						<label for="<?php echo $field->id ?>"><?php echo $field->get_title(); ?></label>
						<?php 
					echo $this->wrapper_html["label"]["end"]; 
					echo $this->wrapper_html["input"]["start"];
						$field->print_field();					
					echo $this->wrapper_html["input"]["end"];

				echo $this->wrapper_html["field"]["end"];
			}
		}
	}

	/**
	 * insert new user based on user info fields
	 * 
	 * If Multisite, the whole multisite signup thing is bypassed, no multisite 
	 * activation etc. See wp-includes/ms-functions.php wpmu_activate_signup()
	 * and wpmu_create_user() for updates.
	 * WP 3.3.1
	 * 
	 * @param string $mail_status - return a string to indicate mail sending status (error | success)
	 * @uses wp_insert_user
	 * 
	 * @return WP_error on failure | new user id
	 */
	public function insert_user(&$mail_status) {
		global $vmfeu;
		
		// Load registration file.
		// TODO:  investigate, WP 3.1 should not need this anymore?
		require_once( ABSPATH . WPINC . '/registration.php' );
		
		$userdata = array();
		
		foreach ($this->fields as $key => $field)
			$userdata[$field->name] = $field->value;

		// Check transient for multiple registration from same ip address
		// Do this to minimize spam
		if ($vmfeu->settings['registration_transient'] && !current_user_can('edit_users')) {
			$spam_nag = $this->check_user_registration_transient();
			if (is_wp_error($spam_nag)) {
				return $spam_nag; 
			}
		}
		
		// If there wasn't user_pass, generate one.
		// In special cases, user registration might be used as customer registration etc...
		if (!isset($userdata["user_pass"])) {
			$userdata["user_pass"] = wp_generate_password(12, true, true);
		}
			
		// Hook to add / remove / modify userdata
		$userdata = apply_filters('vmfeu_new_user_userdata', $userdata);
		
		// Check that username, password and email are set, just in case
		if (!$userdata["user_login"] || !$userdata["user_pass"] || !$userdata["user_email"])
			return new WP_error('credentials_missing', __("User login, email and password are required!", "verkkomuikku"));
		
		$new_user = wp_insert_user( $userdata );

		if (is_wp_error($new_user))
			return $new_user;
			
		do_action('vmfeu_new_user', $new_user, $userdata);
		
		// If multisite, add the user to current blog
		if (is_multisite()) {
			global $current_blog;
			// See wp-includes/ms-functions.php  add_new_user_to_blog()
			add_user_to_blog( $current_blog->blog_id, $new_user, get_option('default_role') );
			update_user_meta( $new_user, 'primary_blog', $current_blog->blog_id );			
		}
		
		// Check if user has to activate his account.
		$account_activation_needed = $vmfeu->require_account_activation($new_user, $userdata);
		
		// Send email to admin about new user
		$user_login = stripslashes($userdata['user_login']);
		$plaintext_pass = stripslashes($userdata['user_pass']);
		$user_email = $userdata['user_email'];
		
		$blogname = get_option('blogname');
		
		$subject = apply_filters('vmfeu_registration_email_subject_admin', sprintf(__('[%s] New User Registration'), $blogname), $userdata);
		
		$message = $this->get_email_message("registration_admin", $userdata);
		
		@wp_mail(get_option('admin_email'), $subject, $message);
		
		// Send email to others than admin as well?
		// The array should contain email addresses.
		$email_others = apply_filters('vmfeu_registration_email_copy', array());
		foreach ((array)$email_others as $email_addr) {
			if (is_email($email_addr))
				@wp_mail($email_addr, $subject, $message);
		}
		
		
		if ( empty($plaintext_pass) )
			return;
			
		// Send password to user.
		// If admin created the user this step can be opted out
		// TODO: proper email system here
		if ((isset($_POST['send_password']) && ($_POST['send_password'] == 1)) || $account_activation_needed){
			
			if ($account_activation_needed) {
				$subject = sprintf(__("%s - Activate your user account", "verkkomuikku"), get_bloginfo('name'));
				$subject = apply_filters('vmfeu_registration_email_subject_activation_required', $subject, $userdata);
				$message = $this->get_email_message("registration_activation", $userdata);
			} else {
				$subject = sprintf(__("%s - Your user account details", "verkkomuikku"), get_bloginfo('name'));
				$subject = apply_filters('vmfeu_registration_email_subject', $subject, $userdata);
				$message = $this->get_email_message("registration", $userdata);
			}
			
			$mail_status = wp_mail($user_email, $subject, $message);
		}
					
		return $new_user;
	}

	public function update_user($edit_user_id, &$mail_status, &$password_updated) {
		// Load registration file.
		require_once( ABSPATH . WPINC . '/registration.php' );
		
		$userdata = array();
		
		foreach ($this->fields as $key => $field) {
			// If value is not updateable, don't include in userdata just in case
			if (!$field->updateable)
				continue;
				
			$userdata[$field->name] = $field->value;
		}
		
		// Username is not updateable, Username is required though by wp_insert_user
		$original_userdata = get_userdata($edit_user_id);
		$userdata["user_login"] = $original_userdata->user_login;
		
		// To update user set $userdata["ID"]
		$userdata["ID"] = $edit_user_id;

		// To update password include $userdata["user_pass"]
		// user_pass stays empty if it isn't updated
		if ($userdata["user_pass"] == "") {
			unset($userdata["user_pass"]);
		} else {
			$password_updated = true;
		}
		 
		// Hook to add / remove / modify userdata
		$userdata = apply_filters('vmfeu_update_user_userdata', $userdata);
		
		// Check that ID, username and email are set, just in case. Password stays the same if not updated here
		if (!$userdata["ID"] || !$userdata["user_email"])
			return new WP_error('credentials_missing', __("User ID and email are required!", "verkkomuikku"));
		
		// wp_update_user is able to update user if ID is present
		$updated_user = wp_update_user( $userdata );

		if (is_wp_error($updated_user))
			return $updated_user;
			
		do_action('vmfeu_update_user', $updated_user, $userdata);
		
		// Send new password to user.
		// If admin updated the user this step can be opted out
		// TODO: proper email system here
		
		if ($password_updated && isset($userdata["user_pass"]) && isset($_POST['send_password']) && ($_POST['send_password'] == 1)){
			$email = $userdata['user_email']; 

			$subject = apply_filters('vmfeu_profile_update_email_subject', sprintf(__("%s - your new password", "verkkomuikku"), get_bloginfo('name')), $userdata);
			$msg =  $this->get_email_message("profile_update", $userdata); 

			$mail_status = wp_mail( $email, $subject, $msg);
		}
		
		return $new_user;
	}
	
	
	/**
	 * New user created or a user has been updated. Userdata may hold values 
	 * that don't get saved in wp_insert_user(). This function compares 
	 * userdata to fields that go into usermeta.
	 * 
	 * @param int $user_id
	 * @param array $userdata
	 */
	public function save_usermeta($user_id, $userdata) {
		
		// Get Wordpress user fields that get saved in wp_insert_user
		// And save the ones that are not in this :) 
		$wp_fields = $this->get_wp_fields();
		$user = get_userdata($user_id);
		
		do_action('vmfeu_pre_save_usermeta', $userdata);
		
		// All userdata
		foreach ((array)$userdata as $field => $data) {
			// not included in wp_fields? save into usermeta
			if (!isset($wp_fields[$field])) {
				update_usermeta($user->ID, $field, $data);
			}
		}
	}
	
	/**
	 * Generate email message based on user info fields.
	 * 
	 * @param string $action what message to return
	 * @param array $userdata 
	 * @return email message
	 */
	public function get_email_message($action, $userdata) {
		global $shortcode_tags, $vmfeu;
		
		$user_login = stripslashes($userdata["user_login"]);
		$user_pass 	= stripslashes($userdata["user_pass"]);
		$user_email = stripslashes($userdata["user_email"]);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		// Note: if qTranslate in use, decode special chars. OwelaPro Multilanguage plugin already does this
		//$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$blogname = get_option('blogname');

		// Loop all fields so they can be included in email.
		$fieldsets = $this->get_fieldsets();
		$fields = $this->get_fields();
		$fields_for_message = "";
		
		// Save info fields into array so they can be passed to the message filter
		$info_fields = array();
		
		foreach($fieldsets as $fieldset) {
			foreach ($fieldset["fields"] as $field_name) {
				if ($field_name == "user_pass" || $field_name == "user_login" || $field_name == "user_email")
					continue;

				// By error, a field might be in fieldset but the fields doesn't exist
				// (human error)
				if (!isset($fields[$field_name]))
					continue;
					
				$fields_for_message .= $fields[$field_name]->get_title().": ".$fields[$field_name]->get_value_for_email()."\r\n";
				$info_fields[$field_name] = array("title" => $fields[$field_name]->get_title(), "value" => $fields[$field_name]->get_value_for_email());
			}
		}		
		
		// Email message about new user for site admin		
		if ("registration_admin" == $action) {

			// First put username and email, these are always required
			$message  = sprintf(__('New user registration on your site %s:', 'verkkomuikku'), $blogname) . "\r\n\r\n";
			$message .= sprintf(__('Username: %s', 'verkkomuikku'), $user_login) . "\r\n\r\n";
			$message .= sprintf(__('E-mail: %s', 'verkkomuikku'), $user_email) . "\r\n\r\n";
			
			$message .= $fields_for_message;
			
			$message = apply_filters('vmfeu_registration_email_message_admin', $message, $userdata, $info_fields);
			
			// Add info that user needs to activate her account
			if ($account_activation_needed) {
				$more = "\r\n".__("The user has to to click activation link on her email in order to log in.", "verkkomuikku");
				$message .= apply_filters('vmfeu_registation_activation_needed_admin', $more, $userdata);
			}
			
			return $message;
			
		// Message for user update
		} elseif ("profile_update" == $action) {
			$message = sprintf(__("Password changed for user %s for site %s.", "verkkomuikku"), $user_login, $blogname)."\r\n\r\n";
			$message .= sprintf(__("Your new password is: %s", "verkkomuikku"), $user_pass)."\r\n\r\n";
			$message .= sprintf(__("If you didn't change your password and think someone has stolen your account, please contact %s", "verkkomuikku"), get_option('admin_email'))."\r\n\r\n";
			$message .= get_bloginfo('home');
			
			return apply_filters('vmfeu_profile_updated_email_message', $message, $userdata);
			
		// Message for registration that needs account activation
		} elseif ("registration_activation" == $action) {
			
			$message = sprintf(__("Your username and password for site %s:", "verkkomuikku"),$blogname)."\r\n\r\n";
			$message .= sprintf(__("Username: %s", "verkkomuikku"), $user_login)."\r\n";
			$message .= sprintf(__("Password: %s", "verkkomuikku"), $user_pass)."\r\n\r\n";
			$message .= __("In order to finish registration process, please activate your account by clicking the activation link:", "verkkomuikku")."\r\n";
			$message .= $vmfeu->get_account_activation_link($userdata);

			return apply_filters('vmfeu_registration_email_message_activation_required', $message, $userdata, $info_fields);
		} else {
			$message = sprintf(__("Your username and password for site %s:", "verkkomuikku"),$blogname)."\r\n\r\n";
			$message .= sprintf(__("Username: %s", "verkkomuikku"), $user_login)."\r\n";
			$message .= sprintf(__("Password: %s", "verkkomuikku"), $user_pass)."\r\n\r\n";
			$message .= __("Welcome!", "verkkomuikku")."\r\n";
			$message .= get_bloginfo('home'); 

			return apply_filters('vmfeu_registration_email_message', $message, $userdata, $info_fields);			
		}
	}
	
	/**
	 * Check if users are created from same IP address within interval.
	 * Admins are ignored.
	 * 
	 * @return true if all ok | WP_error if user tries to register within interval
	 */
	public function check_user_registration_transient() {
		global $wpdb;
		
		// Allow admins to create users
		if (current_user_can('edit_users'))
			return true;
			
		$transient_name = "vmfeu_user_registration";
		$interval = 60; // Minutes
		$transient_time = $interval * 60; // Seconds
		
		// Otherwise check if transient contains IP address
		$ip = $_SERVER["REMOTE_ADDR"];
		
		$transient = get_transient($transient_name);
		
		// Check if IP has created user recently
		if ($transient && isset($transient[$ip])) {
			// If same IP address has registered user within interval, return error nag
			$within_interval = ($transient[$ip] > $wpdb->get_var("SELECT DATE_SUB(NOW(), INTERVAL {$interval} MINUTE)"));
			if ($within_interval)
				return new WP_error('multiple_registration', 
									apply_filters('vmfeu_multiple_user_registration_nag', sprintf(__("You have already created an user account. If you want to create multiple accounts, please wait %s minutes or contact site owner.", "verkkomuikku"), $interval), $interval)
									);
		}
		
		// Set current time transient for the IP
		$timestamp = $wpdb->get_var("SELECT NOW()");
		
		if (is_array($transient))
			$transient[$ip] = $timestamp;
		else
			$transient = array($ip => $timestamp);
		
		// Update or insert transient
		set_transient($transient_name, $transient, $transient_interval);
			
		return true;
	}
	
	/**
	 * Return how many attempts user has tried to log in within interval.
	 * 
	 * TODO: Not working properly
	 * 
	 * @param boolean $update Update transient attempts, if set false, you can check how many attempts user has done
	 * @return int $attempts
	 */
	function check_user_login_transient($update = true) {
		global $wpdb;
			
		$transient_name = "vmfeu_user_login";
		$interval = 60; // Minutes
		$transient_time = $interval * 60; // Seconds
		$within_interval = false;
		
		$ip = $_SERVER["REMOTE_ADDR"];
		
		$transient = get_transient($transient_name);
		
		// Set current time transient for the IP
		$timestamp = $wpdb->get_var("SELECT NOW()");
		
		if (is_array($transient)) {
			$transient[$ip]["interval"] = $timestamp;
			$transient[$ip]["attempts"] = $transient[$ip]["attempts"] > 0 ? intval($transient[$ip]["attempts"])+1 : 1; 
		}
		else {
			$transient = array($ip => array("timestamp" => $timestamp, "attempts" => 1));
		}
		
		// Update or insert transient
		if ($update)
			set_transient($transient_name, $transient, $transient_interval);
		
		return $transient[$ip]["attempts"];
	}
	
	/**
	 * Reset login transient for user
	 * 
	 */
	function reset_login_transient() {
		$transient_name = "vmfeu_user_login";

		// Otherwise check if transient contains IP address
		$ip = $_SERVER["REMOTE_ADDR"];
		
		$transient = get_transient($transient_name);
		
		if (isset($transient[$ip])) {
			unset($transient[$ip]);
			set_transient($transient_name, $transient);		
		}
		
		return;
	}
	
	/**
	 * If user tries wrong password too many times when logging in, 
	 * display extra question for humanity check.
	 * 
	 * TODO: Not tested
	 * Create custom class that holds the equation and checks for right answer
	 * 
	 */
	public function print_bot_check_field() {
		
		// Get random question
		$question = $this->get_bot_check_question();

		// Print it
		$question->print_label_and_field($this->wrapper_html);
		
		// Accommodate with key so that answer can be validated
		$validator = new Vmfeu_Hidden_Field(array(
						"name" => "vmfeu_bc",
						"value" => $question->get_key()
					));
		$validator->print_field();
	}
	
	/**
	 * Bot check question
	 * Return random question, or question for certain key
	 * 
	 * @return array $questions
	 */
	function get_bot_check_question($question_key = "") {
		$equation_text = __("Give correct result for equation %s", "verkkomuikku");
		$questions =  array(
					new Vmfeu_Botcheck_Field(array(
						"name" 		=> "vmfeu_bc_answer",
						"title" 	=> __("Humanity check", "verkkomuikku"),
						"help_text" => sprintf($equation_text, "1 + 3 = ?"),
						"answer" 	=> 4,
						"mandatory" => true
					)),
					new Vmfeu_Botcheck_Field(array(
						"name" 		=> "vmfeu_bc_answer",
						"title" 	=> __("Humanity check", "verkkomuikku"),
						"help_text" => sprintf($equation_text, "20 - 1 = ?"),
						"answer" 	=> 19,
						"mandatory" => true
					)),
					new Vmfeu_Botcheck_Field(array(
						"name" 		=> "vmfeu_bc_answer",
						"title" 	=> __("Humanity check", "verkkomuikku"),
						"help_text" => sprintf($equation_text, "2 * 6 = ?"),
						"answer" 	=> 12,
						"mandatory" => true
					)),
					new Vmfeu_Botcheck_Field(array(
						"name" 		=> "vmfeu_bc_answer",
						"title" 	=> __("Humanity check", "verkkomuikku"),
						"help_text" => sprintf($equation_text, "6 / 3 = ?"),
						"answer" 	=> 2,
						"mandatory" => true
					))
					);
					
		// Hash keys
		foreach ($questions as $key => $q) {
			
			// Use hourly changing hash for index keys
			$hash = md5(date('d.m.H')."papupipupaa".$key);
			$questions[$hash] = $q;
			$questions[$hash]->set_key($hash); 

			// Unset orginal index
			unset($questions[$key]);
		}
		
		// Randomly select one question		
		if ("" == $question_key) {
			$key = (date('dHis') % count($questions));
			$question_key = md5(date('d.m.H')."papupipupaa".$key);
		}

		// User tried to sneak wrong key
		if (!isset($questions[$question_key]))
			return false;
			
		return $questions[$question_key];
	}
	
	/**
	 * Validate bot check function
	 * 
	 * @param object WP_error
	 */
	function validate_bot_check(&$error) {
		$question = Vmfeu_User_Info_Fieldset::get_bot_check_question($_REQUEST["vmfeu_bc"]);
		
		// User tried to change the question
		if (!$question)
			$error->add("vmfeu_wrong_bot_check_question", __("No such question!", "verkkomuikku"));
		else
			$question->validate($error);
		
		return;
	}
}
