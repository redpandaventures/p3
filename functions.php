<?php
/**
 * @package P3
 */

require_once( get_template_directory() . '/inc/utils.php' );

p3_maybe_define( 'P3_INC_PATH', get_template_directory()     . '/inc' );
p3_maybe_define( 'P3_INC_URL',  get_template_directory_uri() . '/inc' );
p3_maybe_define( 'P3_JS_PATH',  get_template_directory()     . '/js'  );
p3_maybe_define( 'P3_JS_URL',   get_template_directory_uri() . '/js'  );

class P3 {
	/**
	 * DB version.
	 *
	 * @var int
	 */
	var $db_version = 3;

	/**
	 * Options.
	 *
	 * @var array
	 */
	var $options = array();

	/**
	 * Option name in DB.
	 *
	 * @var string
	 */
	var $option_name = 'p3_manager';

	/**
	 * Components.
	 *
	 * @var array
	 */
	var $components = array();

	/**
	 * Includes and instantiates the various P3 components.
	 */
	public function __construct() {
		// Fetch options
		$this->options = get_option( $this->option_name );
		if ( false === $this->options )
			$this->options = array();

		// Include the P3 components
		$includes = array( 'compat', 'terms-in-comments', 'js-locale',
			'mentions', 'search', 'js', 'options-page', 'widgets/recent-tags', 'widgets/recent-comments',
			'list-creator' );

		require_once( P3_INC_PATH . "/template-tags.php" );

		// Logged-out/unprivileged users use the add_feed() + ::ajax_read() API rather than the /admin-ajax.php API
		// current_user_can( 'read' ) should be equivalent to is_user_member_of_blog()
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( p3_user_can_post() || current_user_can( 'read' ) ) )
			$includes[] = 'ajax';

		foreach ( $includes as $name ) {
			require_once( P3_INC_PATH . "/$name.php" );
		}

		// Add the default P3 components
		$this->add( 'mentions',             'P3_Mentions'             );
		$this->add( 'search',               'P3_Search'               );
		$this->add( 'post-list-creator',    'P3_Post_List_Creator'    );
		$this->add( 'comment-list-creator', 'P3_Comment_List_Creator' );

		// Bind actions
		add_action( 'init',       array( &$this, 'init'             ) );
		add_action( 'admin_init', array( &$this, 'maybe_upgrade_db' ), 5 );
	}

	function init() {
		// Load language pack
		load_theme_textdomain( 'p3', get_template_directory() . '/languages' );

		// Set up the AJAX read handler
		add_feed( 'p3.ajax', array( $this, 'ajax_read' ) );
	}

	function ajax_read() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		require_once( P3_INC_PATH . '/ajax-read.php' );

		P3Ajax_Read::dispatch();
	}

	/**
	 * Will upgrade the database if necessary.
	 *
	 * When upgrading, triggers actions:
	 *    'p3_upgrade_db_version'
	 *    'p3_upgrade_db_version_$number'
	 *
	 * Flushes rewrite rules automatically on upgrade.
	 */
	function maybe_upgrade_db() {
		if ( ! isset( $this->options['db_version'] ) || $this->options['db_version'] < $this->db_version ) {
			$current_db_version = isset( $this->options['db_version'] ) ? $this->options['db_version'] : 0;

			do_action( 'p3_upgrade_db_version', $current_db_version );
			for ( ; $current_db_version <= $this->db_version; $current_db_version++ ) {
				do_action( "p3_upgrade_db_version_$current_db_version" );
			}

			// Flush rewrite rules once, so callbacks don't have to.
			flush_rewrite_rules();

			$this->set_option( 'db_version', $this->db_version );
			$this->save_options();
		}
	}

	/**
	 * COMPONENTS API
	 */
	function add( $component, $class ) {
		$class = apply_filters( "p3_add_component_$component", $class );
		if ( class_exists( $class ) )
			$this->components[ $component ] = new $class();
	}
	function get( $component ) {
		return $this->components[ $component ];
	}
	function remove( $component ) {
		unset( $this->components[ $component ] );
	}

	/**
	 * OPTIONS API
	 */
	function get_option( $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;
	}
	function set_option( $key, $value ) {
		return $this->options[ $key ] = $value;
	}
	function save_options() {
		update_option( $this->option_name, $this->options );
	}
}

$GLOBALS['p3'] = new P3;

