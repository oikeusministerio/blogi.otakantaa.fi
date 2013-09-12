<?php
/**
 * The template for displaying content in the carousel (Verkkomuikku-galleria-plugin)
 *
 * @package WordPress
 * @subpackage Twenty_Eleven - Osallistumisympäristö
 * @since Twenty Eleven 1.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-content">
		<?php the_content(); ?>
	</div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
