<?php

/**
 * Twenty eleven adds body class "singular" if there is no sidebar (single pages / posts etc).
 * Osallistymisymparisto wants sidebar to be always present..
 * 
 * @param array $classes
 */
function osallistumisymparisto_body_classes( $classes ) {
	
	if ( ! is_multi_author() ) {
		$classes[] = 'single-author';
	}
	
	return $classes;
}
add_filter( 'body_class', 'osallistumisymparisto_body_classes', 1, 10);

// Remove Twenty eleven body_class filter
add_action( 'init', 'remove_te_body_class_filter');
function remove_te_body_class_filter() {
	remove_filter( 'body_class', 'twentyeleven_body_classes' );
}

/**
 * Hide empty pages / categorylists from menu
 * 
 * @param object $menu_item The menu item
 * @return object The (possibly) modified menu item
 */
function osy_filter_out_empty_nav_menu_items( $sorted_menu_items, $args ) {
	global $wpdb, $q_config;
	
	// No need to proceed
	if (is_admin() || !function_exists('qtrans_getLanguage') || $q_config['default_language'] == qtrans_getLanguage())
		return $sorted_menu_items;
	
	// Find page ids
	$check_these = array();
	foreach ($sorted_menu_items as $key => $menu_item) {
		if ( 'page' == $menu_item->object ) {
			$check_these[$key] = $menu_item->object_id;
		}
	}

	// Check which of them don't have translation for the language
	if (!empty($check_these)) {
		$query = "SELECT ID FROM {$wpdb->posts} 
					WHERE post_status = 'publish' 
					AND post_type='page'
					AND ID IN (".implode(',', $check_these).")
					AND post_content like '%<!--:".qtrans_getLanguage()."-->%'";

		$ok = $wpdb->get_col($query);
		foreach ($check_these as $key => $id) {
			if (!in_array($id, $ok))
				unset($sorted_menu_items[$key]);
		}
	}
	
	return $sorted_menu_items;
}
add_filter('wp_nav_menu_objects', 'osy_filter_out_empty_nav_menu_items', 1, 2);

/**
 * Add sidebar for sigle posts
 * 
 */
add_action( 'widgets_init', 'osallistumisymparisto_register_sidebars' );
function osallistumisymparisto_register_sidebars() {
		
	register_sidebar( array(
		'name' => __( 'Sidebar for post entries', 'twentyeleven' ),
		'id' => 'single-post-sidebar',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	
	
	register_widget( 'Osy_Widget_Recent_Comments' );
}

/**
 * Copy of Recent_Comments widget class with edited author link
 *
 * 
 */
class Osy_Widget_Recent_Comments extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_osy_recent_comments', 'description' => __( 'The most recent comments with link to author posts' ) );
		parent::__construct('recent-comments', __('Osy - Recent Comments'), $widget_ops);
		$this->alt_option_name = 'widget_osy_recent_comments';

		add_action( 'comment_post', array(&$this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array(&$this, 'flush_widget_cache') );
	}



	function flush_widget_cache() {
		wp_cache_delete('widget_recent_comments', 'widget');
	}

	function widget( $args, $instance ) {
		global $comments, $comment;

		$cache = wp_cache_get('widget_recent_comments', 'widget');

		if ( ! is_array( $cache ) )
			$cache = array();

		if ( isset( $cache[$args['widget_id']] ) ) {
			echo $cache[$args['widget_id']];
			return;
		}

 		extract($args, EXTR_SKIP);
 		$output = '';
 		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Comments') : $instance['title']);

		if ( ! $number = absint( $instance['number'] ) )
 			$number = 5;

		$comments = get_comments( array( 'number' => $number, 'status' => 'approve', 'post_status' => 'publish' ) );
		$output .= $before_widget;
		if ( $title )
			$output .= $before_title . $title . $after_title;

		$output .= '<ul id="recentcomments">';
		if ( $comments ) {
			foreach ( (array) $comments as $comment) {
				// verkkomuikku / osy
				$comment_author_link = get_author_posts_url($comment->user_id);
				if ($comment_author_link)	
					$comment_author_link = '<a href="'.$comment_author_link.'">'.get_comment_author().'</a>';
				else
					$comment_author_link = get_comment_author_link();
				$output .=  '<li class="recentcomments"><span class="comment_author_link">' . $comment_author_link. ':</span> <a href="' . esc_url( get_comment_link($comment->comment_ID) ) . '">' . get_the_title($comment->comment_post_ID) . '</a>' . '</li>';
			}
 		}
		$output .= '</ul>';
		$output .= $after_widget;

		echo $output;
		$cache[$args['widget_id']] = $output;
		wp_cache_set('widget_recent_comments', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = absint( $new_instance['number'] );
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_recent_comments']) )
			delete_option('widget_recent_comments');

		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of comments to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}

/**
 * Don't show recent comments widget other than in Finnish
 * 
 * @param array $sidebars_widgets
 * @return array $sidebars_widgets
 */
function osy_filter_widgets($sidebars_widgets) {
	
	if (!function_exists('qtrans_getLanguage') || is_admin())
		return $sidebars_widgets;

	if (qtrans_getLanguage() != "fi") {
		foreach ($sidebars_widgets['sidebar-1'] as $key => $val) {
			if (preg_match('#^recent-comments#', $val)) {
				unset($sidebars_widgets['sidebar-1'][$key]);
			}
		}
	}
		
	return $sidebars_widgets;
}
add_filter('sidebars_widgets', 'osy_filter_widgets');
