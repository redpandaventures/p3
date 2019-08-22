<?php
/**
 * Tag Archive Template.
 *
 * @package P3
 */
?>
<?php get_header(); ?>
<?php $tag_obj = $wp_query->get_queried_object(); ?>

<div class="sleeve_main">

	<div id="main">
		<h2><?php printf( __( 'Tagged: %s', 'p3' ), single_tag_title( '', false) ); ?>
			<span class="controls">
				<a href="#" id="togglecomments"> <?php _e( 'Toggle Comment Threads', 'p3' ); ?></a> | <a href="#directions" id="directions-keyboard"><?php _e( 'Keyboard Shortcuts', 'p3' ); ?></a>
			</span>
		</h2>

		<?php if ( have_posts() ) : ?>

			<ul id="postlist">
			<?php while ( have_posts() ) : the_post(); ?>

				<?php p3_load_entry(); ?>

			<?php endwhile; ?>
			</ul>

		<?php else : ?>

			<div class="no-posts">
			    <h3><?php _e( 'No posts found!', 'p3' ); ?></h3>
			</div>

		<?php endif ?>

		<div class="navigation">
			<p class="nav-older"><?php next_posts_link( __( '&larr; Older posts', 'p3' ) ); ?></p>
			<p class="nav-newer"><?php previous_posts_link( __( 'Newer posts &rarr;', 'p3' ) ); ?></p>
		</div>

	</div> <!-- main -->

</div> <!-- sleeve -->

<?php get_footer(); ?>
