<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Sidebar widget that displays login menu 
 * @author teemu
 *
 */
class Vmfeu_Sidebar_Login extends WP_Widget {
    /** constructor */
    function vmfeu_sidebar_login() {
        parent::WP_Widget(false, $name = 'Sidebar login');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
    	global $vmfeu;
    			
        extract( $args );
        
        $title = apply_filters('widget_title', $instance['title']);
		$amount = $instance['amount'];

		// Don't show on login page
		if ("login" == $vmfeu->is_vmfeu_page())
			return;
		
	    echo $before_widget;
	    if ($title)
			echo $before_title.$title.$after_title;

			echo do_shortcode('[vmfeu-login]');
		
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title 	= isset($instance['title']) ? $instance['title'] : "";
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'profilebuilder'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }

}
?>
