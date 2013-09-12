<?php
/**
 * AJAX wrapper for registration and reset password. 
 * Login doesn't need ajax or does it?
 * 
 */

// Ajaxify the registration form
// add_filter('vmfeu_registration_action_url', 'vmfeu_ajax_url_filter');
add_action('wp_ajax_vmfeu_add_user', 'vmfeu_ajax_register_user');
add_action('wp_ajax_nopriv_vmfeu_add_user', 'vmfeu_ajax_register_user');


/**
 * Change form action urls to ajax url
 *  
 * @param string $url
 * 
 * @return string $url WordPress ajaxurl
 */
function vmfeu_ajax_url_filter($url) {
	if (VMFEU_AJAX)
		return admin_url('admin-ajax.php');
	
	return $url;
}

/**
 * Call registration function and return the 
 * form as json response. 
 * 
 */
function vmfeu_ajax_register_user() {
	
	if (!VMFEU_AJAX)
		return;
	    		
	// Just call the registration procedure
	vmfeu_register_user();

	// and the template function.
	echo vmfeu_front_end_register(array(),"");
	
	exit();
}
?>