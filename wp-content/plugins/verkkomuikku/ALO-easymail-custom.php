<?php
/**
 * Custom stuff for ALO EasyMail Newsletter plugin
 * Adds placeholder for latest posts
 * From ALO easymail custom hooks example
 * 
 */
/*******************************************************************************
 * 
 * EXAMPLE 
 *
 * The following set of functions adds a new placeholder that includes the latest 
 * published posts inside newsletter
 *
 * @since: 2.0
 *
 ******************************************************************************/


/**
 * Add placeholder to table in new/edit newsletter screen
 *
 */
function custom_easymail_placeholders ( $placeholders ) {
	$placeholders["custom_latest"] = array (
		"title" 		=> __("Latest posts", "alo-easymail"),
		"tags" 			=> array (
			"[LATEST-POSTS]"		=> __("A list with the latest published posts", "alo-easymail")
		)
	);
	return $placeholders;
}
add_filter ( 'alo_easymail_newsletter_placeholders_table', 'custom_easymail_placeholders' );


/**
 * Add select in placeholders table
 * 
 * Note that the hook name is based upon the name of placeholder given in previous function as index:
 * alo_easymail_newsletter_placeholders_title_{your_placeholder}
 * If placeholder is 'my_archive' the hook will be:
 * alo_easymail_newsletter_placeholders_title_my_archive
 *
 */
function custom_easymail_placeholders_title_custom_latest ( $post_id ) {
	echo __("Select how many posts", "alo-easymail"). ": ";	
	echo '<select name="placeholder_custom_latest" id="placeholder_custom_latest" >';
	for ( $i = 3; $i <= 10; $i++ ) {
	    $select_custom_latest = ( get_post_meta ( $post_id, '_placeholder_custom_latest', true) == $i ) ? 'selected="selected"': '';
	    echo '<option value="'.$i.'" '. $select_custom_latest .'>'. $i. '</option>';
	}
	echo '</select>'; 
}
add_action('alo_easymail_newsletter_placeholders_title_custom_latest', 'custom_easymail_placeholders_title_custom_latest' );


/**
 * Save latest post number when the newsletter is saved
 */
function custom_save_placeholder_custom_latest ( $post_id ) {
	if ( isset( $_POST['placeholder_custom_latest'] ) && is_numeric( $_POST['placeholder_custom_latest'] ) ) {
		update_post_meta ( $post_id, '_placeholder_custom_latest', $_POST['placeholder_custom_latest'] );
	}
}
add_action('alo_easymail_save_newsletter_meta_extra', 'custom_save_placeholder_custom_latest' );


/**
 * Replace the placeholder when the newsletter is sending 
 * @param	str		the newsletter text
 * @param	obj		newsletter object, with all post values
 * @param	obj		recipient object, with following properties: ID (int), newsletter (int: recipient ID), email (str), result (int: 1 if successfully sent or 0 if not), lang (str: 2 chars), unikey (str), name (str: subscriber name), user_id (int/false: user ID if registered user exists), subscriber (int: subscriber ID), firstname (str: firstname if registered user exists, otherwise subscriber name)
 * @param	bol    	if apply "the_content" filters: useful to avoid recursive and infinite loop
 */ 
function custom_easymail_placeholders_get_latest ( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
	if ( !is_object( $recipient ) ) $recipient = new stdClass();
	if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );
	$limit = get_post_meta ( $newsletter->ID, '_placeholder_custom_latest', true );
	$latest = "";
	if ( $limit ) {
		$args = array( 'numberposts' => $limit, 'order' => 'DESC', 'orderby' => 'date' );
		$myposts = get_posts( $args );
		if ( $myposts ) :
			$latest .= "<ul>\r\n";
			foreach( $myposts as $post ) :	// setup_postdata( $post );
				$post_title = stripslashes ( alo_em_translate_text ( $recipient->lang, $post->post_title ) );
	   			$latest .= "<li><a href='". esc_url ( alo_em_translate_url( $post->ID , $recipient->lang ) ). "'>". $post_title ."</a></li>\r\n";
			endforeach; 
			$latest .= "</ul>\r\n";
		endif;	     
	} 
	$content = str_replace("[LATEST-POSTS]", $latest, $content);
   
	return $content;	
}
add_filter ( 'alo_easymail_newsletter_content',  'custom_easymail_placeholders_get_latest', 10, 4 );


