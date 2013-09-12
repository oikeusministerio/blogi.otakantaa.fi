<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Eleven - Osallistumisymparisto
 * @since Twenty Eleven 1.0
 */
// Verkkomuikku
ob_start();
language_attributes();
$lang_attr = ob_get_contents();
ob_end_clean(); 
?><!DOCTYPE html>
<!--[if IE 6]>
<html id="ie6" <?php echo $lang_attr ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php echo $lang_attr ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php echo $lang_attr ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php echo $lang_attr ?>>
<!--<![endif]-->
<head>
<meta charset="UTF-8<?php //bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<title><?php wp_title( '' ); // wordpress seo plugin handles this ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico" />
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<?php
	/* We add some JavaScript to pages with the comment form
	 * to support sites with threaded comments (when in use).
	 */
	if ( is_singular() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );

	/* Always have wp_head() just before the closing </head>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to add elements to <head> such
	 * as styles, scripts, and meta tags.
	 */
	wp_head();
?>
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo get_stylesheet_directory_uri(); ?>/ie7.css" />
<![endif]-->
<script type="text/javascript">
	var fbstyle = '<link href="<?php echo get_stylesheet_directory_uri(); ?>/facebook.css" rel="stylesheet" type="text/css">';
	if (window.parent.frames.length > 0) { 
		document.write(fbstyle); 
		jQuery(document).ready(function(){
			jQuery('#main a').each(function() {
				if (jQuery(this).attr('href').indexOf('osallistumisymparisto.fi') < 0 || jQuery(this).attr('href').indexOf('blogi.otakantaa.fi') < 0) {
					jQuery(this).attr('target', '_blank');
				}
			});
		});
	}
</script>
</head>
<?php flush(); ?>
<body <?php body_class(); ?>>
<?php do_action('vmfeu_wp_body'); ?>
<div id="page" class="hfeed">
		<?php 
		// Generate qtranslate link
		if (function_exists('qtrans_convertURL')) :
			global $q_config; 
			
			if(is_404()) 
				$url = get_option('home'); 
			else 
				$url = '';
			
			$current_language = qtrans_getLanguage();
			
			?>
			<div id="language-menu"  class="horizontal-menu">
				<ul id="menu-language-menu" class="menu">
					<?php foreach (qtrans_getSortedLanguages() as $language) :?>
					<li <?php echo $language == $current_language ? 'class="selected"' : '' ?>><a href="<?php echo qtrans_convertURL($url, $language)?>" ><?php echo $q_config['language_name'][$language]; ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php global $VerkkomuikkuPlugin; if ($VerkkomuikkuPlugin) { $VerkkomuikkuPlugin->fontresize_buttons(); } ?>				
			</div>
		<?php endif; ?>	

	<header id="branding" role="banner">
			<?php
				// Check to see if the header image has been removed
				$header_image = get_header_image();
				if ( ! empty( $header_image ) ) :
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php header_image(); ?>" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="" />
			</a>
			<?php else: // Static image / osallistumisymparisto ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo get_stylesheet_directory_uri().'/images/header.jpg' ?>" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="" />
			</a>
			<?php endif; // end check for removed header image ?>

			<nav id="access" role="navigation">
				<div id="nav-searchform">
					<?php get_search_form(); ?>
				</div>			
				<h3 class="assistive-text"><?php _e( 'Main menu', 'twentyeleven' ); ?></h3>
				<?php /*  Allow screen readers / text browsers to skip the navigation menu and get right to the good stuff. */ ?>
				<div class="skip-link"><a class="assistive-text" href="#content" title="<?php esc_attr_e( 'Skip to primary content', 'twentyeleven' ); ?>"><?php _e( 'Skip to primary content', 'twentyeleven' ); ?></a></div>
				<div class="skip-link"><a class="assistive-text" href="#secondary" title="<?php esc_attr_e( 'Skip to secondary content', 'twentyeleven' ); ?>"><?php _e( 'Skip to secondary content', 'twentyeleven' ); ?></a></div>
				<?php /* Our navigation menu.  If one isn't filled out, wp_nav_menu falls back to wp_page_menu. The menu assiged to the primary position is the one used. If none is assigned, the menu with the lowest ID is used. */ ?>
				<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
		
			</nav><!-- #access -->
	</header><!-- #branding -->


	<div id="main">