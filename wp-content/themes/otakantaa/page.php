<?php
/**
 * The template for displaying all pages.
 *
 *
 * Osallistumisympäristö: Sidebar added 
 * 
 * 
 * @package WordPress
 * @subpackage Twenty_Eleven - Osallistymisympäristö
 * @since Twenty Eleven 1.0
 */

get_header(); ?>

		<div id="primary">
			<div id="content" role="main">

				<?php the_post(); ?>

				<?php get_template_part( 'content', 'page' ); ?>

				<?php comments_template( '', true ); ?>

			</div>
		</div>
		
<?php get_sidebar();?>
<?php get_footer(); ?>