function p3_get( $component = '' ) {
	global $p3;
	return empty( $component ) ? $p3 : $p3->get( $component );
}
function p3_get_option( $key ) {
	return $GLOBALS['p3']->get_option( $key );
}
function p3_set_option( $key, $value ) {
	return $GLOBALS['p3']->set_option( $key, $value );
}
function p3_save_options() {
	return $GLOBALS['p3']->save_options();
}




/**
 * ----------------------------------------------------------------------------
 * NOTE: Ideally, the rest of this file should be moved elsewhere.
 * ----------------------------------------------------------------------------
 */

if ( ! isset( $content_width ) )
	$content_width = 632;

$themecolors = array(
	'bg'     => 'ffffff',
	'text'   => '555555',
	'link'   => '3478e3',
	'border' => 'f1f1f1',
	'url'    => 'd54e21',
);

/**
 * Setup P3 Theme.
 *
 * Hooks into the after_setup_theme action.
 *
 * @uses p3_get_supported_post_formats()
 */
function p3_setup() {
	require_once( get_template_directory() . '/inc/custom-header.php' );
	p3_setup_custom_header();

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-formats', p3_get_supported_post_formats( 'post-format' ) );

	add_theme_support( 'custom-background', apply_filters( 'p3_custom_background_args', array( 'default-color' => 'f1f1f1' ) ) );

	add_filter( 'the_content', 'make_clickable', 12 ); // Run later to avoid shortcode conflicts

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'p3' ),
	) );

	if ( is_admin() && false === get_option( 'prologue_show_titles' ) )
		add_option( 'prologue_show_titles', 1 );
}
add_filter( 'after_setup_theme', 'p3_setup' );

function p3_register_sidebar() {
	register_sidebar( array(
		'name' => __( 'Sidebar', 'p3' ),
		'id'   => 'sidebar-1',
	) );
}
add_action( 'widgets_init', 'p3_register_sidebar' );

function p3_background_color() {
	$background_color = get_option( 'p3_background_color' );

	if ( '' != $background_color ) :
	?>
	<style type="text/css">
		body {
			background-color: <?php echo esc_attr( $background_color ); ?>;
		}
	</style>
	<?php endif;
}
add_action( 'wp_head', 'p3_background_color' );

function p3_background_image() {
	$p3_background_image = get_option( 'p3_background_image' );

	if ( 'none' == $p3_background_image || '' == $p3_background_image )
		return false;

?>
	<style type="text/css">
		body {
			background-image: url( <?php echo get_template_directory_uri() . '/i/backgrounds/pattern-' . sanitize_key( $p3_background_image ) . '.png' ?> );
		}
	</style>
<?php
}
add_action( 'wp_head', 'p3_background_image' );

/**
 * Add a custom class to the body tag for the background image theme option.
 *
 * This dynamic class is used to style the bundled background
 * images for retina screens. Note: The background images that
 * ship with P3 have been deprecated as of P3 1.5. For backwards
 * compatibility, P3 will still recognize them if the option was
 * set before upgrading.
 *
 * @since P3 1.5
 */
function p3_body_class_background_image( $classes ) {
	$image = get_option( 'p3_background_image' );

	if ( empty( $image ) || 'none' == $image )
		return $classes;

	$classes[] = esc_attr( 'p3-background-image-' . $image );

	return $classes;
}
add_action( 'body_class', 'p3_body_class_background_image' );

// Content Filters
function p3_title( $before = '<h2>', $after = '</h2>', $echo = true ) {
	if ( is_page() )
		return;

	if ( is_single() && false === p3_the_title( '', '', false ) ) { ?>
		<h2 class="transparent-title"><?php the_title(); ?></h2><?php
		return true;
	} else {
		p3_the_title( $before, $after, $echo );
	}
}

/**
 * Generate a nicely formatted post title
 *
 * Ignore empty titles, titles that are auto-generated from the
 * first part of the post_content
 *
 * @package WordPress
 * @subpackage P3
 * @since 1.0.5
 *
 * @param    string    $before    content to prepend to title
 * @param    string    $after     content to append to title
 * @param    string    $echo      echo or return
 * @return   string    $out       nicely formatted title, will be boolean(false) if no title
 */
