<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Provide Like button (+ other sharing buttons)
 * See: http://yoast.com/social-buttons/
 * 
 * This file should be included from Verkkomuikku FB plugin and run before 
 * init action so that scripts and css get included.
 * 
 * http://twitter.com/about/resources/tweetbutton
 * 
 */
if (!class_exists('Vmfb_Sharing')) {
	class Vmfb_Sharing {
		
		var $settings;
		
		/**
		 * Constructor
		 * 
		 */
		public function __construct() {
			$this->init();
		}
		
		/**
		 * Init function
		 * 
		 */
		public function init() {
			$this->init_attributes();
						
		    // Register dashboard settings
		    if (is_admin())
				add_action('admin_init', array(&$this, 'register_settings'));
		}

		/**
		 * General init tasks 
		 * 
		 */
		public function init_attributes() {

			// Load settings
			$this->settings = apply_filters('vmfb_sharing_settings', get_option('vmfb_sharing_settings'));
										
			// Init settings
			// TODO: possibility to reset settings
			if (!$this->settings) {
				$this->settings = array(
					"fb_like"		=>	true,
					"fb_share"		=>	true,
					"twitter"		=> 	true,
					"twitter_mention" =>	"", // Twitter user who to @ mention in the tweet 
					"twitter_recommend" =>	"", // Twitter user(s) who recommend to follow. Up to 2 separate with : 
					"google_plus"	=>	true,
				);
				
				// Update here, since the following options are in their own option rows in database
				update_option('vmfb_sharing_settings', $this->settings);
			}
		}
		
		/**
		 * Register dahsboard settings. Utilizes Verkkomuikku FB plugin settings page
		 * 
		 */
		public function register_settings() {
			add_settings_section('vmfb_sharing_settings', __('Sharing buttons', 'verkkomuikku'), array(&$this, 'sharing_buttons_settings_section'), 'VerkkomuikkuFbSettings');
			add_settings_field('fb_like', __('Facebook Like, open graph meta tags required.', 'verkkomuikku'), array(&$this, 'sf_use_fb_like'), 'VerkkomuikkuFbSettings', 'vmfb_sharing_settings');			
			add_settings_field('fb_share', __('Facebook Share, open graph meta tags required.', 'verkkomuikku'), array(&$this, 'sf_use_fb_share'), 'VerkkomuikkuFbSettings', 'vmfb_sharing_settings');
			add_settings_field('twitter', __('Twitter', 'verkkomuikku'), array(&$this, 'sf_use_twitter'), 'VerkkomuikkuFbSettings', 'vmfb_sharing_settings');
			add_settings_field('google_plus', __('Google+', 'verkkomuikku'), array(&$this, 'sf_use_google_plus'), 'VerkkomuikkuFbSettings', 'vmfb_sharing_settings');
			
			register_setting( 'VerkkomuikkuFbSettings', 'vmfb_sharing_settings');
		}
		
		/**
		 * Display text for settings section
		 * 
		 */
		public function sharing_buttons_settings_section () {
			?>
				<p><?php _e("Select which sharing options you wan't to show and use template function vmfb_show_sharing_buttons()", "verkkomuikku")?></p>
			<?php 
		}
		
		/**
		 * Echo settings field: Show facebook like button
		 * 
		 */
		public function sf_use_fb_like() {
			$use = $this->settings["fb_like"];
			?>
			<input type="checkbox" name="vmfb_sharing_settings[fb_like]" id="use_fb_like" <?php echo $use ? ' checked="checked"' : ''; ?> value="1"/>
			<?php
		}

		/**
		 * Echo settings field: Show facebook share button
		 * 
		 */
		public function sf_use_fb_share() {
			$use = $this->settings["fb_share"];
			?>
			<input type="checkbox" name="vmfb_sharing_settings[fb_share]" id="use_fb_share" <?php echo $use ? ' checked="checked"' : ''; ?> value="1"/>
			<?php
		}
		
		/**
		 * Echo settings field: Show tweet button
		 * 
		 */
		public function sf_use_twitter() {
			$use = $this->settings["twitter"];
			$mention = $this->settings["twitter_mention"];
			$recommend = $this->settings["twitter_recommend"];
			?>
			<input type="checkbox" name="vmfb_sharing_settings[twitter]" id="use_twitter" <?php echo $use ? ' checked="checked"' : ''; ?> value="1"/><br/>
			<input type="text" name="vmfb_sharing_settings[twitter_mention]" id="twitter_mention" value="<?php echo $mention ?>"/><?php _e("Give Twitter username who to @ mention on the tweet", "verkkomuikku"); ?><br/>
			<input type="text" name="vmfb_sharing_settings[twitter_recommend]" id="twitter_recommend" value="<?php echo $recommend ?>"/><?php _e("Give up to two Twitter username(s) who recommend to be followed separated with (:). Example; username1:username2", "verkkomuikku"); ?>
			<?php
		}	
			
		/**
		 * Echo settings field: Show Google+ button
		 * 
		 */
		public function sf_use_google_plus() {
			$use = $this->settings["google_plus"];
			?>
			<input type="checkbox" name="vmfb_sharing_settings[google_plus]" id="use_google_plus" <?php echo $use ? ' checked="checked"' : ''; ?> value="1"/>
			<?php
		}			
		
		/**
		 * Show Sharing buttons
		 * This should be called within the loop
		 * 
		 * @param array $settings You may override default settings
		 * @return string $html
		 */
		public function get_sharing_buttons() {
			global $vmfeu_fb_connect;
			
			$html = "";
			$providers = $this->settings;
			
			// Nothing to display..
			if ( count($providers) <= 0 )
				return $html;
				
			ob_start();
			
			?>
			<div class="vmfb_sharing_wrapper">
				<ul>
				<?php foreach ($this->settings as $provider => $show) : ?>
					<?php if ("fb_like" == $provider) :
						$share = $this->settings["fb_share"] ? 'send="true"' : '';
						?>
						<li class="vmfb_fb_button">
							<fb:like href="<?php the_permalink() ?>" <?php echo $share; ?> showfaces="false" width="600" layout="button_count" action="like"/></fb:like>
						</li>
					<?php elseif ("twitter" == $provider) : 
						$mention = $this->settings["twitter_mention"] ? 'data-via="'.$this->settings["twitter_mention"].'"' : '';
						$recommend = $this->settings["twitter_recommend"] ? 'data-related="'.$this->settings["twitter_recommend"].'"' : '';
						?>
						<li class="vmfb_twitter_button">
							<a href="http://twitter.com/share" data-count="horizontal" data-url="<?php the_permalink(); ?>" data-text="<?php the_title(); ?>" <?php echo $mention; ?> <?php echo $recommend; ?> class="twitter-share-button">Tweet</a>
						</li>
					<?php elseif ("google_plus" == $provider) : ?>
						<li class="vmfb_google_plus_button">
							<g:plusone size="medium" callback="vmfb_plusone_vote"></g:plusone>
						</li>
					<?php endif;?>

				<?php endforeach;?>
				<?php 				
				/**
				 * Display Sharedaddy here (if it is enabled)
				 * 
				 */
				if (function_exists('sharing_display')) {
					// Force to show the sharedaddy buttons with the filter
					add_filter( 'sharing_show', 'vmfb_show_sharedaddy_sharing_filter', 1, 2);
					$sharedaddy = sharing_display();
					if ('' != $sharedaddy)
						echo '<li class="sharedaddy_buttons">'.$sharedaddy.'</li>';
				}?>
				</ul>
			</div>
			<?php 
			$html = ob_get_contents();
			ob_end_clean();
			
			return $html;
		}
		
		/**
		 * Show like button for the web site
		 * See: http://developers.facebook.com/docs/reference/plugins/like/
		 * 
		 * @param string $action like | recommend
		 * @return string $html
		 * 
		 */
		public function get_site_like($like_url = '', $action = 'like') {
			global $vmfeu_fb_connect;
			
			$html = "";
			$providers = $this->settings;
			
			// Nothing to display..
			if ( !isset($providers['fb_like']) )
				return $html;
				
			// Like this site or some Facebook page / group etc...
			if (!preg_match("#facebook.com#", $like_url) || $like_url == "" || !$like_url)
				$like_url = site_url();

			ob_start();
			
			?>
			<div class="vmfb_site_like_wrapper">
				<fb:like href="<?php echo $like_url; ?>" showfaces="false" width="120" layout="button_count" action="<?php echo $action ?>"/></fb:like>
			</div>
			<?php 
			$html = ob_get_contents();
			
			ob_end_clean();
			
			return $html;
		}
	}
}

