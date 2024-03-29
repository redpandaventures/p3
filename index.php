<?php
/**
 * Main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package P3
 */
?>
<?php get_header(); ?>

<div class="sleeve_main">
	<?php if ( p3_user_can_post() && !is_archive() ) : ?>
		<?php locate_template( array( 'post-form.php' ), true ); ?>
	<?php endif; ?>
	<div id="main">
		<h2>
			<?php if ( is_home() or is_front_page() ) : ?>

				<?php _e( 'Recent Updates', 'p3' ); ?> <?php if ( p3_get_page_number() > 1 ) printf( __( 'Page %s', 'p3' ), p3_get_page_number() ); ?>

			<?php else : ?>

				<?php printf( _x( 'Updates from %s', 'Month name', 'p3' ), get_the_time( 'F, Y' ) ); ?>

			<?php endif; ?>

			<span class="controls">
				<a href="#" id="togglecomments"> <?php _e( 'Toggle Comment Threads', 'p3' ); ?></a> | <a href="#directions" id="directions-keyboard"><?php _e( 'Keyboard Shortcuts', 'p3' ); ?></a>
			</span>
		</h2>

		<ul id="postlist">
		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post(); ?>
	    		<?php p3_load_entry(); ?>
			<?php endwhile; ?>

		<?php else : ?>

			<li class="no-posts">
		    	<h3><?php _e( 'No posts yet!', 'p3' ); ?></h3>
			</li>

		<?php endif; ?>
		</ul>

		<div class="navigation">
			<p class="nav-older"><?php next_posts_link( __( '&larr; Older posts', 'p3' ) ); ?></p>
			<p class="nav-newer"><?php previous_posts_link( __( 'Newer posts &rarr;', 'p3' ) ); ?></p>
		</div>

	</div> <!-- main -->

</div> <!-- sleeve -->

<?php get_footer(); ?>
