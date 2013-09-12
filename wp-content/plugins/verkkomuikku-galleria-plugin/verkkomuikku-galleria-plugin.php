<?php
/*
Plugin Name: Verkkomuikku Galleria plugin - Uses http://galleria.io/ v1.2.9
Plugin URI: http://www.verkkomuikku.fi
Description: Content slider: slide posts / images / html
Author: Teemu Muikku verkkomuikku@gmail.com
Version: 1.3
Author URI: http://www.verkkomuikku.fi
*/

/**
 * HUOM! Kun päivität galleria IO:n (tai WP:n!), pidä huoli 
 * että juuressa olevat symlinkit toimii!
 * galleria.js > wp-content/plugins/verkkomuikku-galleria-plugin/js/embed.js
 * galleria.io.js > wp-content/plugins/verkkomuikku-galleria-plugin/galleria/galleria-1.2.9.js (tai mikä versio ny sattuukaa olee)
 * galleria.io.theme.js > wp-content/plugins/verkkomuikku-galleria-plugin/galleria/themes/content-slider/galleria.content_slider.js (tai mikä teema ny sattuukaa olee)
 * galleria.io.theme.css > wp-content/plugins/verkkomuikku-galleria-plugin/galleria/themes/content-slider/galleria.content_slider.css (tai mikä teema ny sattuukaa olee)
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if (!defined('VERKKOMUIKKU_GALLERIA_PLUGIN_URL'))
	define('VERKKOMUIKKU_GALLERIA_PLUGIN_URL' , WP_CONTENT_URL.'/plugins/verkkomuikku-galleria-plugin/');

load_plugin_textdomain("verkkomuikku", PLUGINDIR."/verkkomuikku-galleria-plugin");

/**
 * This class incorporates content slider based on Galleria jQuery slider
 * http://galleria.aino.se/ 
 * 
 */
