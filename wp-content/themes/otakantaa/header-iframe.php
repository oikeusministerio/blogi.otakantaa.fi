<?php
/**
 * Header for iframe. Used with the Full iframe page template
 *
 * Displays all of the <head> section and everything up till the <div id="full_iframe"> which
 * in turn contains only page content.
 *
 * @package WordPress
 * @subpackage Otakantaa theme

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
<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico" />
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<?php
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
</head>
<?php flush(); ?>
<body <?php body_class(); ?>>