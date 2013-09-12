<?php
/**
 * Header for iframe widget use. Used with the Iframe Widget page template
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
<title><?php wp_title( '' ); // wordpress seo plugin handles this ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="http://blogi.otakantaa.fi/wp-content/themes/otakantaa/style.css" />

<link rel='stylesheet' id='galleria_theme_style-css'  href='http://blogi.otakantaa.fi/wp-content/plugins/verkkomuikku-galleria-plugin/galleria/themes/content_slider/galleria.content_slider.css?ver=3.6' type='text/css' media='all' />
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<script type='text/javascript' src='http://blogi.otakantaa.fi/wp-includes/js/jquery/jquery.js?ver=1.10.2'></script>
<script type='text/javascript' src='http://blogi.otakantaa.fi/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.2.1'></script>
<script type='text/javascript' src='http://blogi.otakantaa.fi/wp-content/plugins/verkkomuikku-galleria-plugin/galleria/galleria-1.2.9.js?ver=1.2.8'></script>
<script type='text/javascript' src='http://blogi.otakantaa.fi/wp-content/plugins/verkkomuikku-galleria-plugin/galleria/themes/content_slider/galleria.content_slider.js?ver=1.2.9'></script>
<meta http-equiv="Content-Language" content="fi" />


<!--[if IE 7]>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo get_stylesheet_directory_uri(); ?>/ie7.css" />
<![endif]-->
</head>
<?php flush(); ?>
<body <?php body_class(); ?> style="background-color: white">