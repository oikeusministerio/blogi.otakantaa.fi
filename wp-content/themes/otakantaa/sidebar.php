<?php
/**
 * The Sidebar containing the main widget area.
 *
 * Twenty eleven doesn't show sidebar if we are in a content page. OSY, instead, want's
 * sidebar to be shown always.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven - Osallistumisympäristö
 * @since Twenty Eleven 1.0
 */
?>
<div id="secondary" class="widget-area" role="complementary">
	<?php  
	// Osallistumisympäristö
	// When in single post, put single post sidebar
	$singular_sidebar = false;
	if (is_singular() && ! is_home() && ! is_page())
		$singular_sidebar = dynamic_sidebar( 'single-post-sidebar' );

	// Leave single post sidebar empty and the main sidebar will show 
	if (!$singular_sidebar)
		dynamic_sidebar( 'sidebar-1' );  
	?>
</div>