if (!class_exists('Verkkomuikku_Galleria_Plugin')) {
	
	class Verkkomuikku_Galleria_Plugin {
		
		/**
		 * Holds posts that are looped in the 
		 * Galleria
		 * 
		 * The posts are queried based on the shortcode
		 * arguments, or, you can set the posts from outside
		 * and then call the shortcode function to display the
		 * gallery!
		 * 
		 * @var array
		 */
		var $posts = array();
		
		/**
		 * You can show multiple galleries on one page, 
		 * the $index will change for each to provide
		 * unique HTML id attributes.
		 * 
		 * @var int
		 */
		var $index = 1;
		
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
		public function init() {
			
			// Set theme for the Galleria.io
			$this->theme = apply_filters('vm_galleria_theme', 'content_slider'); // classic | content_slider
			
			// Load this plugin as soon as possible before other plugins.
			// let qTranslate load first though ;)
			add_action('init', array(&$this, 'load_galleria_second'));
			
		    // include Javascript 
		    add_action('init', array(&$this, 'add_plugin_scripts'));
		    
		    add_action('wp_print_styles', array(&$this, 'add_plugin_styles'));
		    
			// Shortcodes
		    add_shortcode( 'vm_galleria', array(&$this, 'show_galleria_shortcode' ));
		    
		    // Filter out a Carousel category on frontpage main query and from feeds
		    add_action('pre_get_posts', array(&$this, 'filter_out_the_category'));
		    
		    // Check if a request wants some data for otakantaa.fi
		    add_action('wp_ajax_vm_galleria_data', array(&$this, 'data_for_remote'));
			add_action('wp_ajax_nopriv_vm_galleria_data', array(&$this, 'data_for_remote'));
			
			// Allow otakantaa.fi to use WP ajax API
			add_filter('allowed_http_origins', array(&$this,'filter_allowed_origins'));

		}
		
		/**
		 * Arrange active plugins array so that Galleria plugins is initiated second 
		 * directly after qTranslate. (qTranslate needs to be loaded first for translations
		 * to work in other plugins).
		 * 
		 * This relates to issue with Aino Galleria jquery slider library and in general
		 * with browser restrictions. Loading the javascript as early as possible in the
		 * dom gives better change for the slider to work right (especially with IE).
		 * 
		 * see: http://getsatisfaction.com/galleria/topics/js_fatal_error_is_raised_on_1st_load_in_ie7_8
		 * 
		 */
		public function load_galleria_second() {
			
			$active_plugins = get_option('active_plugins');
			
			// if Galleria plugin is not second, arrange
			if (preg_match('#^verkkomuikku-galleria-plugin#', $active_plugins[1])) 
				return; 
			
			$the_rest = array();
			$priority_plugins=array();
			foreach ($active_plugins as $key => $p) {
				// Let qtranslate be the first
				if ($key == 0) {
					$priority_plugins[] = $p;

				// Then comes the galleria plugin as second
				} elseif (preg_match('#^verkkomuikku-galleria-plugin#', $p)) {
					$priority_plugins[] = $p;
					
				// And the rest
				} else {
					$the_rest[] = $p;
				}
				
			}
			
			// Combine and update
			update_option('active_plugins', array_merge($priority_plugins, $the_rest));
			return;
		}		
		
		/**
		 * Include Looped slider javascript library 
		 * 
		 */
		public function add_plugin_scripts () {

			if (is_admin())
				return;
				
			// Load Aino galleria script
			wp_register_script('galleria_io_script',
		   		plugins_url('/galleria/galleria-1.2.9.js', __FILE__),
		       	array('jquery'),
		       	'1.2.9');
		       	
		    wp_enqueue_script('galleria_io_script');
		    
			// Load theme that uses attributes from this plugin
			wp_register_script('verkkomuikku_galleria_theme_script',
		   		plugins_url('/galleria/themes/'.$this->theme.'/galleria.'.$this->theme.'.js', __FILE__),
		       	array('galleria_io_script', 'jquery'),
		       	'1.2.9');

			// enqueue the script
			wp_enqueue_script('verkkomuikku_galleria_theme_script');	   
		}
		
		/**
		 * Add plugin styles
		 * 
		 */
		public function add_plugin_styles() {
			$styleUrl = plugins_url('galleria/themes/'.$this->theme.'/galleria.'.$this->theme.'.css', __FILE__);
			wp_register_style('galleria_theme_style', $styleUrl);
			wp_enqueue_style( 'galleria_theme_style' );			
		}
		
		/**
		 * Output HTML for the galleria
		 * for Galleria attributes, see http://galleria.aino.se/docs/
		 * 
		 * @param array $atts
		 * @param string $content
		 * 
		 * @return string $html
		 */
		public function show_galleria_shortcode($atts, $content = '') {

			$defaults = apply_filters('vmg_shortcode_defaults', array(
				
				// Display
				"title"				=> false, // Show post title
				"excerpt"			=> false, // Show post excerpt
				"width"				=> "auto", // Detected from containing element
				"height"			=> "0.5625", // Responsive 16/9 ratio
				"show_nav"			=> true,
				"timthumb"			=> false,
				
				// Link?
				"use_link"			=> false,
			
				// What posts ?
				"post_id"			=> "", // Slide images from singe post
				"cat"				=> "", // Slide posts from categories (see query_posts)
				"tag"				=> "", // Slide posts with tags (see query_posts)
				"showposts"			=> 5,  // How many to get		
				"orderby"			=> "date",
				"order"				=> "DESC",
			
				// Show post content, set your WordPress theme template (and template part)
				"show_content"		=> true,
				"content_template"	=> 'content',
				"content_template_part"	=> 'slider',
				"content_default_image_url"	=> VERKKOMUIKKU_GALLERIA_PLUGIN_URL.'/slider_default.png', // The content won't display without an image. Please define a default image. 
			
				// Some of the galleria attributes
				"responsive"		=> "true",
				"swipe"				=> "true",
		        "transition" 		=> "slide",
		        "transitionspeed" 	=> "400",
		        "autoplay" 			=> "10000",
		        "thumbcrop" 		=> "false",
		        "imagecrop" 		=> "'width'",
		        "carousel" 			=> "false",
		        "imagepan" 			=> "true",
		        "clicknext" 		=> "false",
		        "thumbnails" 		=> "empty",
		        "showinfo" 			=> "false",
		        "showcounter" 		=> "false",
			));
			
			// Allow GET parameters prefixed vmg_{attribute}
			foreach ($defaults as $att => $val) {
				if (isset($_GET['vmg_'.$att]))
					$atts[$att] = filter_input(INPUT_GET, 'vmg_'.$att, FILTER_SANITIZE_STRING);
			}

			$atts = shortcode_atts( $defaults, $atts );
			
			extract($atts);
			
			// Use current post if no cat / tag / post_id set
			if (empty($post_id) && empty($cat) && empty($tag))
				$post_id = get_the_ID();
				
			
			$html = "";
			
			ob_start();
			?>
			<div id="vm_galleria_wrapper_<?php echo $this->index; ?>" class="vm_galleria_wrapper">
			      
				<?php
				$args = array('posts_per_page' => $showposts, 'orderby' => $orderby, 'order' => $order, 'post_type' => 'post', 'post_status' => 'publish');
				
				// Get single post attached images
				if ($post_id) {

				// Get posts
				} else {
					if ($cat != "") {
						$args['cat'] = $cat;
					}
					
					if ($tag != "") {
						$args['tag'] = $tag;
					}

				}

				// Display images from a single post
			    if ($post_id) {
			    	
					$attachments = get_attached_media( 'image', $post_id );
					    	
			    	if ($attachments) {
						foreach ( $attachments as $id => $attachment ) {
							
				        	unset($img_src); 
							$thumbURL = wp_get_attachment_image_src( $id, '' );
							$thumb = wp_get_attachment_image($id, '');
					        $img_src = $thumbURL[0];  
							$img_alt   = trim(strip_tags( get_post_meta($id, '_wp_attachment_image_alt', true) ));
							$img_title = trim(strip_tags( $attachment->post_title ));
			        
				        	$link = $use_link ? 'data-link="'.$img_src.'"' : '';
				 			
							if ($timthumb) 
								$src = plugins_url('/include/timthumb.php',__FILE__).'?src='.$img_src.'&amp;h='.$height.'&amp;w='.$width.'&amp;zc=1';
							else
								$src = $img_src;
							?><img class="slider_content" src="<?php echo $src; ?>" height="<?php echo $height; ?>" alt="<?php echo $img_alt; ?>" <?php echo $link; ?> title="<?php echo $img_title; ?>" width="<?php echo $width; ?>"/><?php
						}
			    	}
			    	
			    // Get posts using a query. Display image from each post.
			    // Can also display the whole post content (html) if you
			    // set the argument $show_content = true
			    } else {
			    	
			    	// If the posts aren't set, query
			    	if (empty($this->posts)) {
			    		$galleria_query = new WP_query($args);
			    		
						$this->posts = $galleria_query->posts;
						unset($galleria_query);
			    	}
			    	
			    	if (!empty($this->posts)) {
				    	global $post;
				    	
				    	// Don't show verkkomuikku and jetpack sharing buttons
				    	remove_filter('the_content', 'vmfb_show_sharing_buttons');
				    	remove_filter( 'the_content', 'sharing_display', 19 );
				    	remove_filter( 'the_excerpt', 'sharing_display', 19 );
				    	
						foreach ($this->posts as $post) {
							
							setup_postdata($post);
		
				        	unset($img_src);

				        	// show_content = show a post in the gallery. Thus, try to 
				        	// use a default image first (blank image not to interfere with the content).
				        	// The post images are shown withing the layer (HTML content).
				        	if ($show_content) {
				        		$img_src = $content_default_image_url;
				        		//$use_link = false;
				        		
				        	// Else use the post thumbnail as the image
				        	} elseif ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() ) {
								$thumbURL = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), '' );
					            $img_src = $thumbURL[0];
				        					        		
					        // Or any image in the post if no post thumbnail.
				        	} else {
								$img_src = $this->catch_that_image($post->ID);
				        	}
				        	
				        	// Cannot output without image, the Aino Galleria won't work.
				 			if ($img_src) {
				 				
					        	$img_title = trim(strip_tags(get_the_title()));
					        	$img_alt = trim(strip_tags(get_the_excerpt()));
					        	
					        	// Link to the post
					        	$link = $use_link ? 'data-link="'.get_permalink().'"' : '';				 				
				 				
				 				if ($timthumb) 
				 					$src = plugins_url('/include/timthumb.php',__FILE__).'?src='.$img_src.'&amp;h='.$height.'&amp;w='.$width.'&amp;zc=1';
				 				else
				 					$src = $img_src;
				 				
								?><img class="slider_content" src="<?php echo $src; ?>" <?php echo $link; ?> title="<?php echo $img_title; ?>" alt="<?php echo $img_alt ?> " width="<?php echo $width; ?>" height="<?php echo $height; ?>" /><?php
					    		
								if ($show_content) {
					    			echo '<div class="layer">';
									get_template_part( $content_template, $content_template_part );
									echo '</div>';
								}
									     							
				 			} // if an image exists
				 			
						} // endforeach have_posts
						
						// Restore verkkomuikku sharing buttons filter
						// Also jetpack sharedaddy sharing buttons
						add_filter('the_content', 'vmfb_show_sharing_buttons');
						add_filter( 'the_content', 'sharing_display', 19 );
						add_filter( 'the_excerpt', 'sharing_display', 19 );
						
						wp_reset_query();
						
			    	}// have posts
		        	
				} // If else to determine what to display, single post attachments or posts loop ?>
			</div>
