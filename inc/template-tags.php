<?php
/**
 * Template tags.
 *
 * @package P3
 * @since unknown
 */

function p3_body_class( $classes ) {
	if ( is_tax( P3_MENTIONS_TAXONOMY ) )
		$classes[] = 'mentions';
	if ( p3_is_iphone() )
		$classes[] = 'iphone';
	return $classes;
}
add_filter( 'body_class', 'p3_body_class' );

function p3_user_can_post() {
	global $user_ID;

	if ( current_user_can( 'publish_posts' ) || ( get_option( 'p3_allow_users_publish' ) && $user_ID ) )
		return true;

	return false;
}

function p3_show_comment_form() {
	global $post, $form_visible;

	$show = ( !isset( $form_visible ) || !$form_visible ) && 'open' == $post->comment_status;

	if ( $show )
		$form_visible = true;

	return $show;
}

function p3_is_ajax_request() {
	global $post_request_ajax;

	return ( $post_request_ajax ) ? $post_request_ajax : false;
}

function p3_media_upload_form() {
	require( ABSPATH . '/wp-admin/includes/template.php' );
	media_upload_form();
?>
<?php
}

function p3_user_display_name() {
	echo p3_get_user_display_name();
}

function p3_get_user_display_name() {
	$current_user = wp_get_current_user();

	return apply_filters( 'p3_get_user_display_name', isset( $current_user->first_name ) && $current_user->first_name ? $current_user->first_name : $current_user->display_name );
}

function p3_discussion_links() {
	echo p3_get_discussion_links();
}

function p3_get_discussion_links() {
	$comments = get_comments( array( 'post_id' => get_the_ID() ) );

	$unique_commentors = array();
	foreach ( $comments as $comment ) {
		if ( '1' == $comment->comment_approved )
			$unique_commentors[$comment->comment_author_email] = get_avatar( $comment, 16 ) . ' ' . get_comment_author_link( $comment->comment_ID );
	}

	$unique_commentors = array_values( $unique_commentors );
	$total_unique_commentors = count( $unique_commentors );

	$content = '';

	if ( 1 == $total_unique_commentors ) {
		$content = sprintf( __( '%1$s is discussing.', 'p3' ), $unique_commentors[0] );
	} else if ( 2 == $total_unique_commentors ) {
		$content = sprintf( __( '%1$s and %2$s are discussing.', 'p3' ),
			$unique_commentors[0],
			$unique_commentors[1]
		);
	} else if ( 3 == $total_unique_commentors ) {
		$content = sprintf( __( '%1$s, %2$s, and %3$s are discussing.', 'p3' ),
			$unique_commentors[0],
			$unique_commentors[1],
			$unique_commentors[2]
		);
	} else if ( 3 < $total_unique_commentors ) {
		$others = $total_unique_commentors - 3;
		$content .= sprintf( _n( '%1$s, %2$s, %3$s, and %4$d other are discussing.', '%1$s, %2$s, %3$s, and %4$d others are discussing.', $others, 'p3' ),
			$unique_commentors[0],
			$unique_commentors[1],
			$unique_commentors[2],
			$others
		);
	}

	return $content;
}

function p3_quote_content() {
	echo p3_get_quote_content();
}
	function p3_get_quote_content() {
		return sprintf( '<blockquote>%s</blockquote>', apply_filters( 'p3_get_quote_content', get_the_content( __( '(More ...)' , 'p3' ) ) ) );
	}
	add_filter( 'p3_get_quote_content', 'p3_quote_filter_kses', 1 );
	add_filter( 'p3_get_quote_content', 'wptexturize' );
	add_filter( 'p3_get_quote_content', 'convert_smilies' );
	add_filter( 'p3_get_quote_content', 'convert_chars' );
	add_filter( 'p3_get_quote_content', 'prepend_attachment' );
	add_filter( 'p3_get_quote_content', 'make_clickable' );

	function p3_quote_filter_kses( $content ) {
		global $allowedtags;

		$quote_allowedtags = $allowedtags;
		$quote_allowedtags['cite'] = array();
		$quote_allowedtags['p'] = array();

		return wp_kses( $content, $quote_allowedtags );
	}

