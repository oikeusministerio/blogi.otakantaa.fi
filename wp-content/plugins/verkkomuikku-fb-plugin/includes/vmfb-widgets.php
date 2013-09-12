<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Sidebar widget that displays Facebook Wall
 *  
 * @author verkkomuikku
 *
 */
class Vmfb_Wall_Widget extends WP_Widget {

    function Vmfb_wall_widget() {
        parent::WP_Widget(false, $name = 'Facebook Wall Widget');
    }

    function widget($args, $instance) {
    	
    	// No wall extension for some reason...
    	if (!function_exists('vmfb_show_wall'))
    		return;
    		
        extract( $args );
        
        $title = apply_filters('widget_title', $instance['title']);
		$amount = $instance['amount'];


	    echo $before_widget;
	    if ($title)
			echo $before_title.$title.$after_title;

			vmfb_show_wall();
		
		echo $after_widget;
    }

    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		
        return $instance;
    }

    function form($instance) {				
        $title 	= isset($instance['title']) ? $instance['title'] : "";
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'verkkomuikku'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }
}

/**
 * Sidebar widget that displays Facebook Like button (for the site)
 * @author verkkomuikku
 *
 */
class Vmfb_Facebook_Like_Widget extends WP_Widget {

    function Vmfb_Facebook_Like_Widget() {
        parent::WP_Widget(false, $name = 'Facebook Like Widget');
    }

    function widget($args, $instance) {
    	
    	// No wall extension for some reason...
    	if (!function_exists('vmfb_show_site_like'))
    		return;
    		
        extract( $args );
        
        $title = apply_filters('widget_title', $instance['title']);
        $like_url = apply_filters('vmfb_facebook_like_widget_url', $instance['like_url']);

	    echo $before_widget;
	    if ($title)
			echo $before_title.$title.$after_title;

		echo vmfb_show_site_like($like_url);
		
		echo $after_widget;
    }

    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['like_url'] = $new_instance['like_url'];
		
        return $instance;
    }

    function form($instance) {				
        $title 	= isset($instance['title']) ? $instance['title'] : "";
        $like_url = isset($instance['like_url']) ? $instance['like_url'] : "";
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'verkkomuikku'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('like_url'); ?>"><?php _e('Like url:', 'verkkomuikku'); ?> <input class="widefat" id="<?php echo $this->get_field_id('like_url'); ?>" name="<?php echo $this->get_field_name('like_url'); ?>" type="text" value="<?php echo $like_url; ?>" /></label>
           	<?php _e("By default the Like widget will link to this site url. If you want the like button to Like your Facebook page or group or what ever instead provide its url here."); ?>
            </p>
        <?php 
    }
}
?>