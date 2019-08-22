<?php
/**
 * Theme options page.
 *
 * @package P3
 * @since unknown
 */

add_action( 'admin_menu', array( 'P3_Options', 'init' ) );

class P3_Options {

	static function init() {
		global $plugin_page;

		add_theme_page( __( 'Theme Options', 'p3' ), __( 'Theme Options', 'p3' ), 'edit_theme_options', 'p3-options-page', array( 'P3_Options', 'page' ) );

		if ( 'p3-options-page' == $plugin_page ) {
			wp_enqueue_script( 'farbtastic' );
			wp_enqueue_style( 'farbtastic' );
			wp_enqueue_script( 'colorpicker' );
			wp_enqueue_style( 'colorpicker' );
		}
	}

	static function page() {
		register_setting( 'p3ops', 'prologue_show_titles' );
		register_setting( 'p3ops', 'p3_allow_users_publish' );
		register_setting( 'p3ops', 'p3_prompt_text' );
		register_setting( 'p3ops', 'p3_hide_sidebar' );
		register_setting( 'p3ops', 'p3_background_color' );
		register_setting( 'p3ops', 'p3_background_image' );
		register_setting( 'p3ops', 'p3_hide_threads' );

		$prologue_show_titles_val    = get_option( 'prologue_show_titles' );
		$p3_allow_users_publish_val  = get_option( 'p3_allow_users_publish' );
		$p3_prompt_text_val          = get_option( 'p3_prompt_text' );
		$p3_hide_sidebar             = get_option( 'p3_hide_sidebar' );
		$p3_background_color         = get_option( 'p3_background_color' );
		$p3_background_image         = get_option( 'p3_background_image' );
		$p3_hide_threads             = get_option( 'p3_hide_threads' );

		if ( isset( $_POST[ 'action' ] ) && esc_attr( $_POST[ 'action' ] ) == 'update' ) {
			check_admin_referer( 'p3ops-options' );

			if ( isset( $_POST[ 'prologue_show_titles' ] ) )
				$prologue_show_titles_val = intval( $_POST[ 'prologue_show_titles' ] );
			else
				$prologue_show_titles_val = 0;

			if ( isset( $_POST[ 'p3_allow_users_publish' ] ) )
				$p3_allow_users_publish_val = intval( $_POST[ 'p3_allow_users_publish' ] );
			else
				$p3_allow_users_publish_val = 0;

			if ( isset( $_POST[ 'p3_background_color_hex' ] ) ) {
				// color value must be 3 or 6 hexadecimal characters
				if ( preg_match( '/^#?([a-f0-9]{3}){1,2}$/i', $_POST['p3_background_color_hex'] ) ) {
					$p3_background_color = $_POST['p3_background_color_hex'];
					// if color value doesn't have a preceding hash, add it
					if ( false === strpos( $p3_background_color, '#' ) )
						$p3_background_color = '#' . $p3_background_color;
				} else {
					$p3_background_color = '';
				}
			}

			if ( esc_attr( $_POST[ 'p3_prompt_text' ] ) != __( "Whatcha' up to?", 'p3') )
				$p3_prompt_text_val = esc_attr( $_POST[ 'p3_prompt_text' ] );

			if ( isset( $_POST[ 'p3_hide_sidebar' ] ) )
				$p3_hide_sidebar = intval( $_POST[ 'p3_hide_sidebar' ] );
			else
				$p3_hide_sidebar = false;

			if ( isset( $_POST[ 'p3_hide_threads' ] ) )
				$p3_hide_threads = $_POST[ 'p3_hide_threads' ];
			else
				$p3_hide_threads = false;

			if ( isset( $_POST['p3_background_image'] ) )
				$p3_background_image = $_POST[ 'p3_background_image' ];
			else
				$p3_background_image = 'none';

			update_option( 'prologue_show_titles', $prologue_show_titles_val );
			update_option( 'p3_allow_users_publish', $p3_allow_users_publish_val );
			update_option( 'p3_prompt_text', $p3_prompt_text_val );
			update_option( 'p3_hide_sidebar', $p3_hide_sidebar );
			update_option( 'p3_background_color', $p3_background_color );
			update_option( 'p3_background_image', $p3_background_image );
			update_option( 'p3_hide_threads', $p3_hide_threads );

		?>
			<div class="updated"><p><strong><?php _e( 'Options saved.', 'p3' ); ?></strong></p></div>
		<?php

			} ?>

		<div class="wrap">
	    <?php echo "<h2>" . __( 'P3 Options', 'p3' ) . "</h2>"; ?>

		<form enctype="multipart/form-data" name="form1" method="post" action="<?php echo esc_attr( str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) ); ?>">

			<h3 style="font-family: georgia, times, serif; font-weight: normal; border-bottom: 1px solid #ddd; padding-bottom: 5px">
				<?php _e( 'Functionality Options', 'p3' ); ?>
			</h3>

			<?php settings_fields( 'p3ops' ); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e( 'Posting Access:', 'p3' ); ?></th>
						<td>

						<input id="p3_allow_users_publish" type="checkbox" name="p3_allow_users_publish" <?php if ( $p3_allow_users_publish_val == 1 ) echo 'checked="checked"'; ?> value="1" />

						<?php if ( defined( 'IS_WPCOM' ) && IS_WPCOM )
								$msg = __( 'Allow any WordPress.com member to post', 'p3' );
							  else
							  	$msg = __( 'Allow any registered member to post', 'p3' );
						 ?>

						<label for="p3_allow_users_publish"><?php echo $msg; ?></label>

						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Hide Threads:', 'p3' ); ?></th>
						<td>

						<input id="p3_hide_threads" type="checkbox" name="p3_hide_threads" <?php if ( $p3_hide_threads == 1 ) echo 'checked="checked"'; ?> value="1" />
						<label for="p3_hide_threads"><?php _e( 'Hide comment threads by default on all non-single views', 'p3' ); ?></label>

						</td>
					</tr>
				</tbody>
			</table>

			<p>&nbsp;</p>

			<h3 style="font-family: georgia, times, serif; font-weight: normal; border-bottom: 1px solid #ddd; padding-bottom: 5px">
				<?php _e( 'Design Options', 'p3' ); ?>
			</h3>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e( 'Custom Background Color:', 'p3' ); ?></th>
						<td>
							<input id="pickcolor" type="button" class="button" name="pickcolor" value="<?php esc_attr_e( 'Pick a Color', 'p3' ); ?> "/>
							<input name="p3_background_color_hex" id="p3_background_color_hex" type="text" value="<?php echo esc_attr( $p3_background_color ); ?>" />
							<div id="colorPickerDiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"> </div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Background Image:', 'p3' ); ?></th>
						<td>
							<input type="radio" id="bi_none" name="p3_background_image" value="none"<?php if ( 'none' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_none"><?php _e( 'None', 'p3' ); ?></label><br />
							<input type="radio" id="bi_bubbles" name="p3_background_image" value="bubbles"<?php if ( 'bubbles' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_bubbles"><?php _e( 'Bubbles', 'p3' ); ?></label><br />
							<input type="radio" id="bi_polka" name="p3_background_image" value="dots"<?php if ( 'dots' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_polka"><?php _e( 'Polka Dots', 'p3' ); ?></label><br />
							<input type="radio" id="bi_squares" name="p3_background_image" value="squares"<?php if ( 'squares' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_squares"><?php _e( 'Squares', 'p3' ); ?></label><br />
							<input type="radio" id="bi_plaid" name="p3_background_image" value="plaid"<?php if ( 'plaid' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_plaid"><?php _e( 'Plaid', 'p3' ); ?></label><br />
							<input type="radio" id="bi_stripes" name="p3_background_image" value="stripes"<?php if ( 'stripes' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_stripes"><?php _e( 'Stripes', 'p3' ); ?></label><br />
							<input type="radio" id="bi_santa" name="p3_background_image" value="santa"<?php if ( 'santa' == $p3_background_image ) : ?> checked="checked" <?php endif; ?>/> <label for="bi_santa"><?php _e( 'Santa', 'p3' ); ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Sidebar display:', 'p3' ); ?></th>
						<td>
							<input id="p3_hide_sidebar" type="checkbox" name="p3_hide_sidebar" <?php if ( $p3_hide_sidebar ) echo 'checked="checked"'; ?> value="1" />
							<label for="p3_hide_sidebar"><?php _e( 'Hide the Sidebar', 'p3' ); ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Post prompt:', 'p3' ); ?></th>
						<td>
							<input id="p3_prompt_text" type="input" name="p3_prompt_text" value="<?php echo ($p3_prompt_text_val == __( "Whatcha' up to?", 'p3') ) ? __("Whatcha' up to?", 'p3') : stripslashes( $p3_prompt_text_val ); ?>" />
				 			(<?php _e( 'if empty, defaults to <strong>Whatcha up to?</strong>', 'p3' ); ?>)
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Post Titles:', 'p3' )?></th>
						<td>
							<input id="prologue_show_titles" type="checkbox" name="prologue_show_titles" <?php if ( $prologue_show_titles_val != 0 ) echo 'checked="checked"'; ?> value="1" />
							<label for="prologue_show_titles"><?php _e( 'Display titles', 'p3' ); ?></label>
						</td>
					</tr>
				</tbody>
			</table>

			<p>
			</p>

			<p class="submit">
				<input type="submit" name="Submit" value="<?php esc_attr_e( 'Update Options', 'p3' ); ?>" />
			</p>

		</form>
		</div>

<script type="text/javascript">
/* <![CDATA[ */
	var farbtastic;

	function pickColor(color) {
		jQuery('#p3_background_color_hex').val(color);
		farbtastic.setColor(color);
	}

	jQuery(document).ready(function() {
		jQuery('#pickcolor').click(function() {
			jQuery('#colorPickerDiv').show();
		});

		jQuery('#hidetext' ).click(function() {
			toggle_text();
		});

		farbtastic = jQuery.farbtastic( '#colorPickerDiv', function(color) { pickColor(color); });
	});

	jQuery(document).mousedown(function(){
		// Make the picker disappear, since we're using it in an independant div
		hide_picker();
	});

	function colorDefault() {
		pickColor( '#<?php echo HEADER_TEXTCOLOR; ?>' );
	}

	function hide_picker(what) {
		var update = false;
		jQuery('#colorPickerDiv').each(function(){
			var id = jQuery(this).attr( 'id' );
			if (id == what) {
				return;
			}
			var display = jQuery(this).css( 'display' );
			if (display == 'block' ) {
				jQuery(this).fadeOut(2);
			}
		});
	}

	function toggle_text(force) {
		if (jQuery('#textcolor').val() == 'blank' ) {
			//Show text
			jQuery(buttons.toString()).show();
			jQuery('#textcolor').val( '<?php echo HEADER_TEXTCOLOR; ?>' );
			jQuery('#hidetext').val( '<?php _e( 'Hide Text', 'p3' ); ?>' );
		}
		else {
			//Hide text
			jQuery(buttons.toString()).hide();
			jQuery('#textcolor').val( 'blank' );
			jQuery('#hidetext').val( '<?php _e( 'Show Text', 'p3' ); ?>' );
		}
	}
/* ]]> */
</script>

<?php
	}
}