$vmfb_sharing = new Vmfb_Sharing();

/**
 * If Sharedaddy is enabled it to behave along with vmfb plugin.
 * Remove filters that adds the sharedaddy sharing buttons, and call 
 * the sharing_display from function vmfb_show_sharing_buttons().
 * 
 * Jetpack version 1.1.3
 */
add_action('wp_head', 'vmfb_disable_sharedaddy_filters'); 
function vmfb_disable_sharedaddy_filters() {
	remove_filter( 'the_content', 'sharing_display', 19 );
	remove_filter( 'the_excerpt', 'sharing_display', 19 );
}

/**
 * Template function to display Sharing buttons
 * 
 * @param string $content
 * @return string $html
 */
add_filter('the_content', 'vmfb_show_sharing_buttons');
function vmfb_show_sharing_buttons($content) {
	global $vmfb_sharing, $post;
	
	// Show only in the loop and single posts
	// TODO: dashboard setting to select to show in pages / posts
	//if (!is_single() || !in_the_loop())
	if (!in_the_loop())
		return $content;
		
	// Don't show for private and drafts
	if ($post->post_status != "publish")
		return $content;
		
	// Let plugins deside
	if (!apply_filters('vmfb_show_sharing_buttons', true))
		return $content;
	
	$html = $vmfb_sharing->get_sharing_buttons();
	
	$return = $content.$html;
	
	return $return;
}

