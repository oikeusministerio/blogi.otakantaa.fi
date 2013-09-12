<?php
/**
 * Verkkomuikku Front end user Avatar crop / upload
 * 
 * Avatars are saved in vmfeu_avatars folder that should be in
 * the root of WP uploads folder what ever that might be.
 * Default {WP_CONTENT_DIR}/uploads/vmfeu_avatars. Create the folder
 * and make it writable by apache.
 * 
 * Based on code from 
 * http://tympanus.net/codrops/2009/11/04/jquery-image-cropper-with-uploader-v1-1/
 * 
 * Notice: 
 * The code has been edited to use latest jQuery, jcrop
 * and class.upload.php and of course to tap into 
 * WordPress and vmfeu plugin.
 * 
 * The associated css and javascript files are combined into vmfeu-avatar.css and vmfeu-avatar.js
 * 
 * class.upload.php
 * http://www.verot.net/php_class_upload.htm
 * 
 * Jcrop
 * http://deepliquid.com/content/Jcrop.html
 * 
 * Uploadify
 * http://www.uploadify.com/
 * Note: v2.1.4 has some bugs with cancel upload script.. no can do.
 *
 * 
 * TODO:
 * 
 * ok - cropin lisäks class php upload sais skaalata kuvan!
 * - cleanup function (files not in usermeta remove)
 * - bugit pois  http://validator.w3.org/
 * - oman kuvan poistaminen
 * 
 * - kuva myös dashboardiin!
 * 
 * ok - save filename in usermeta, pitäis mennä suoraan vmfeu namen perusteella
 * ok - on upload remove the old file
 * 
 * 
 * ok - custom field class that displays the avatar stuff
 * ok - javascript that gives life for the avatar stuff
 * ok - handler functions for ajax calls
 *  
 * 
 * 
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * @author verkkomuikku
 *
 */