<script type="text/javascript">//<![CDATA[
(function() {
    jQuery(document).ready(function() {
	

	    
	    // Initialize Galleria
	    Galleria.run('#vm_galleria_wrapper_<?php echo $this->index; ?>', {
	    	width: <?php echo is_numeric($width) ? $width : "'auto'"; ?>,
	                height: <?php echo $height; ?>,
			responsive: <?php echo $responsive; ?>,
			swipe:		<?php echo $swipe; ?>,
			transition: "<?php echo $transition; ?>",
			transitionSpeed: <?php echo $transitionspeed; ?>,
			autoplay: <?php echo $autoplay; ?>,
	    	thumbCrop: <?php echo $thumbcrop; ?>,
			imageCrop: <?php echo $imagecrop; ?>,
			carousel: <?php echo $carousel; ?>,
			imagePan: <?php echo $imagepan; ?>,
			clicknext: <?php echo $clicknext; ?>,
			thumbnails: "<?php echo $thumbnails; ?>",
			showInfo: <?php echo $showinfo; ?>,
			showCounter: <?php echo $showcounter; ?>,
			popupLinks: <?php echo $use_links ? "true" : "false"; ?>,
		    dataSelector: 'img.slider_content'
			<?php if ($show_content) : ?>
			, dataConfig: function(img) {
		        return {
		            layer: jQuery(img).next('div.layer').html()
		        }
		    }
			<?php endif;?>
	    });
	});
}());
//]]></script>
			<?php 
			
			$html = ob_get_contents();
			ob_end_clean();
			
			// Add the index to be able to display multiple galleries on one page
			$this->index++;
			
			return $html;
		}
		
		/**
		 * Searches images from post content
		 * 
		 * @param $post_id
		 * @param $width
		 * @param $height
		 * @param $img_script
		 */
		public function catch_that_image($post_id=0, $width=60, $height=60, $img_script='') {
			global $wpdb;
		
			if($post_id <= 0)
				return;
		
			// select the post content from the db
			$sql = 'SELECT post_content FROM ' . $wpdb->posts . ' WHERE id = ' . $wpdb->escape($post_id);
			$row = $wpdb->get_row($sql);
			$the_content = $row->post_content;
		
			if (strlen($the_content)) {
				// use regex to find the src of the image
				preg_match("/<img src\=('|\")(.*)('|\") .*( |)\/>/", $the_content, $matches);
				if(!$matches) {
					preg_match("/<img class\=\".*\" src\=('|\")(.*)('|\") .*( |)\/>/U", $the_content, $matches);
				}
		      	if(!$matches) {
					preg_match("/<img class\=\".*\" title\=\".*\" src\=('|\")(.*)('|\") .*( |)\/>/U", $the_content, $matches);
				}
				
				$the_image = '';
				$the_image_src = $matches[2];
				$frags = preg_split("/(\"|')/", $the_image_src);
				
				if(count($frags)) {
					$the_image_src = $frags[0];
				}
		
				// if an image isn't found yet
				if(!strlen($the_image_src)) {
					$attachments = get_children( array( 'post_parent' => $post_id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID' ) );
		          
					if (count($attachments) > 0) {
			            $q = 0;
			          	foreach ( $attachments as $id => $attachment ) {
			          		$q++;
			          		if ($q == 1) {
			          			$thumbURL = wp_get_attachment_image_src( $id, $args['size'] );
			          			$the_image_src = $thumbURL[0];
			          			break;
			          		} // if first image
			          	} // foreach
					} // if there are attachments
				} // if no image found yet
				
				// if src found, then create a new img tag
				if(strlen($the_image_src)) {
					if(strlen($img_script)) {
				    // if the src starts with http/https, then strip out server name
		
				    	if(preg_match("/^(http(|s):\/\/)/", $the_image_src)) {
							$the_image_src = preg_replace("/^(http(|s):\/\/)/", '', $the_image_src);
							$frags = split("\/", $the_image_src);
					     	array_shift($frags);
					     	$the_image_src = '/' . join("/", $frags);
				    	}
				    	$the_image = '<img alt="" src="' . $img_script . $the_image_src . '" />';
					} else {
						$the_image = '<img alt="" src="' . $the_image_src . '" width="' . $width . '" height="' . $height . '" />';
					}
				}
				
				return $the_image_src;
			}
		}
		
		/**
		 * If there is a category Karuselli. Filter it out from the front page posts
		 * (when displaying latest posts as frontpage) and from feeds. Also exlude from
		 * search.
		 * 
		 * @param object $query
		 * 
		 */
		public function filter_out_the_category($query) {
			
			
			if (($query->is_main_query() && is_home()) || $query->is_feed() || $query->is_search()) {
				$cat = get_category_by_slug("karuselli");
				if ($cat->term_id)
					$query->set('category__not_in',$cat->term_id);
			}
		}
		
		/**
		 * Checks GET parameters if a galleria content is requested
		 * to otakantaa.fi (remote host).
		 * We could return a JSON data array, but instead return bunch of
		 * HTML for ease of use (just call the shortcode).
		 * 
		 */
		public function data_for_remote() {
			
			// Do some sanitizing just in case. All inputs can be validated as string for
			// now since this is not for public script.
			$atts = array();
			foreach ($_GET as $key => $param) {
				$atts[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING);
			}
			
			$json = array('html' => $this->show_galleria_shortcode($atts));

			// Return JSONP https://gist.github.com/cowboy/1200708
			$callback = isset($_GET['callback']) ? preg_replace('/[^a-z0-9$_]/si', '', $_GET['callback']) : false;
			header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
			
			echo ($callback ? $callback . '(' : '') . json_encode($json) . ($callback ? ')' : '');
			
			exit();
		}
		
		/**
		 * Add beta.otakantaa.fi and otakantaa.fi to 
		 * allowed origins to allow remote AJAX requests.
		 * 
		 * @param array $allowed_origins
		 * 
		 * @return array $allowed_origins
		 */
		public function filter_allowed_origins($allowed_origins) {
			
			$allowed_origins[] = 'http://beta.otakantaa.fi';
			$allowed_origins[] = 'http://otakantaa.fi';
			$allowed_origins[] = 'http://www.otakantaa.fi';
			$allowed_origins[] = 'http://blogi.otakantaa.fi';
			
			return $allowed_origins;
		}
	}
	
}
$vmg = new Verkkomuikku_Galleria_Plugin();