/**
 * Just return true to display the sharedaddy sharing buttons. 
 * This filter is hooked from the template function which determines
 * whether or not to show vmfb buttons.
 * 
 * @param boolean $show
 * @param object $post
 */
function vmfb_show_sharedaddy_sharing_filter( $show, $post ) {
	return true;
}

/**
 * Show like button for the web site
 * 
 * @param string $like_url, if you want to your like button to like something else than this site, provide an Facebook url (page, group.. etc).
 * 
 * @return $html like button
 * 
 */
function vmfb_show_site_like($like_url = '') {
	global $vmfb_sharing;
	
	$html = $vmfb_sharing->get_site_like($like_url);
	
	return $html;	
}

/**
 * Disable these buttons from Sharedaddy (jetpack)
 * Leave only the "hidden" part. 
 * 
 */
add_filter('sharing_services_enabled', 'vmfb_disable_sharedaddy_buttons');
function vmfb_disable_sharedaddy_buttons($blog) {
	global $vmfb_sharing;
	
	// Disable all visible items
	$blog["visible"] = array();
	
	// If there are same items in hidden as vmfb already displays, hide them
	foreach ($blog["hidden"] as $provider => $val) {
		if ($provider == "facebook" && isset($vmfb_sharing->settings['fb_like']))
			unset($blog["hidden"][$provider]);
		elseif ($provider == "twitter" && isset($vmfb_sharing->settings['twitter']))
			unset($blog["hidden"][$provider]);
		elseif ($provider == "google" && isset($vmfb_sharing->settings['google_plus']))
			unset($blog["hidden"][$provider]);
	} 
		
	return $blog;
}
?>