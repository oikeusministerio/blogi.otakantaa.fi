<?php
/*
Plugin Name: Osallistumisympäristö ominaisuuksia
Plugin URI: http://www.verkkomuikku.fi
Description: Räätälöinnit
Author: Teemu Muikku verkkomuikku@gmail.com
Version: 1.1
Author URI: http://
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if (!defined('VERKKOMUIKKU_PLUGIN_URL'))
	define('VERKKOMUIKKU_PLUGIN_URL' , site_url().'/wp-content/plugins/verkkomuikku/');

load_plugin_textdomain("verkkomuikku", PLUGINDIR."/verkkomuikku");

if (!class_exists('Verkkomuikku_Plugin')) {
	
	class Verkkomuikku_Plugin {
		
		var $current_page;
		
		public function __construct () {

		    // include Javascript 
		    add_action('init', array(&$this, 'add_plugin_scripts'));

			add_action('template_redirect', array(&$this, 'set_current_page'));
			
			// Set background information fields and restricted pages etc.
			// Verkkomuikku Front end user plugin settings
			add_filter('vmfeu_user_info_fieldsets', array(&$this, 'filter_user_info_fieldsets'));
			add_filter('vmfeu_user_info_fields', array(&$this, 'filter_user_info_fields'));
			
			// Exclude user from the users list (people page) if user has set 
			// option "exclude me.." from her profile. See filter_user_info_fields
			add_filter('verkkomuikku_users_list_pre_include', array(&$this, 'filter_users_list_user'));
			
			// Restrict access to pages
			add_filter('vmfeu_restrict_access_pages', array(&$this, 'filter_page_access'));
			
			// Google analytics			
			add_action('wp_head', array(&$this, 'google_analytics'));
			
			// Clean wp head 
		    add_action('init', array(&$this, 'disable_wp_head_stuff'));
		    
			// Change "Create account" link text
			add_filter('vmfeu_feedback_texts', array(&$this, 'filter_vmfeu_feedback_texts'));
			
			// Other qtranslate related fixes
			$this->fix_qtranslate_issues();
			
			// Shortcodes
		    add_shortcode( 'antispam', array(&$this, 'encode_email_address_shortcode' ));			
			add_shortcode( 'permalink', array(&$this, 'permalinker_shortcode'));
			add_shortcode( 'links', array(&$this, 'link_list_shortcode'));
			add_shortcode( 'users', array(&$this, 'list_users_shortcode'));
			
			// Do shortcodes for textwidget
			add_filter('widget_text', array(&$this, 'filter_text_widget'), 20, 2);
			
			/**
			 * These affect the layout somehow
			 * 
			 */
			// Settings fields for Dashboard
			add_action('admin_init', array(&$this, 'do_settings_fields'));
			
			// Change tag cloud args
			add_filter('widget_tag_cloud_args', array(&$this, 'filter_widget_tag_cloud_args'));

			// Remove some Dashboard widgets
			add_action('wp_dashboard_setup', array(&$this, 'remove_dashboard_widgets'));
		}

		/**
		 * Removes some WordPress default dashboard widgets.
		 * 
		 */
		function remove_dashboard_widgets() {
			
			// Remove incoming links and quickpress widgets
			remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
			//remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
			
			// Remove WordPress blog feeds
			remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
			remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
		}
		
		
		/**
		 * Add javascript 
		 * 
		 */
		public function add_plugin_scripts() {
			// Replace default jQuery with Google CDN minified jQuery
			// Ei toiminu omguest verkossa!
			//wp_deregister_script( 'jquery' );
    		//wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js');
			
			// Cookie retains font scale for the user
			/* The jquery cookie and fontscale scripts are included in vm.js!
			wp_register_script('jquery_cookie',
		   		plugins_url('/js/jquery.cookie.js', __FILE__),
		       	array('jquery'),
		       	'1.0');
		   
			// Font scale script
			wp_register_script('jquery_fontscale',
		   		plugins_url('/js/jquery.fontscale.js', __FILE__),
		       	array('jquery', 'jquery_cookie', ),
		       	'1.0');
		    */   				

			// Needed only for the frontend
			if (!is_admin()) {
				wp_register_script('vm_script',
			   		plugins_url('js/vm.js', __FILE__),
			       	//array('jquery', 'jquery_cookie','jquery_fontscale'),
			       	array('jquery'),
			       	'1.0');
			       	
				wp_enqueue_script('vm_script');
			}			
		}
		
		/**
		 * Template function to display font resize buttons
		 * 
		 */
		public function fontresize_buttons() {
			echo '<div id="font-resize-wrapper"><div class="button" id="text_size_reset">A</div><div class="button" id="text_size_up">A</div></div>';
		}		
		
		/**
		 * Wordpress prints all sorts of stuff into the header that we don't need
		 * 
		 */
		public function disable_wp_head_stuff() {
			// From wp-includes/default-filters.php
			//remove_action( 'wp_head',             'feed_links',                    2     );
			//remove_action( 'wp_head',             'feed_links_extra',              3     );
			remove_action( 'wp_head',             'rsd_link'                             );
			remove_action( 'wp_head',             'wlwmanifest_link'                     );
			remove_action( 'wp_head',             'parent_post_rel_link',          10, 0 );
			remove_action( 'wp_head',             'start_post_rel_link',           10, 0 );
			remove_action( 'wp_head',             'adjacent_posts_rel_link_wp_head', 10, 0 );
			remove_action( 'wp_head',             'wp_generator'                         );			
		}
		
		/**
		 * General init tasks
		 *  
		 */
		public function init_attributes() {
		}
		
		/**
		 * Include Google Analytics
		 * 
		 */
		public function google_analytics() {
			
			if( $_SERVER["HTTP_HOST"] == "blogi.otakantaa.fi") : 
			?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-25875985-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
			<?php endif; 
		}
				
		/**
		 * Set current page
		 *  - this is here since get_the_id() may return post id or forum id when called from sidebar template
		 *  
		 */
		public function set_current_page() {
			global $wp_query;
			if (!$wp_query || !$wp_query->queried_object || "page" != $wp_query->queried_object->post_type)
				$this->current_page = false;
			
			$this->current_page = $wp_query->queried_object_id;
		}

		/**
		 * Use page sidebar or default
		 *
		 * @param $page_id - use get_the_id() in template file 
		 * @param $default_sidebar - default sidebar name, in twenty ten it is primary-widget-area 
		 * 
		 */
		public function page_sidebar($default_sidebar = 'primary-widget-area') {
			$sidebar = false;

			if ($this->current_page)
				$sidebar = dynamic_sidebar("page-sidebar-".$this->current_page);
			
			if (!$sidebar)
				$sidebar = dynamic_sidebar($default_sidebar);
			
			return $sidebar;
		}
		
		/**
		 * Shortcode to encode email addresses
		 */
		public function encode_email_address_shortcode($atts, $content = null) {
			/* No atts in use
			extract( shortcode_atts( array(
				'foo' => 'something',
				'bar' => 'something else',
			), $atts ) );
			*/
			// WP antispambot function wp-includes/formatting.php
			return antispambot($content);
		}
				
		/**
		 * Shortcode that generates permalink from page/post id
		 * 
		 * @param $atts
		 * @param $content
		 * 
		 * @return permalink to a page / post
		 */
		public function permalinker_shortcode($atts, $content = null) {
			extract(shortcode_atts(array(
				'url' => "",
				'id' => null,
				'target' => null,
				'class' => null,
				'rel' => null
			), $atts));
			
			if ( empty($id) ) {
				$id = get_the_ID();
			}
			
			// Apply filter so translations etc. work
			$content = apply_filters('the_title',trim($content));
			
			if ( !empty($content) ) {
				$link_url = !empty($url) ? $url : get_permalink($id); 
				$output = '<a href="' .  $link_url . '"';
				if ( !empty($target) ) {
					$output .= ' target="' . $target . '"';
				}
				$output .= ' class="permalinker_link';
				if ( !empty($class) ) {
					$output .= " $class";
				}
				$output .= '"';
				if ( !empty($rel) ) {
					$output .= ' rel="' . $rel . '"';
				}
				$output .= '>' . $content . '</a>';
			}
			else {
				$output = get_permalink($id);
			}
			return $output;
		}
		
		/**
		 * Shortcode to display link lists
		 * 
		 * @param array $atts
		 * @param string $content not used
		 * 
		 * @return string $html Link list
		 */
		public function link_list_shortcode($atts, $content = null) {
			$atts = shortcode_atts(array(
				'orderby' => 'name', 
				'order' => 'ASC',
				'limit' => -1, 
				'category' => '', 
				'exclude_category' => '',
				'category_name' => '', 
				'hide_invisible' => 1,
				'show_updated' => 0, 
				'echo' => 1,
				'categorize' => 0, // Set to 1 if you'd like to show link categories 
				'title_li' => "", // If empty together with categorized set to 0, no titles are shown
				'title_before' => '<h2>', 
				'title_after' => '</h2>',
				'category_orderby' => 'name', 
				'category_order' => 'ASC',
				'class' => 'linkcat', 
				'category_before' => '<li id="%id" class="%class">',
				'category_after' => '</li>',
				// These are from _walk_bookmarks
				'show_description' => 0,
				'show_images' => 1, 
				'show_name' => 0,
				'before' => '<li>', 
				'after' => '</li>', 
				'between' => "\n",
				'show_rating' => 0, 
				'link_before' => '', 
				'link_after' => ''				
			), $atts);

			// Shortcode should return its output, not echo
			ob_start();
			
			wp_list_bookmarks(apply_filters('verkkomuikku_shortcode_links_args', $atts));

			$html = ob_get_contents();
			ob_end_clean();
			
			return $html;
		}
		
		
		/**
		 * Shortcode to display users as a list. Taken mostly from
		 * wp_list_authors function.  Can get users by ids, use include argument.
		 * 
		 * @param array $atts
		 * @param string $content not used here
		 * 
		 * @return string $html users list
		 */
		function list_users_shortcode($atts = '', $content = "") {
			global $wpdb;
		
			$defaults = array(
				'orderby' => 'name', 
				'order' => 'ASC', 
				'number' => '',
				'optioncount' => false, 
				'exclude_admin' => true,
				'show_fullname' => true, 
				'hide_empty' => false,
				'feed' => '',
				'feed_image' => '', 
				'feed_type' => '',
				'link_to' => '', // By deafault users link to their respective public profile. Giving a link here, every link are now pointing to this url.
				'link_title' => '', // By default "Post by %s", override with single title to all (handy with the link_to attrivute) 
				'echo' => false, // Don't echo, this is shortcode!
				'style' => 'block_list', // block_list | list | ''
				'html' => true,
				'include' => '', // User IDs, comma separated
				'show_name' => true, // Show user name 
				'show_description' => true, // Show user description
				'avatar' => true, // Show avatar
				'avatar_size' => 60 // width in pixels
			);
		
			$args = wp_parse_args( $atts, $defaults );
			extract( $args, EXTR_SKIP );
		
			$return = '';
		
			$query_args = wp_array_slice_assoc( $args, array( 'include', 'orderby', 'order', 'number' ) );
			$query_args['fields'] = 'ids';
	
			$authors = get_users( $query_args );

			$author_count = array();
			foreach ( (array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row )
				$author_count[$row->post_author] = $row->count;
		
			foreach ( $authors as $key => $author_id ) {
				$author = get_userdata( $author_id );
		
				// Allow authors to be excluded by plugins
				if (!apply_filters('verkkomuikku_users_list_pre_include', $author))
					continue;
					
				if ( $exclude_admin && 'admin' == $author->display_name )
					continue;
		
				$posts = isset( $author_count[$author->ID] ) ? $author_count[$author->ID] : 0;
		
				if ( !$posts && $hide_empty )
					continue;
		
				$link = '';
		
				if ( $show_fullname && $author->first_name && $author->last_name )
					$name = "$author->first_name $author->last_name";
				else
					$name = $author->display_name;
		
				if ( !$html ) {
					$return .= $name . ', ';
		
					continue; // No need to go further to process HTML.
				}
				
				// Author links to...
				$author_link_url = $link_to != '' ? $link_to : get_author_posts_url( $author->ID, $author->user_nicename );
				$author_link_title = $link_title != '' ? $link_title : esc_attr( sprintf(__("Posts by %s", "verkkomuikku"), $author->display_name) );
				
				// For osy, three in a row
				$row_class = $key % 3 == 0 ? 'first' :'';
				
				if ( 'list' == $style ) {
					$return .= '<li class="'.$row_class.'">';
				} else if ('block_list' == $style) {
					$return .= '<div class="author-info '.$row_class.'">';
				}
				
				if ($avatar) {
					$avatar = get_avatar( $author->user_email, apply_filters( 'author_bio_avatar_size', $avatar_size ) );
					
					// Put link to the avatar
					$avatar = '<a href="' .$author_link_url. '" title="' .$author_link_title. '">' . $avatar . '</a>';
					$return .= '<div class="author-avatar">'.$avatar.'</div>';
				}
				
				$link = '<div class="author-description">';
				
				// Add authors name and link to her posts
				if ($show_name)
					$link .= '<h2><a href="' .$author_link_url. '" title="' .$author_link_title. '">' . $name . '</a></h2>';
				
				// Add description
				if ($show_description && $author->description) {
					$adesc = $author->description;
					if (strlen($adesc) > 43) {
						$adesc = mb_substr($adesc,0,40) . '...';
					}					
					$link .= $adesc;
				}
				$link .= '</div>';
				
				// Allow plugins to add more stuff before the list entry end tag
				$link = apply_filters('verkkomuikku_users_list', $link, $author);
						
				if ( !empty( $feed_image ) || !empty( $feed ) ) {
					$link .= ' ';
					if ( empty( $feed_image ) ) {
						$link .= '(';
					}
		
					$link .= '<a href="' . get_author_feed_link( $author->ID ) . '"';
		
					$alt = $title = '';
					if ( !empty( $feed ) ) {
						$title = ' title="' . esc_attr( $feed ) . '"';
						$alt = ' alt="' . esc_attr( $feed ) . '"';
						$name = $feed;
						$link .= $title;
					}
		
					$link .= '>';
		
					if ( !empty( $feed_image ) )
						$link .= '<img src="' . esc_url( $feed_image ) . '" style="border: none;"' . $alt . $title . ' />';
					else
						$link .= $name;
		
					$link .= '</a>';
		
					if ( empty( $feed_image ) )
						$link .= ')';
				}
		
				if ( $optioncount )
					$link .= ' ('. $posts . ')';
		
				$return .= $link;
				
				if ( 'list' == $style ) 
					$return .= '</li>';
				elseif ( 'block_list' == $style ) 
					$return .= '</div>';
				else
					$return .= ', ';
			}
		
			$return = rtrim($return, ', ');
		
			if ( !$echo )
				return $return;
		
			echo $return;
		}		
		
		/**
		 * Filter WP widget Text text
		 * Do shortcodes
		 * 
		 * @param string $text
		 * @param object $widget
		 */
		public function filter_text_widget($text, $widget) {
			return do_shortcode($text);
		}
		
		public function filter_vmfeu_feedback_texts($texts) {
			$texts["login_label_create_account"] = __("Create account", "verkkomuikku");
			return $texts;
		}
		
		/**
		 * Translate this one line for better qTranslate feedback
		 * 
		 * @param string $text
		 * 
		 * @return string $translated text that current content is not available in users language
		 */
		public function filter_verkkomuikku_content_language_sorry_text($text) {
			return __("Sorry, the content is not available in English.", "verkkomuikku");
		}
		
		/**
		 * Home link in the nav menu doesn't contain the qtranslate language tag
		 * and thus resets the language... Converts other "custom" type links as well
		 * 
		 * @param object $menu_item The menu item
		 * @return object The (possibly) modified menu item
		 */
		public function filter_home_nave_menu_item( $menu_item ) {
			if ($menu_item->object == "custom" && function_exists('qtrans_convertURL')) {
				$menu_item->url = qtrans_convertURL($menu_item->url);
			}
			return $menu_item;
		}
		
		/**
		 * Sets users background info fields for the needs of Osallistumisympäristö
		 * Check out Verkkomuikku-front-end-user-plugin
		 * 
		 * @param array $fields - array of user info fields
		 */
		public function filter_user_info_fields($fields) {
			
			// Exclude from users list filters the user out from the users shortcode users list
			$fields["public_profile"] = new Vmfeu_Checkbox_Field(array(
									"name" 	=> "public_profile",
									"title" => "",
									"value_label_pairs" => array(
											"exclude_from_users_list" => array("value" => "1", "label" => __("Exclude me from the Participants page", "verkkomuikku")),									
											),
									));
			return $fields;
		}
		
		/**
		 * Put user info fields into fieldsets so there can be
		 * message / helper text per fieldset
		 * 
		 * @param array $fieldsets - array of fieldsets that enclose user info fields under a header
		 */
		public function filter_user_info_fieldsets($fieldsets) {
			
			$fieldsets["more_info"]["title"] = __("Public profile", "verkkomuikku");
			$fieldsets["more_info"]["fields"] = array("public_profile");			
			
			return $fieldsets;
		}
			
		/**
		 * Exclude user from the users shortcode that generates list
		 * of users. This filter is used for exluding users that
		 * don't want their faces to show on the "Participants" page
		 * 
		 * @param object $author author info
		 * @return object $author | null return null if you want to exclude the user
		 */
		public function filter_users_list_user($author) {
			// Osallistumisympäristö - users have meta field
			// exclude_from_users_list if they don't want them selves to show 
			// on the list
			if (isset($author->public_profile) 
					&& is_array($author->public_profile) 
					&& isset($author->public_profile['exclude_from_users_list'])) {

				return null;
			}
			
			return $author;
		}		
		/**
		 * Require certain user capability to enter a page
		 * 
		 * @param $restricted_pages, page id as key, user capability as value
		 */
		public function filter_page_access($restricted_pages) {
			
			// Restrict the experiment pages for Wiki, content loop and landing page
			$restricted_pages[1452] = 'publish_posts'; // wiki page, blogi.otakantaa.fi/tyylit
			return $restricted_pages;
		}
		
		/**
		 * Function to bundle qtranslate related stuff
		 * 
		 */
		public function fix_qtranslate_issues() {
			// If qtranslate is in use, load it first
			add_action('plugins_loaded', array(&$this, 'load_qtranslate_first'));

			// No qtranslate
			if (!function_exists('qtrans_init'))
				return;
				
			// qTranslate time and date conversions suck for some reason..
			remove_filter('get_comment_date',		'qtrans_dateFromCommentForCurrentLanguage',0,2);
			remove_filter('get_comment_time',		'qtrans_timeFromCommentForCurrentLanguage',0,4);
			remove_filter('get_post_modified_time',	'qtrans_timeModifiedFromPostForCurrentLanguage',0,3);
			remove_filter('get_the_time',			'qtrans_timeFromPostForCurrentLanguage',0,3);
			remove_filter('get_the_date',			'qtrans_dateFromPostForCurrentLanguage',0,4);			
			
			// qTranslate doesn't have url convert filter for wp home_url function.
			// home_url is used a lot by WP functions and also by Twenty eleven theme.
			// home_url must be filtered after wp object parse_request, otherwise 
			// things won't work (messes base url).
			add_action('parse_request', array(&$this, 'start_qtranslate_url_filtering'));
			
			// Need to filter site_url too
			// This is tricky since site_url is used to target root files (such as wp-comments-post.php and wp-login.php)
			// We cannot use the QT_URL_PATH type here...
			// add_filter('site_url', array(&$this, 'filter_qtranslate_convert_site_url'), 100, 4);
			
			// But, for example wp-comments-post is loaded wihtout wp object so home_url
			// doesn't receive the filter..
			// Just add redirect_to hidden field with current url, comment form can utilize that
			// to maintain language.
			add_action('comment_form', array(&$this, 'add_comment_form_redirect'));
			
			// qTranslate shows language switch link if current content is not available
			// in current language. I prefer it to show text that "sorry this is not available in English"
			// and still display the text in default language.
			// Edit qTranslate_core.php, function qtrans_use(), at the bottom before last return add
			// $return = "<p>".apply_filters('verkkomuikku_content_language_sorry_text', '')."</p>";
			// $lang_text = $content[$q_config['default_language']];
			// if ($lang_text) 
			//	return $return.$lang_text;
			add_filter('verkkomuikku_content_language_sorry_text', array(&$this, 'filter_verkkomuikku_content_language_sorry_text'));

			// Main menu home link doesn't contain the language attribute...
			add_filter('wp_setup_nav_menu_item', array(&$this, 'filter_home_nave_menu_item'));
		}
		
		/**
		 * add qTranslate qtrans_convertURL to home_url.
		 * 
		 * @param object $wp no need to use this to anything here
		 */
		public function start_qtranslate_url_filtering($wp) {
			add_filter('home_url', array(&$this, 'filter_help_qtranslate_convert_home_url'), 100, 4);
			
			// qTranslate 2.5.24, 28.7.2011
			// These are not needed anymore after we have hooked home_url
			// 
			// These filters qtranslate didn't even hook to
			// - post_type_link
			// - attachment_link
			// - taxonomy_feed_link
			// - search_link
			// - post_type_archive_link
			
			remove_filter('author_feed_link',				'qtrans_convertURL');
			remove_filter('author_link',					'qtrans_convertURL');
			remove_filter('author_feed_link',				'qtrans_convertURL');
			remove_filter('day_link',						'qtrans_convertURL');
			
			remove_filter('month_link',					'qtrans_convertURL');
			remove_filter('page_link',						'qtrans_convertURL');
			remove_filter('post_link',						'qtrans_convertURL');
			remove_filter('year_link',						'qtrans_convertURL');
			remove_filter('category_feed_link',			'qtrans_convertURL');
			remove_filter('category_link',					'qtrans_convertURL');
			remove_filter('tag_link',						'qtrans_convertURL');
			remove_filter('term_link',						'qtrans_convertURL');
			remove_filter('the_permalink',					'qtrans_convertURL');
			remove_filter('feed_link',						'qtrans_convertURL');
			remove_filter('post_comments_feed_link',		'qtrans_convertURL');
			remove_filter('tag_feed_link',					'qtrans_convertURL');
			remove_filter('get_pagenum_link',				'qtrans_convertURL');		

			remove_filter('bloginfo_url',					'qtrans_convertBlogInfoURL',10,2);			
		}
		
		/**
		 * site_url must also be filtered, but only domain and query url_modes work of the self
		 * 
		 * @param string $url
		 * @param string $path
		 * @param string $orig_scheme
		 * @param string $blog_id
		 */
		public function filter_qtranslate_convert_site_url($url, $path = "", $orig_scheme = "", $blog_id = "") {
			global $q_config;
			
			// CHange url mode temporarily in to QT_URL_QUERY
			if (QT_URL_PATH == $q_config['url_mode']) {
				$q_config['url_mode'] = QT_URL_QUERY;
				$url = qtrans_convertURL($url);
				$q_config['url_mode'] = QT_URL_PATH;
				return $url;
			} else {
				return qtrans_convertURL($url);
			}
		}
		
		/**
		 * Arrange active plugins array so that qTranslate is initiated first
		 * 
		 */
		public function load_qtranslate_first() {
			// No qTranslate at all
			if (!function_exists('qtrans_init'))
				return;
			
			$active_plugins = get_option('active_plugins');
			
			// if qTranslate is not first, arrange
			if (preg_match('#^qtranslate#', $active_plugins[0])) 
				return; 
				
			foreach ($active_plugins as $key => $p) {
				if (preg_match('#^qtranslate#', $p)) {
					unset($active_plugins[$key]);
					update_option('active_plugins', array_merge(array($p), $active_plugins));
					return;
				}
			}
			return;
			
		}
		
		/**
		 * Just pass the url to qtrans_convertURL
		 * 
		 * @param string $url
		 * @param string $path
		 * @param string $orig_scheme
		 * @param string $blog_id
		 * 
		 * @return string $url Converted url
		 */
		public function filter_help_qtranslate_convert_home_url($url, $path = "", $orig_scheme = "", $blog_id = "") {
			return qtrans_convertURL($url);
		}
		
		/**
		 * Include current url, that has language in it, as hidden field
		 * so that posting a comment will redirect back with current language.
		 * 
		 * @param int $post_id
		 */
		public function add_comment_form_redirect($post_id) {
			?>
				<input type="hidden" name="redirect_to" value="<?php echo get_permalink($post_id); ?>"/>
			<?php 
		}
		
		/**
		 * Change tag cloud widget argumets.
		 * The largest, by default, is 22pt which is a tad too big...
		 * 
		 * @param array $args
		 * @return array $args
		 */
		public function filter_widget_tag_cloud_args($args) {
			$args['largest'] = 18;
			return $args;
		}
		
		/**
		 * Add some settings to the dashboard
		 * 
		 */
		public function do_settings_fields() {
			
			// Add field to choose how many posts are shown fully in frontpage
			// and after that show only titles. 
			// Note: Edit your loop template accordingly to take advantage of this setting.
			add_settings_field('vm_minimize_posts_in_loop_after', __('Number of posts to show fully after which show only titles.', 'verkkomuikku'), array(&$this, 'sf_minimize_posts_after'), 'reading');
			register_setting( 'reading', 'vm_minimize_posts_in_loop_after');
			
			// Default setting
			if (!get_option('vm_minimize_posts_in_loop_after'))
				update_option('vm_minimize_posts_in_loop_after', get_option('posts_per_page'));
				
			// Select which category to show in full (in front page) and minimize others.
			// Note: Edit your loop template accordingly to take advantage of this setting.
			add_settings_field('vm_minimize_other_categories_in_loop', __('Select the main category for front page. Posts from other categories are minimized.', 'verkkomuikku'), array(&$this, 'sf_minimize_posts_category'), 'reading');
			register_setting( 'reading', 'vm_minimize_other_categories_in_loop');
		}
		
		/**
		 * Display function for the vm_minimize_posts_in_loop setting
		 * 
		 */
		public function sf_minimize_posts_after() {
			?>
			<input type="text" size="4" name="vm_minimize_posts_in_loop_after" id="vm_minimize_posts_in_loop_after" value="<?php echo get_option('vm_minimize_posts_in_loop_after'); ?>"/><small><?php _e("Set posts per page from 10 to 100 and use this setting to minimize posts to minimize scrolling.", "verkkomuikku"); ?></small>
			<?php		
		}
		
		/**
		 * Display function for the vm_minimize_posts_in_loop setting
		 * 
		 */		
		public function sf_minimize_posts_category() {
			wp_dropdown_categories(array(
				'show_option_all' 	=> __("Show all categories", 'verkkomuikku'),
				'name' 				=> 'vm_minimize_other_categories_in_loop',
				'id'				=> 'vm_minimize_other_categories_in_loop',
				'selected'			=> get_option('vm_minimize_other_categories_in_loop')
			));
			?>
			<small><?php _e('Select "Show All Categories" and all posts are shown fully.', 'verkkomuikku'); ?></small>
			<?php 
		}
	}
}
$VerkkomuikkuPlugin = new Verkkomuikku_Plugin();