function p3_the_title( $before = '<h2>', $after = '</h2>', $echo = true ) {
	global $post;

	$temp = $post;
	$t = apply_filters( 'the_title', $temp->post_title, $temp->ID );
	$title = $temp->post_title;
	$content = $temp->post_content;
	$pos = 0;
	$out = '';

	// Don't show post title if turned off in options or title is default text
	if ( 1 != (int) get_option( 'prologue_show_titles' ) || 'Post Title' == $title )
		return false;

	$content = trim( $content );
	$title = trim( $title );
	$title = preg_replace( '/\.\.\.$/', '', $title );
	$title = str_replace( "\n", ' ', $title );
	$title = str_replace( '  ', ' ', $title);
	$content = str_replace( "\n", ' ', strip_tags( $content) );
	$content = str_replace( '  ', ' ', $content );
	$content = trim( $content );
	$title = trim( $title );

	// Clean up links in the title
	if ( false !== strpos( $title, 'http' ) )  {
		$split = @str_split( $content, strpos( $content, 'http' ) );
		$content = $split[0];
		$split2 = @str_split( $title, strpos( $title, 'http' ) );
		$title = $split2[0];
	}

	// Avoid processing an empty title
	if ( '' == $title )
		return false;

	// Avoid processing the title if it's the very first part of the post content,
	// which is the case with most "status" posts
	$pos = strpos( $content, $title );
	if ( '' == get_post_format() || false === $pos || 0 < $pos ) {
		if ( is_single() )
			$out = $before . $t . $after;
		else
			$out = $before . '<a href="' . get_permalink( $temp->ID ) . '">' . $t . '&nbsp;</a>' . $after;

		if ( $echo )
			echo $out;
		else
			return $out;
	}

	return false;
}

function p3_comments( $comment, $args ) {
	$GLOBALS['comment'] = $comment;

	if ( !is_single() && get_comment_type() != 'comment' )
		return;

	$depth          = prologue_get_comment_depth( get_comment_ID() );
	$can_edit_post  = current_user_can( 'edit_post', $comment->comment_post_ID );

	$reply_link     = prologue_get_comment_reply_link(
		array( 'depth' => $depth, 'max_depth' => $args['max_depth'], 'before' => ' | ', 'reply_text' => __( 'Reply', 'p3' ) ),
		$comment->comment_ID, $comment->comment_post_ID );

	$content_class  = 'commentcontent';
	if ( $can_edit_post )
		$content_class .= ' comment-edit';

	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
		<?php do_action( 'p3_comment' ); ?>

		<?php echo get_avatar( $comment, 32 ); ?>
		<h4>
			<?php echo get_comment_author_link(); ?>
			<span class="meta">
				<?php echo p3_date_time_with_microformat( 'comment' ); ?>
				<span class="actions">
					<a class="thepermalink" href="<?php echo esc_url( get_comment_link() ); ?>" title="<?php esc_attr_e( 'Permalink', 'p3' ); ?>"><?php _e( 'Permalink', 'p3' ); ?></a>
					<?php
					echo $reply_link;

					if ( $can_edit_post )
						edit_comment_link( __( 'Edit', 'p3' ), ' | ' );

					?>
				</span>
			</span>
		</h4>
		<div id="commentcontent-<?php comment_ID(); ?>" class="<?php echo esc_attr( $content_class ); ?>"><?php
				echo apply_filters( 'comment_text', $comment->comment_content, $comment );

				if ( $comment->comment_approved == '0' ): ?>
					<p><em><?php esc_html_e( 'Your comment is awaiting moderation.', 'p3' ); ?></em></p>
				<?php endif; ?>
		</div>
	<?php
}

function get_tags_with_count( $post, $format = 'list', $before = '', $sep = '', $after = '' ) {
	$posttags = get_the_tags($post->ID, 'post_tag' );

	if ( !$posttags )
		return '';

	foreach ( $posttags as $tag ) {
		if ( $tag->count > 1 && !is_tag($tag->slug) ) {
			$tag_link = '<a href="' . get_tag_link( $tag ) . '" rel="tag">' . $tag->name . ' ( ' . number_format_i18n( $tag->count ) . ' )</a>';
		} else {
			$tag_link = $tag->name;
		}

		if ( $format == 'list' )
			$tag_link = '<li>' . $tag_link . '</li>';

		$tag_links[] = $tag_link;
	}

	return apply_filters( 'tags_with_count', $before . join( $sep, $tag_links ) . $after, $post );
}

function tags_with_count( $format = 'list', $before = '', $sep = '', $after = '' ) {
	global $post;
	echo get_tags_with_count( $post, $format, $before, $sep, $after );
}

function p3_title_from_content( $content ) {
	$title = p3_excerpted_title( $content, 8 ); // limit title to 8 full words

	// Try to detect image or video only posts, and set post title accordingly
	if ( empty( $title ) ) {
		if ( preg_match("/<object|<embed/", $content ) )
			$title = __( 'Video Post', 'p3' );
		elseif ( preg_match( "/<img/", $content ) )
			$title = __( 'Image Post', 'p3' );
	}

	return $title;
}

