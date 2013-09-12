<?php
/**
 *Template Name: Iframe widget
 *Description: A Page Template that uses minimal resources, no page layout. Use if you want to provide content for iframe.
 */
get_header("iframe_widget"); ?>

<?php while ( have_posts() ) : the_post(); ?>

	<?php echo do_shortcode(get_the_content()); ?>

<?php endwhile; // end of the loop. ?>

<?php get_footer("iframe_widget"); ?>