/**
 * This function helps to get strings into my own .po file
 * 
 * @param $key key into the array
 * 
 * @return $translated string
 */
function verkkomuikku_translateable_text($key) {
	$strings = array(
		"newsletter_archive_title" => __("Sent Newsletters", "verkkomuikku"),
		"subscribe_to_newsletter" => __('Did you like the article? <a href="%s" title="Subscribe email newsletter">Subscribe our email newsletter</a> to stay updated with new articles!', "verkkomuikku")
	);
	
	if (isset($strings[$key]))
		return $strings[$key];
	else
		return ""; // TODO: send verkkomuikku admin email			
}

/**
 * Translate string with qTranslate
 * Usefull for external plugins that aren't qtranslate compatible such as Yoast SEO...
 * 
 */
function verkkomuikku_translate($string) {
	if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage'))
		return qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($string);
	else
		return $string;
}
// These are from Yoast SEO plugin
add_filter('wpseo_title', 'verkkomuikku_translate');
add_filter('wpseo_metadesc', 'verkkomuikku_translate');

/**
 * Include ALO EasyMail custom hooks
 * 
 */
if (function_exists('alo_em_install')) {
	require_once('ALO-easymail-custom.php');
}

/**
 *  Override twentyeleven theme functions that are not needed
 */
/**
 * Disable some unnecessary inline styles regarding customizable header
 */
if (!function_exists('twentyeleven_header_style')) {
	function twentyeleven_header_style() {
		return;
	}
}
?>