function p3_excerpted_title( $content, $word_count ) {
	$content = strip_tags( $content );
	$words = preg_split( '/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/', $content, $word_count + 1, PREG_SPLIT_NO_EMPTY );

	if ( count( $words ) > $word_count ) {
		array_pop( $words ); // remove remainder of words
		$content = implode( ' ', $words );
		$content = $content . '...';
	} else {
		$content = implode( ' ', $words );
	}

	$content = trim( strip_tags( $content ) );

	return $content;
}

function p3_add_reply_title_attribute( $link ) {
	return str_replace( "rel='nofollow'", "rel='nofollow' title='" . __( 'Reply', 'p3' ) . "'", $link );
}
add_filter( 'post_comments_link', 'p3_add_reply_title_attribute' );

function p3_fix_empty_titles( $data, $postarr ) {
	if ( 'post' != $data['post_type'] )
		return $data;

	if ( ! empty( $postarr['post_title'] ) )
		return $data;

	$data['post_title'] = p3_title_from_content( $data['post_content'] );

	return $data;
}
add_filter( 'wp_insert_post_data', 'p3_fix_empty_titles', 10, 2 );

function p3_add_head_content() {
	if ( is_home() && is_user_logged_in() ) {
		include_once( ABSPATH . '/wp-admin/includes/media.php' );
	}
}
add_action( 'wp_head', 'p3_add_head_content' );

function p3_new_post_noajax() {
	if ( empty( $_POST['action'] ) || $_POST['action'] != 'post' )
	    return;

	if ( !is_user_logged_in() )
		auth_redirect();

	if ( !current_user_can( 'publish_posts' ) ) {
		wp_redirect( home_url( '/' ) );
		exit;
	}

	$current_user = wp_get_current_user();

	check_admin_referer( 'new-post' );

	$user_id        = $current_user->ID;
	$post_content   = $_POST['posttext'];
	$tags           = $_POST['tags'];

	$post_title = p3_title_from_content( $post_content );

	$post_id = wp_insert_post( array(
		'post_author'   => $user_id,
		'post_title'    => $post_title,
		'post_content'  => $post_content,
		'tags_input'    => $tags,
		'post_status'   => 'publish'
	) );

	$post_format = 'status';
	if ( in_array( $_POST['post_format'], p3_get_supported_post_formats() ) )
		$post_format = $_POST['post_format'];

	set_post_format( $post_id, $post_format );

	wp_redirect( home_url( '/' ) );

	exit;
}
add_filter( 'template_redirect', 'p3_new_post_noajax' );

/**
 * iPhone Stylesheet.
 *
 * Hooks into the wp_enqueue_scripts action late.
 *
 * @uses p3_is_iphone()
 * @since P3 1.4
 */
