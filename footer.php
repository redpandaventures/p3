<?php
/**
 * Footer template.
 *
 * @package P3
 */
?>
	<div class="clear"></div>

</div> <!-- // wrapper -->

<div id="footer">
	<p>
		<?php echo prologue_poweredby_link(); ?>
		<?php
				printf(
					__( 'Theme: %1$s by %2$s.', 'p3' ),
					'<a href="https://wordpress.com/themes/p3">P3</a>',
					'<a href="https://wordpress.com/themes/" rel="designer">WordPress.com</a>'
				);
			?>
	</p>
</div>

<div id="notify"></div>

<div id="help">
	<dl class="directions">
		<dt>c</dt><dd><?php _e( 'Compose new post', 'p3' ); ?></dd>
		<dt>j</dt><dd><?php _e( 'Next post/Next comment', 'p3' ); ?></dd>
		<dt>k</dt> <dd><?php _e( 'Previous post/Previous comment', 'p3' ); ?></dd>
		<dt>r</dt> <dd><?php _e( 'Reply', 'p3' ); ?></dd>
		<dt>e</dt> <dd><?php _e( 'Edit', 'p3' ); ?></dd>
		<dt>o</dt> <dd><?php _e( 'Show/Hide comments', 'p3' ); ?></dd>
		<dt>t</dt> <dd><?php _e( 'Go to top', 'p3' ); ?></dd>
		<dt>l</dt> <dd><?php _e( 'Go to login', 'p3' ); ?></dd>
		<dt>h</dt> <dd><?php _e( 'Show/Hide help', 'p3' ); ?></dd>
		<dt><?php _e( 'shift + esc', 'p3' ); ?></dt> <dd><?php _e( 'Cancel', 'p3' ); ?></dd>
	</dl>
</div>

<?php wp_footer(); ?>

</body>
</html>