if (!class_exists('Vmfeu_Avatar')) {
	class Vmfeu_Avatar {

		var $settings = array();
		var $error_messages = array();
		
		/**
		 * Constructor
		 * 
		 */
		public function __construct() {
			$this->init();
		}
		
		/**
		 * Init
		 * 
		 */
		private function init() {
			
			// Set options
			$this->set_options();

			// Register settings to dashboard options page
			add_action('admin_init', array(&$this, 'register_settings'));
			
			// Don't continue further if avatars not enabled
			if (!$this->uploading_enabled() || !get_option('show_avatars'))
				return;
				
			// Check the upload folder is ok
			if (!$this->check_upload_dir())
				return;

			add_action('init', array(&$this, 'vmfeu_avatar_scripts'));
			add_action('wp_print_styles', array(&$this, 'vmfeu_avatar_styles'));
			
			// Hook to ajax upload
			add_action('wp_ajax_vmfeu_avatar_upload', array(&$this, 'avatar_upload_handler'));
			add_action('wp_ajax_nopriv_vmfeu_avatar_upload', array(&$this, 'avatar_upload_handler'));
			
			// Hook to ajax crop
			add_action('wp_ajax_vmfeu_avatar_crop', array(&$this, 'avatar_crop_handler'));
			add_action('wp_ajax_nopriv_vmfeu_avatar_crop', array(&$this, 'avatar_crop_handler'));
			
			// Hook to ajax remove avatar
			add_action('wp_ajax_vmfeu_avatar_remove', array(&$this, 'avatar_remove_handler'));
			add_action('wp_ajax_nopriv_vmfeu_avatar_remove', array(&$this, 'avatar_remove_handler'));
			
			// Replace avatar
			add_filter('get_avatar', array(&$this, 'filter_avatar'), 10, 5);

			// Add custom fields to profile
			add_action('init', array(&$this, 'add_avatar_user_info_fields'));
			
			add_action('vmfeu_pre_save_usermeta', array(&$this, 'remove_avatar_on_update'));
									
			// Uninstall
			// TODO There isnät vmfeu_uninstall action call, since there is no uninstall function yet
			//add_action('vmfeu_uninstall', array(&$this, 'vmfeu_facebook_connect_uninstall'));
			
		}
		
		/**
		 * Check if the upload folder is writable by apache
		 * 
		 */
		private function check_upload_dir() {

			if (!is_writable($this->settings['upload_dir'])) {
				add_action('admin_notices', array(&$this, 'admin_error'));
				if ('' == $this->settings['upload_dir'])
					$upload_dir = sprintf(__("Check permissions for the default WordPress uploads folder %s/uploads.", "verkkomuikku"), WP_CONTENT_DIR);
				else
					$upload_dir = $this->settings['upload_dir'];
					
				$this->error_messages[] = __("Vmfeu avatars disabled: The avatar upload directory is not writable by Apache.", "verkkomuikku")."<br/>".$upload_dir;
					
				return false;
			}
			return true;
		}
		
		/**
		 * Display admin error message if the avatar upload dir is not 
		 * writable. 
		 * 
		 */
		public function admin_error() {
			if ($this->error_messages) {
				foreach( $this->error_messages as $msg) {
				    echo '<div class="error">
				       <p>'.$msg.'</p>
				    </div>';
				}
			}
		}
		
		/**
		 * Check if avatar uploading is enabled, eg. the 
		 * avatar field is included in registration nad profile forms.
		 * 
		 * @return boolean $uploading_enabled
		 */
		public function uploading_enabled() {
			return $this->settings['uploading_enabled'] ? true : false;
		}
		
		/**
		 * Check whether to show the uploaded avatars
		 * 
		 * @return boolean $show_avatars
		 */
		public function show_avatars() {
			return $this->settings['show_uploaded_avatars'] ? true : false;
		}
		
		/**
		 * Set options. Get from database as member variable.
		 * If the options don't exist yet insert the default
		 * values to database.
		 * 
		 */
		private function set_options() {
		
			$this->settings = get_option('vmfeu_avatar_settings');
			
			// No settings saved. Insert the defaults into database
			if (!$this->settings || '' == $this->settings['upload_dir']) {
				
				// For multisite installs, allow the avatars to be used
				// across the site by uploading them to the main site upload dir.
				if (is_multisite()) {
					global $current_site;
					
					if (switch_to_blog($current_site->blog_id)) {
						$wp_upload_dir = wp_upload_dir();
						restore_current_blog();
					} else {
						wp_die("Vmfeu: Cannot switch to root blog to set the upload folder.");
					}
				} else {
					$wp_upload_dir = wp_upload_dir();
				}
				
				// If the upload dir is not writable, don't enable
				if (false !== $wp_upload_dir['error']) {
					$uploading_enabled = false;
					$upload_dir = '';
					$upload_url = '';
				} else {
					$uploading_enabled = true;
					$upload_dir = $wp_upload_dir['basedir'].'/vmfeu_avatars';
					$upload_url = $wp_upload_dir['baseurl'].'/vmfeu_avatars';
				}
				
				// Setting up for the first time
				if (empty($this->settings)) {
					$this->settings = array(
						"uploading_enabled" 	=> $uploading_enabled, 	// Include the avatar user info field
						"show_uploaded_avatars" => $uploading_enabled,	// Disable the uploaded avatars from showing in the site. Just incase some one tries to exploit
						"size"					=> 100,			// Avatar size. The 96 seems to be WP default
						"upload_dir"			=> $upload_dir, // Path to the avatar folder
						"upload_url"			=> $upload_url, // URL of the avatar folder
						"temp_folder"			=> "temp", 
					);
				
				// Try to update the upload dir and url that failed last time
				// due to no write permission for the wp_upload_dir() folder
				} else {
					$this->settings["upload_dir"] = $upload_dir;
					$this->settings["upload_url"] = $upload_url;
				}
				
				update_option('vmfeu_avatar_settings', $this->settings);
			}
			
			$this->settings = apply_filters('vmfeu_avatar_settings', $this->settings);
		}
		
		/**
		 * WP admin settings register
		 * 
		 */
		public function register_settings() {	
			
			add_settings_section('vmfeu_avatar_settings_section', __('Avatar settings', 'verkkomuikku'), array(&$this, 'vmfeu_avatar_settings_section'), 'VerkkomuikkuFrontEndUserSettings');
			add_settings_field('uploading_enabled', __('Add avatar upload to registration and profile forms.', 'verkkomuikku'), array(&$this, 'sf_uploading_enabled'), 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_avatar_settings_section');			
			add_settings_field('show_uploaded_avatars', __('Show the uploaded avatars as user avatar instead of Gravatars etc.', 'verkkomuikku'), array(&$this, 'sf_show_uploaded_avatars'), 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_avatar_settings_section');
			add_settings_field('size', __('Crop to size. 150px crop should be ok, WordPress displays 96px avatars by default.', 'verkkomuikku'), array(&$this, 'sf_size'), 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_avatar_settings_section');
			add_settings_field('upload_dir', __('Directory where the avatars reside.', 'verkkomuikku'), array(&$this, 'sf_upload_dir'), 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_avatar_settings_section');			
			add_settings_field('upload_url', __('URL of the directory.', 'verkkomuikku'), array(&$this, 'sf_upload_url'), 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_avatar_settings_section');
			
			register_setting( 'VerkkomuikkuFrontEndUserSettings', 'vmfeu_avatar_settings');		
		}

		
		/**
		 * Guide text for avatar settings section
		 * 
		 */
		public function vmfeu_avatar_settings_section() {
			$message = __("Allow users to upload their avatars from computer.", "verkkomuikku");
			return '<p>'.$message.'</p>'; 
		}		
			
		/**
		 * Display vmfeu_avatar_uploading_enabled admin option field
		 * 
		 */
		public function sf_uploading_enabled() {
			$checked = $this->settings['uploading_enabled'] ? ' checked="checked"' : '';
			?>
			<input type="checkbox" name="vmfeu_avatar_settings[uploading_enabled]" id="vmfeu_avatar_uploading_enabled" value="1" <?php echo $checked ?>/>
			<?php
		}
		
		/**
		 * Display vmfeu_avatar_show_uploaded_avatars admin option field
		 * 
		 */
		public function sf_show_uploaded_avatars() {
			$checked = $this->settings['show_uploaded_avatars'] ? ' checked="checked"' : '';
			?>
			<input type="checkbox" name="vmfeu_avatar_settings[show_uploaded_avatars]" id="vmfeu_avatar_show_uploaded_avatars" value="1" <?php echo $checked ?>/>
			<?php
		}
		
		/**
		 * Display vmfeu_avatar_show_uploaded_avatars admin option field
		 * 
		 */
		public function sf_size() {
			$size = isset($this->settings['size']) ? intval($this->settings['size']) : 150;
			?>
			<input type="text" size="4" name="vmfeu_avatar_settings[size]" id="vmfeu_avatar_size" value="<?php echo $size ?>"/>
			<?php
		}		

		/**
		 * Display vmfeu_avatar_upload_dir option field
		 * 
		 */
		public function sf_upload_dir() {
			?>
			<input type="text" name="vmfeu_avatar_dir_dummy" size="80" value="<?php echo $this->settings['upload_dir']; ?>" disabled="disabled"/>
			<input type="hidden" name="vmfeu_avatar_settings[upload_dir]" id="vmfeu_avatar_upload_dir" value="<?php echo $this->settings['upload_dir']; ?>"/>
			<input type="hidden" name="vmfeu_avatar_settings[temp_folder]" id="vmfeu_avatar_temp_folder" value="<?php echo $this->settings['temp_folder']; ?>"/>
			<?php
		}		
		
		/**
		 * Display vmfeu_avatar_upload_url option field
		 * 
		 */
		public function sf_upload_url() {
			?>
			<input type="text" name="vmfeu_avatar_url_dummy" size="80" value="<?php echo $this->settings['upload_url']; ?>" disabled="disabled"/>
			<input type="hidden" name="vmfeu_avatar_settings[upload_url]" size="80" id="vmfeu_avatar_upload_url" value="<?php echo $this->settings['upload_url']; ?>"/>
			<?php
		}		
		
		/**
		 * Handle the avatar upload ajax request.
		 *
		 * The upload just uploads the file as temporary file to show in the 
		 * crop window.
		 * 
		 */		
		public function avatar_upload_handler() {
			
			if (!empty($_FILES)) {
				$avatar_temp_dir = trailingslashit($this->settings['upload_dir']).$this->settings['temp_folder']."/";
				$avatar_temp_dir_url = trailingslashit($this->settings['upload_url']).$this->settings['temp_folder']."/";
				$temp_filename = 'temp_picture_'.substr(md5(date('Ymdhis')), 0,10);
				
				// Class upload required
				require_once('crop/class.upload.php');
				$handle = new Upload($_FILES['vmfeu_avatar_file']);
				
				if ($handle->uploaded) {
					$handle->file_src_name_body      = $temp_filename;
					$handle->file_overwrite 		 = true;
					$handle->file_auto_rename 		 = false;
					$handle->image_resize            = true;
					$handle->image_ratio_y           = true;
					$handle->image_x                 = ($handle->image_src_x < 400) ? $handle->image_src_x : 400;
					$handle->file_max_size 			 = '8192000'; //max size bytes - around 8mb, the uploadify javascript option has to match!
					$handle->Process($avatar_temp_dir);
					
					
					if ($handle->processed) {
		           		$json = array(
		           					"result" 	=> 1,	
		           					"message"	=> __("Avatar uploaded", "verkkomuikku"),
		           					"filename"	=> $handle->file_dst_name,
									"file_url" 		=> $avatar_temp_dir_url.$handle->file_dst_name.'?'.time(),
									"imagewidth" 	=> $handle->image_dst_x,
									"imageheight"	=> $handle->image_dst_y
								);
					} else {
		           		$json = array("result" => 0, "message" => sprintf(__("Error processing the image! %s", "verkkomuikku"), $handle->error ));
					}
					
					$handle->clean();
				} else { 
					$json = array("result" => 0, "message" => __("Error initializing php class upload!", "verkkomuikku"));
				}
			} else {
				$json = array("result" => 0, "message" => __("No file!", "verkkomuikku"));
			}

			echo json_encode($json);
			exit();
		}
		
		/**
		 * Handle the cropped image.
		 * 
		 * The original image is cropped with coordinates from Jcrop and
		 * saved to a persistent folder. The filename is returned back
		 * to be saved in hiddenfield, which in turn then get sent with the
		 * form. The image name is then saved to usermeta when the user registers
		 * / updates the profile.
		 *
		 */
		public function avatar_crop_handler() {
			if ( !isset($_POST['file']) ) {
				$result = array("result" => 0, "message" => __("No File!", "verkkomuikku"));
			} else {
				
				// Uploaded filename, could be security risk?:)
				$temp_filename = $_POST['file'];
				$temp_file_chunks = explode(".", $temp_filename);
				// Check the file type, no white list.. just check a proper 3 letter ending
				$temp_file_ending = substr(array_pop($temp_file_chunks), 0, 3);
				// Check no special characters in the file name
				$temp_file_body = implode('', $temp_file_chunks);
				$temp_file_body = preg_replace('#[^A-Za-z0-9_\-]#', '_', $temp_file_body);
				$temp_filename = $temp_file_body.".".$temp_file_ending;
				
				if ($temp_filename != $_POST['file']) {
					$json = array("result" => 0, "message" => __("Hack attempt!", "verkkomuikku"));
					echo json_encode($json);
					exit();
				}
				
				// Handle the already uploaded temp file
				$avatar_dir = trailingslashit($this->settings['upload_dir']);
				$avatar_dir_url = trailingslashit($this->settings['upload_url']);
				$avatar_temp_dir = trailingslashit($this->settings['upload_dir']).$this->settings['temp_folder']."/";
				$temp_file = $avatar_temp_dir.$temp_filename;
				$crop_size = isset($this->settings['size']) ? intval($this->settings['size']) : 150;
				
				// Sanity check just in case...
				if ($crop_size < 10 || $crop_size > 500)
					$crop_size = 150;
					
				
				// Generic filename for the persisting file
				$filename = md5($temp_file.date('YmdHis'));
				
				// Class upload required
				require_once('crop/class.upload.php');				
				$handle = new Upload($temp_file);
				
				if ($handle->uploaded) {
					
					// Calc coordinates for croping
					$img_width = $handle->image_src_x;
					$img_height = $handle->image_src_y;
					
					$top = intval($_POST['y']);
					$left = intval($_POST['x']);
					$right = $img_width - ($left + intval($_POST['w']));
					$bottom = $img_height - ($top + intval($_POST['h']));
					
					$handle->file_src_name_body      = $filename;

					// Crop the image					
					$handle->image_precrop			 = array($top, $right, $bottom, $left);

					// Resize to default size if the crop area was bigger / smaller
					$handle->image_resize          	= true;
					$handle->image_ratio           	= true;
					$handle->image_y               	= $crop_size;
					$handle->image_x               	= $crop_size;					
					
					// Save the image
					$handle->Process($avatar_dir);
					
					// All ok
					if ($handle->processed) {
		           		$json = array(
		           					"result" 	=> 1,	
		           					"message"	=> __("The avatar will be set when you save your profile.", "verkkomuikku"),
		           					"filename" 		=> $handle->file_dst_name,
									"file_url" 		=> $avatar_dir_url.$handle->file_dst_name,
								);
						$handle->clean();
					} else {
		           		$json = array("result" => 0, "message" => sprintf(__("Error processing the cropped image! %s", "verkkomuikku"), $handle->error ));
					}
					
				} else {
					$json = array("result" => 0, "message" => sprintf(__("Error uploading the cropped image! %s", "verkkomuikku"), $handle->error ));
				}
			}
			
			echo json_encode($json);
			exit();			
		}
		
		/**
		 * Handle the avatar remove AJAX call.
		 * 
		 * 
		 */		
		public function avatar_remove_handler() {
			global $vmfeu;
			
			if (!isset($_POST['user_id']))
				die(-1);

			$user_id = intval($_POST['user_id']);
			
			// The user id is got from input field edit_id
			// which should be present when admin is editing another user.
			// If the input field is not present, the $_POST['user_id'] is 0
			// in which case use the current user id
			if (0 == $user_id) {
				$user_id = get_current_user_id();
			}
			
			if ($user_id == get_current_user_id() || current_user_can('edit_users')) {

				// Get the avatar from usermeta
				$avatar = get_user_meta($user_id, 'vmfeu_avatar', true);
			
				// Delete the file
				$this->delete_file($avatar);
				
				// Delete the usermeta
				delete_user_meta($user_id, 'vmfeu_avatar');
				
				// Avatar size depends on your theme and is sent with the ajax call
				$size = isset($_POST['size']) ? intval($_POST['size']) : 100;
			
	           	$json = array(
           			"result" 	=> 1,	
           			"message"	=> __("Avatar removed", "verkkomuikku"),
           			"avatar_img" => get_avatar($user_id, $size), // returns the default avatar
				);
			
			} else {
				$json = array("result" => 0, "message" => __("You can't edit the user!", "verkkomuikku"));				
			}
			
			echo json_encode($json);
			exit();				
		}
		
				
		/**
		 * Override what ever avatar there is with the the uploaded avatar
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

			if (!$this->show_avatars())
				return;
			
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

			// Return uploaded avatar or default if there isn't any
			$vmfeu_avatar = $this->get_avatar($id, $size);
			if($vmfeu_avatar)
				return $vmfeu_avatar;

			return $avatar;		
		}
		
		/**
		 * Function to check if user has an avatar uploaded
		 * 
		 * @param int $user_id
		 * 
		 * @return boolean $has_avatar
		 */
		public function has_avatar($user_id = 0) {
			if (!$user_id)
				$user_id = get_current_user_id();
			if (!$user_id)
				return false;	
			
			return get_usermeta($user_id, 'vmfeu_avatar') ? true : false;
		}
		
		/**
		 * Get avatar template 
		 * 
		 * @param int $user_id
		 * @param int $size 
		 * 
		 * @return int $avatar HTML img
		 */		
		public function get_avatar($user_id, $size=30) {
			
			$avatar_filename = get_usermeta($user_id, 'vmfeu_avatar');

			if ($avatar_filename) {
				$avatar_url = apply_filters('vmfeu_user_avatar_url', trailingslashit($this->settings['upload_url']).$avatar_filename, $user_id, $size);	
			} else {
				$avatar_url = apply_filters('vmfeu_default_user_avatar_url', $size);
			}

			if (!$avatar_url)
				return false;
			
			$userdata = get_userdata($user_id);						
			
			$style = ' style="width: '.intval($size).'px; height: '.intval($size).'px;"';
			
			$html = '<img src="'.$avatar_url.'" alt="'.$userdata->display_name.'" width="'.intval($size).'" height="'.intval($size).'" '.$style.' class="avatar"/>';
			
			return apply_filters('vmfeu_avatar', $html, $avatar_url);
		}
		
		/**
		 * Add some avatar related user info fields into profile
		 * 
		 */
		public function add_avatar_user_info_fields() {
			add_filter('vmfeu_user_info_fields', array(&$this,'filter_user_info_fields'), 10);
			add_filter('vmfeu_user_info_fieldsets', array(&$this,'filter_user_info_fieldsets'), 10);
		}
		
		/**
		 * Add avatar upload box to the background info section
		 * 
		 * @see verkkomuikku-front-end-user-plugin.php
		 *
		 * @param $fields - array of user info fields
		 * 
		 * @return $fields - user info fields added with avatar related field
		 * 
		 */
		public function filter_user_info_fields($fields) {
			
			$fields["vmfeu_avatar"] = new Vmfeu_Avatar_Field(array(
									"name" 	=> "vmfeu_avatar",
									"title" => __("Profile picture", "verkkomuikku"),
									));

			return $fields;
			
		}
		
		/**
		 * Put Avatar related user info fields into fieldset
		 * 
		 * @param array $fieldsets
		 * 
		 * @return array $fieldsets
		 * 
		 */
		public function filter_user_info_fieldsets($fieldsets) {
		
			$fieldsets["background"]["fields"][] = "vmfeu_avatar";
			
			return $fieldsets;
		}

		/**
		 * When user is updating the profile, delete the old avatar
		 * file if exists.
		 * 
		 * @param array $userdata
		 */
		public function remove_avatar_on_update($userdata) {
			$old_avatar = get_user_meta($userdata["ID"], 'vmfeu_avatar', true);
			
			if ($old_avatar != $userdata["vmfeu_avatar"]) {
				$this->delete_file($old_avatar);
			}
		}

		/**
		 * Delete file from filesystem
		 * 
		 * @param unknown_type $filename
		 */
		public function delete_file($filename) {
			
			$file = trailingslashit($this->settings["upload_dir"]).$filename;
			 
			// Class upload required
			require_once('crop/class.upload.php');			
			$handle = new Upload($file);
			
			if ($handle->uploaded)
				$handle->clean();
				
			return;
			
		}
		
		/**
		 * Unistall
		 * TODO vmfeu plugin doesn't currently have uninstall activation hook.
		 * 
		 */
		public function vmfeu_avatar_uninstall(){

		}
		
		/**
		 * Include javascript for avatar crop in registration 
		 * and upload pages.
		 *
		 */
		public function vmfeu_avatar_scripts() {
			global $vmfeu;
			
			if (!$vmfeu)
				return;
				
			$current_page = $vmfeu->is_vmfeu_page();
				
			// Not on the registration / profile page...
			if (!$current_page || !in_array($current_page, array("registration", "profile")))
				return;
					
			wp_register_script('vmfeu_avatar_script',
		   		plugins_url('vmfeu-avatar.js', __FILE__),
		       	array('jquery'),
		       	'1.0');
			wp_enqueue_script('vmfeu_avatar_script');

			// Pass variables for javascript
			wp_localize_script( 'vmfeu_avatar_script', 'VMFEUAvatar', array( 
				'save_button' 	=> __("Save", "verkkomuikku"),
				'cancel_button' => __("Cancel", "verkkomuikku"),
				'size'			=> isset($this->settings['size']) ? intval($this->settings['size']) : 150, 
			));
		}
		
		/**
		 * Include styles
		 * 
		 */
		public function vmfeu_avatar_styles() {
			global $vmfeu;
			
			if (!$vmfeu)
				return;
				
			$current_page = $vmfeu->is_vmfeu_page();
				
			// Not on the registration / profile page...
			if (!$current_page || !in_array($current_page, array("registration", "profile")))
				return;
							
			$styleUrl = VMFEU_PLUGIN_URL. '/includes/vmfeu-avatar.css';
            wp_register_style('vmfeu_avatar_stylesheet', $styleUrl);
            wp_enqueue_style( 'vmfeu_avatar_stylesheet' );
		}
	}
}
global $vmfeu_avatar;
$vmfeu_avatar = new Vmfeu_Avatar();


/**
 * Avatar field class
 * HTML input[type=hidden] and interface for the ajax upload and crop
 * 
 * @author teemu
 *
 */
class Vmfeu_Avatar_Field extends Vmfeu_User_Info_Field {
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
		global $vmfeu, $vmfeu_avatar;
		
		if (!is_a($vmfeu_avatar, 'Vmfeu_Avatar'))
			wp_die("Vmfeu_Avatar_Field: Vmfeu_Avatar class missing or not initialized!");		
		
		$class = apply_filters("vmfeu_field_class_".sanitize_html_class($this->name,"x"), "");
		
		// Get the default avatar unless editing profile.
		// Get the avatar by default wordpress get_avatar function
		// Which requires user id or user email. If empty, a default avatar is used
		
		$user_id = "";
		if ($vmfeu && $vmfeu->is_vmfeu_page() == "profile") {
			$user_id = $vmfeu->edit_user_id;
		}
		
		?>
			<div class="vmfeu_avatar_content">
				<div class="vmfeu_avatar_left">
					<div id="vmfeu_avatar_preview_container" class="vmfeu_avatar_preview_container">
						<input type="file" name="vmfeu_avatar_uploadify" id="vmfeu_avatar_uploadify" style="display:none;"/>
						<a id="vmfeu_avatar_overlay" class="vmfeu_avatar_preview_overlay" style="display:none;"></a>
						<?php echo get_avatar($user_id, 100); ?>				
					</div>
					<?php $display = $vmfeu_avatar->has_avatar($user_id) ? '' : 'display: none;'; ?>
					<div id="vmfeu_avatar_remove" class="vmfeu_avatar_control" style="<?php echo $display; ?>"><a href="<?php echo '#'.__('Remove_avatar', 'verkkomuikku'); ?>"><?php _e("Remove", "verkkomuikku")?></a></div>
							
					<div id="vmfeu_avatar_ajaxload" class="vmfeu_avatar_ajaxload" style="display:none;"></div>
					<div id="vmfeu_avatar_crop_buttons" class="vmfeu_avatar_control"></div>
					<div class="vmfeu_avatar_queue">
						<div id="vmfeu_avatar_fileQueue"></div>
					</div>
				</div>	
				<div class="vmfeu_avatar_right">
					<p id="vmfeu_avatar_crop_nag" style="display: none;"><?php _e("Select area to crop the image. When ready, click 'Save'", "verkkomuikku"); ?></p>
					<div id="vmfeu_avatar_image_container"></div>	
				</div>
            </div>
            <input type="hidden" id="vmfeu_avatar_tempfile" name="vmfeu_avatar_tempfile" />
			<input type="hidden" name="<?php echo $this->name ?>" value="<?php echo $this->value ?>" id="<?php echo $this->id ?>" class="<?php echo $class ?>" />
		<?php 
	}
}