function p3_iphone_style() {
	if ( p3_is_iphone() ) {
		wp_enqueue_style(
			'p3-iphone-style',
			get_template_directory_uri() . '/style-iphone.css',
			array(),
			'20120402'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'p3_iphone_style', 1000 );

/**
 * Print Stylesheet.
 *
 * Hooks into the wp_enqueue_scripts action.
 *
 * @since P3 1.5
 */
function p3_print_style() {
	wp_enqueue_style( 'p3', get_stylesheet_uri() );
	wp_enqueue_style( 'p3-print-style', get_template_directory_uri() . '/style-print.css', array( 'p3' ), '20120807', 'print' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );
}
add_action( 'wp_enqueue_scripts', 'p3_print_style' );

/*
	Modified to replace query string with blog url in output string
*/
function prologue_get_comment_reply_link( $args = array(), $comment = null, $post = null ) {
	global $user_ID;

	if ( post_password_required() )
		return;

	$defaults = array( 'add_below' => 'commentcontent', 'respond_id' => 'respond', 'reply_text' => __( 'Reply', 'p3' ),
		'login_text' => __( 'Log in to Reply', 'p3' ), 'depth' => 0, 'before' => '', 'after' => '' );

	$args = wp_parse_args($args, $defaults);
	if ( 0 == $args['depth'] || $args['max_depth'] <= $args['depth'] )
		return;

	extract($args, EXTR_SKIP);

	$comment = get_comment($comment);
	$post = get_post($post);

	if ( 'open' != $post->comment_status )
		return false;

	$link = '';

	$reply_text = esc_html( $reply_text );

	if ( get_option( 'comment_registration' ) && !$user_ID )
		$link = '<a rel="nofollow" href="' . site_url( 'wp-login.php?redirect_to=' . urlencode( get_permalink() ) ) . '">' . esc_html( $login_text ) . '</a>';
	else
		$link = "<a rel='nofollow' class='comment-reply-link' href='". get_permalink($post). "#" . urlencode( $respond_id ) . "' title='". __( 'Reply', 'p3' )."' onclick='return addComment.moveForm(\"" . esc_js( "$add_below-$comment->comment_ID" ) . "\", \"$comment->comment_ID\", \"" . esc_js( $respond_id ) . "\", \"$post->ID\")'>$reply_text</a>";
	return apply_filters( 'comment_reply_link', $before . $link . $after, $args, $comment, $post);
}

function prologue_comment_depth_loop( $comment_id, $depth )  {
	$comment = get_comment( $comment_id );

	if ( isset( $comment->comment_parent ) && 0 != $comment->comment_parent ) {
		return prologue_comment_depth_loop( $comment->comment_parent, $depth + 1 );
	}
	return $depth;
}

function prologue_get_comment_depth( $comment_id ) {
	return prologue_comment_depth_loop( $comment_id, 1 );
}

function prologue_comment_depth( $comment_id ) {
	echo prologue_get_comment_depth( $comment_id );
}

function prologue_poweredby_link() {
	return apply_filters( 'prologue_poweredby_link', sprintf( '<a href="%1$s" rel="generator">%2$s</a>', esc_url( __('http://wordpress.org/', 'p3') ), sprintf( __('Proudly powered by %s.', 'p3'), 'WordPress' ) ) );
}

function p3_hidden_sidebar_css() {
	$hide_sidebar = get_option( 'p3_hide_sidebar' );
		$sleeve_margin = ( is_rtl() ) ? 'margin-left: 0;' : 'margin-right: 0;';
	if ( '' != $hide_sidebar ) :
	?>
	<style type="text/css">
		.sleeve_main { <?php echo $sleeve_margin;?> }
		#wrapper { background: transparent; }
		#header, #footer, #wrapper { width: 760px; }
	</style>
	<?php endif;
}
add_action( 'wp_head', 'p3_hidden_sidebar_css' );

// Network signup form
function p3_before_signup_form() {
	echo '<div class="sleeve_main"><div id="main">';
}
add_action( 'before_signup_form', 'p3_before_signup_form' );

function p3_after_signup_form() {
	echo '</div></div>';
}
add_action( 'after_signup_form', 'p3_after_signup_form' );

/**
 * Returns accepted post formats.
 *
 * The value should be a valid post format registered for P3, or one of the back compat categories.
 * post formats: link, quote, standard, status
 * categories: link, post, quote, status
 *
 * @since P3 1.3.4
 *
 * @param string type Which data to return (all|category|post-format)
 * @return array
 */
function p3_get_supported_post_formats( $type = 'all' ) {
	$post_formats = array( 'link', 'quote', 'status' );

	switch ( $type ) {
		case 'post-format':
			break;
		case 'category':
			$post_formats[] = 'post';
			break;
		case 'all':
		default:
			array_push( $post_formats, 'post', 'standard' );
			break;
	}

	return apply_filters( 'p3_get_supported_post_formats', $post_formats );
}

/**
 * Is site being viewed on an iPhone or iPod Touch?
 *
 * For testing you can modify the output with a filter:
 * add_filter( 'p3_is_iphone', '__return_true' );
 *
 * @return bool
 * @since P3 1.4
 */
function p3_is_iphone() {
	$output = false;

	if ( ( isset( $_SERVER['HTTP_USER_AGENT'] ) && strstr( $_SERVER['HTTP_USER_AGENT'], 'iPhone' ) && ! strstr( $_SERVER['HTTP_USER_AGENT'], 'iPad' ) ) || isset( $_GET['iphone'] ) && $_GET['iphone'] )
		$output = true;

	$output = (bool) apply_filters( 'p3_is_iphone', $output );

	return $output;
}

/**
 * Filters wp_title to print a neat <title> tag based on what is being viewed.
 *
 * @since P3 1.5
 */
function p3_wp_title( $title, $sep ) {
	global $page, $paged;

	if ( is_feed() )
		return $title;

	// Add the blog name
	$title .= get_bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		$title .= " $sep $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		$title .= " $sep " . sprintf( __( 'Page %s', 'p3' ), max( $paged, $page ) );

	return $title;
}
add_filter( 'wp_title', 'p3_wp_title', 10, 2 );
