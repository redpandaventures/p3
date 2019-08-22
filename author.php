<?php
/**
 * Author template.
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

		<?php if ( have_posts() ) : ?>

		<h2>
			<?php printf( _x( 'Updates from %s', 'Author name', 'p3' ), p3_get_archive_author() ); ?>

			<span class="controls">
				<a href="#" id="togglecomments"> <?php _e( 'Toggle Comment Threads', 'p3' ); ?></a> | <a href="#directions" id="directions-keyboard"><?php _e( 'Keyboard Shortcuts', 'p3' ); ?></a>
			</span>
		</h2>

		<ul id="postlist">
			<?php while ( have_posts() ) : the_post(); ?>
	    		<?php p3_load_entry(); ?>
			<?php endwhile; ?>
		</ul>

		<?php else : ?>

		<h2><?php _e( 'Not Found', 'p3' ); ?></h2>
		<p><?php _e( 'Apologies, looks like this author does not have any posts.', 'p3' ); ?></p>

		<?php endif; // end have_posts() ?>

		<div class="navigation">
			<p class="nav-older"><?php next_posts_link( __( '&larr; Older posts', 'p3' ) ); ?></p>
			<p class="nav-newer"><?php previous_posts_link( __( 'Newer posts &rarr;', 'p3' ) ); ?></p>
		</div>

	</div> <!-- main -->

</div> <!-- sleeve -->

<?php get_footer(); ?>
