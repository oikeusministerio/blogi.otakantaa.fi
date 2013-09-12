<?php
/**
 *Template Name: Full iframe
 *Description: A Page Template that shows only menu (named full iframe menu) on top, has full width. Used with the Wiki
 */
get_header("iframe"); ?>

		<div id="full_iframe">
				<?php while ( have_posts() ) : the_post(); ?>

					<?php echo get_the_content(); ?>

				<?php endwhile; // end of the loop. ?>

		</div><!-- #full_iframe -->

<?php get_footer("iframe"); ?>