/**
 * Get post format for current post object.
 *
 * The value should be a valid post format or one of the back compat categories.
 *
 * @since P3 1.3.4
 * @uses p3_get_the_category for back compat category check
 * @uses p3_get_supported_post_formats for accepted values
 *
 * @param object post_id Uses global post if in the loop; required for use outside the loop
 * @return string
 */
function p3_get_post_format( $post_id = null ) {
	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	if ( empty( $post_id ) )
		return '';

	// 1- try to get post format, first
	$post_format = get_post_format( $post_id );

	// 2- try back compat category, next
	if ( false === $post_format )
		$post_format = p3_get_the_category( $post_id );

	// Check against accepted values
	if ( empty( $post_format ) || ! in_array( $post_format, p3_get_supported_post_formats() ) )
		$post_format = 'standard';

	return $post_format;
}

function p3_get_the_category( $post_id = null ) {
	$categories = get_the_category( $post_id );
	$slug = ( isset( $categories[0] ) ) ? $categories[0]->slug : '';
	return apply_filters( 'p3_get_the_category', $slug );
}

function p3_user_prompt() {
	echo p3_get_user_prompt();
}
	function p3_get_user_prompt() {
		$prompt = get_option( 'p3_prompt_text' );

		return apply_filters( 'p3_get_user_prompt', sprintf ( __( 'Hi, %s. %s', 'p3' ), esc_html( p3_get_user_display_name() ), ( $prompt != '' ) ? stripslashes( $prompt ) : __( 'Whatcha up to?', 'p3' ) ) );
	}

function p3_page_number() {
	echo p3_get_page_number();
}
	function p3_get_page_number() {
		global $paged;
		return apply_filters( 'p3_get_page_number', $paged );
	}

function p3_media_buttons( $ssl_compat=false ) {
	// If we're using http and the admin is forced to https, bail.
	if ( $ssl_compat && ! is_ssl() && ( force_ssl_admin() || get_user_option( 'use_ssl' ) )  ) {
		return;
	}

	include_once( ABSPATH . '/wp-admin/includes/media.php' );
	ob_start();
	global $post_ID, $post;
	$post_ID = $post = 0;
	do_action( 'media_buttons' );
	$buttons = ob_get_clean();

	// Replace any relative paths to media-upload.php
	$buttons = preg_replace( '/([\'"])media-upload.php/', '${1}' . admin_url( 'media-upload.php' ), $buttons );

	// Remove any images.
	$buttons = preg_replace( '/<img [^>]*src=(\"|\')(.+?)(\1)[^>]*>/i', '', $buttons );

	echo $buttons;
}

function p3_get_hide_sidebar() {
	return ( '' != get_option( 'p3_hide_sidebar' ) ) ? true : false;
}

function p3_archive_author() {
	echo p3_get_archive_author();
}

function p3_get_archive_author() {
	$author = '';
	if ( is_author() )
		$author = get_the_author_meta( 'display_name', get_queried_object_id() );

	return apply_filters( 'p3_get_archive_author', $author );
}

function p3_author_feed_link() {
	echo p3_get_author_feed_link();
}
	function p3_get_author_feed_link() {

		$author_id = get_queried_object_id();

		if ( isset( $author_id ) )
			return apply_filters( 'p3_get_author_feed_link', get_author_feed_link( $author_id ) );
	}

function p3_user_identity() {
	echo p3_get_user_identity();
}
	function p3_get_user_identity() {
		global $user_identity;
		return $user_identity;
	}

function p3_load_entry( $force_comments = true ) {
	global $withcomments;

	if ( $force_comments )
		$withcomments = true;

	get_template_part( 'entry' );
}

function p3_date_time_with_microformat( $type = 'post' ) {
	$d = ( 'comment' == $type ) ? 'get_comment_time' : 'get_post_time';
	$title = $d( 'Y-m-d\TH:i:s\Z', true);
	$time = $d( get_option( 'time_format' ) );
	$date = $d( get_option( 'date_format' ) );
	$out = '<abbr title="' . $title . '">' . sprintf( __( '%1$s <em>on</em> %2$s', 'p3' ),  $time , $date ) . '</abbr>';
	return apply_filters( 'p3_date_time_with_microformat', $out, $title, $time, $date );
}