/**********************************************************
 * Clone a newsletter functionality
 * - adds clone link to newsletters list
 * - clones all properties including receivers
 * 
 * 
 **********************************************************/

/**
 * Duplicate sent newsletter as new.
 * Add custom column to the custom posts view
 * 
 */
function custom_easymail_edit_table_columns ( $columns ) {
   	$columns["easymail_clone_newsletter"] = __( 'Utility', 'verkkomuikku' );   	   	   	   	   	
  	return $columns;
}
add_filter ('manage_edit-newsletter_columns', 'custom_easymail_edit_table_columns', 1, 10);

/**
 * Duplicate sent newsletter as new.
 * Fill in custom column 
 * 
 */
function custom_easymail_table_column_value( $columns ) {
	global $post;
	
	if ($columns == "easymail_clone_newsletter" && alo_em_user_can_edit_newsletter( $post->ID )) {
		$url = get_admin_url().'edit.php?post_type=newsletter';
		$url = add_query_arg(array('clone' => '1', 'id' => $post->ID), $url);
		echo '<a href="'.$url.'" title="'.__("Create new newsletter this newsletter as template.", "verkkomuikku").'">'.__("Clone", "verkkomuikku").'</a>';
	}
}
add_action ('manage_posts_custom_column', 'custom_easymail_table_column_value', 1, 10 );

/**
 * Catch clone query from url
 * 
 * Could check that we are on a correct page in dashboard to 
 * provide security, see how to query newsletters function alo_em_filter_newsletter_table
 * 
 * @param int $id optional id of the newsletter to clone
 * 
 */
function custom_easymail_clone_newsletter($id = 0) {
	global $wpdb;
	
	// ALO easymail not activated
	if (!function_exists('alo_em_user_can_edit_newsletter'))
		return; 
		
	// Check if the function was called from source code
	if ($id) {
		$clone_from_id = $id;

	// Check if user chose to clone from newsletters admin page 
	} elseif ($_GET["clone"] == 1 && $_GET["id"] && alo_em_user_can_edit_newsletter( intval($_GET["id"]) )) {
		$clone_from_id = intval($_GET["id"]);

	// No cloning
	} else {
		return;
	}
	
	// Get the original newsletter
	$clone_from = $wpdb->get_row("SELECT * FROM {$wpdb->posts} 
								WHERE ID = {$clone_from_id} 
								AND post_type = 'newsletter' 
								LIMIT 1");
	
	// No Newsletter...
	if (!$clone_from->ID)
		return;
		
	// Arguments for the new newsletter
	$args = array(
		"post_type" 	=> "newsletter",
		"post_status"	=> "publish",
		"post_title" 	=> $clone_from->post_title,
		"post_content" 	=> $clone_from->post_content,
	);
	$new_newsletter = wp_insert_post($args);
	
	if (!$new_newsletter)
		return false;

	// We need to clone the post meta as well since the 
	// function alo_em_save_newsletter_meta that normally saves 
	// post meta will fail to nonce check
	$clone_from_meta = get_post_custom($clone_from_id);
	
	// These meta keys are related to newsletters, found them by searching alo-easymail plugin
	$newsletter_postmeta = apply_filters('custom_easymail_clone_post_meta_keys', array(
		// "_easymail_completed" not relevant here
		// "_easymail_status" not relevant here
		"_easymail_recipients",
		"_easymail_theme",
		"_placeholder_custom_latest",
		"_placeholder_easymail_post",
		"_placeholder_post_imgsize",
		"_placeholder_newsletter_imgsize",
	));
		
	foreach ($newsletter_postmeta as $key) {
		if (isset($clone_from_meta[$key]))
			add_post_meta ( $new_newsletter, $key, $clone_from_meta[$key] );
	}
	
	// Ok, lets redirect to the editor
	$url = html_entity_decode(get_edit_post_link($new_newsletter));
	wp_redirect($url);
	exit();
}
add_action('admin_init', 'custom_easymail_clone_newsletter');


?>