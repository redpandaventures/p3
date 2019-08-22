<?php 

/**
 * Central class for parsing lists.
 *
 * @package P3
 */
class P3_List_Creator {
	/**
	 * @var string name for action parameter of HTML form (not the action attribute, which is always the admin-ajax.php URL)
	 */
	var $form_action_name = '';
	/**
	 * @var bool Are we currently in a nested list?
	 */
	var $doing_recursion = false;

	var $preserved_texts = array();

	public function __construct() {
		// Have we done the CSS/JS already?
		static $did_header = false;

		if ( $this->form_action_name ) {
			// Add form submission handler
			add_action( "wp_ajax_{$this->form_action_name}", array( $this, 'submit' ) );
		}

		if ( $did_header ) {
			return;
		}

		$did_header = true;

		add_action( 'wp_head', array( $this, 'css' ) );
		add_action( 'wp_head', array( $this, 'js' ) );
		if ( !is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );
		}
	}

	function enqueue_js() {
		wp_enqueue_script( 'jquery-color' );
	}

	function css() {
?>
<style type="text/css">
.is-js .hide-if-js {
	display: none;
}
.p3-task-list ul {
	margin-left: 0 !important;
}
.p3-task-list ul ul {
	margin-left: 20px !important;
}
.p3-task-list li {
	list-style: none;
}
</style>
<?php
	}

	function js() {
?>
<script type="text/javascript">
jQuery( function( $ ) {
	$( 'body' )
		.addClass( 'is-js' )
		.delegate( '.p3-task-list :checkbox', 'click', function() {
			var $this = $( this ),
			    $li = $this.parents( 'li:first' ),
			    $form = $this.parents( 'form:first' ),
			    data = $li.find( ':input' ).serialize(),
			    colorEl = $li, origColor = $li.css( 'background-color' ), color;

			while ( colorEl.get(0).tagName && colorEl.css( 'background-color' ).match( /^\s*(rgba\s*\(\s*0+\s*,\s*0+\s*,\s*0+\s*,\s*0+\s*\)|transparent)\s*$/ ) ) {
				colorEl = colorEl.parent();
			}

			color = colorEl.get(0).tagName ? colorEl.css( 'background-color' ) : '#ffffff';

			data += '&ajax=1&' + $form.find( '.submit :input' ).serialize();

			$.post( $form.attr( 'action' ), data, function( response ) {
				if ( '1' === response )
					$li.css( 'background-color', '#F6F3D1' ).animate( { backgroundColor: color }, 'slow', function() { $li.css( 'background-color', origColor ); } );
			} );
	} );
} );
</script>
<?php
	}

	function regex_to_parse() {
		if ( $this->parse_task_lists() ) {
			// Parse UL/OL/Task lists
			return '!              # $0 = whole list
				^
				([ ]{0,3})     # $1 = nested list space padding
				(              # $2 = list item marker
					([xo]) # $3 = task list item marker
				|
					[#*-]  # UL/OL item marker
				)
				\s+            # Mandatory whitespace after the list item marker
				.*             # List item
				$              # EOL
				(?:            # Multiple list items of the same type
					\n     # New line
					^      # BOL
					(?:
						\1             # Same amount of padding
						(?(3)(?3)|\2)  # Same list item marker
					|                      # OR ...
						\1[ ]{1,}      # Increased padding (start of nested list)
						(?2)           # Any list item marker
					)
					\s+    # Mandatory whitespace after the list item marker
					.*     # List item
					$      # EOL
				)*
			!mx';
		}

		return '!               # $0 = whole list
			^
			([ ]{0,3})      # $1 = number of spaces
			([#*-])         # $2 = UL/OL item marker
			\s+             # Mandatory whitespace after the list item marker
			.*              # List item
			$               # EOL
			(?:
				\n      # New line
				^       # BOL
				\1[ ]*  # Same or increased amount of padding
				[xo#*-] # Any list item marker
				\s+     # Mandatory whitespace after the list item marker
				.*      # List item
				$       # EOL
			)*
		!mx';
	}

	function task_list_regex_to_unparse() {
		return '!               # $0 = whole list
			^
			([ ]{0,3})      # $1 = number of spaces
			([xo])          # $2 = tasklist item marker
			\s+             # Mandatory whitespace after the list item marker
			.*              # List item
			$               # EOL
			(?:
				\n      # New line
				^       # BOL
				\1[ ]*  # Same or increased amount of padding
				[xo#*-] # Any list item marker
				\s+     # Mandatory whitespace after the list item marker
				.*      # List item
				$       # EOL
			)*
		!mx';
	}

	function preserve_text( $text ) {
		global $SyntaxHighlighter;

		if ( false !== strpos( $text, '[' ) && is_a( $SyntaxHighlighter, 'SyntaxHighlighter' ) && $SyntaxHighlighter->shortcodes ) {
			$shortcodes_regex = '#\[(' . join( '|', array_map( 'preg_quote', $SyntaxHighlighter->shortcodes ) ) . ')(?:\s|\]).*\[/\\1\]#s';
			$text = preg_replace_callback( $shortcodes_regex, array( $this, 'preserve_text_callback' ), $text );
		}

		if ( false !== strpos( $text, '<pre' ) ) {
			$text = preg_replace_callback( '#<pre(?:\s|>).*</pre>#s', array( $this, 'preserve_text_callback' ), $text );
		}

		return $text;
	}

	function preserve_text_callback( $matches ) {
		$hash = md5( $matches[0] );
		$this->preserved_text[$hash] = $matches[0];
		return "[preserved_text $hash /]";
	}

	function restore_text( $text ) {
		if ( false === strpos( $text, '[preserved_text ' ) ) {
			return $text;
		}

		return preg_replace_callback( '#\[preserved_text (\S+) /\]#', array( $this, 'restore_text_callback' ), $text );
	}

	function restore_text_callback( $matches ) {
		if ( isset( $this->preserved_text[$matches[1]] ) ) {
			return $this->preserved_text[$matches[1]];
		}

		return $matches[0];
	}

	/**
	 * Converts * and - into ULs, # into OLs, and x and o into task lists.
	 *
	 * @param string $text Plaintext to parse for lists
	 * @param bool $doing_recursion Are we in a nested list?
	 * @return string HTML
	 */
	function parse_list( $text, $doing_recursion = false ) {
		$text = $this->preserve_text( $text );

		$text = preg_replace( '/(\r\n|\r|\n)/', "\n", $text );

		// Run our regex through the callback, get the eventual text a few levels down and return it back to P3 here.

		$old_doing_recursion = $this->doing_recursion;
		$this->doing_recursion = $doing_recursion;
		$r = preg_replace_callback( $this->regex_to_parse(), array( $this, '_do_list_callback' ), $text );
		$this->doing_recursion = $old_doing_recursion;

		return $this->restore_text( $r );
	}

	function task_list_counter( $action = 'get' ) {
		static $id = 0;

		switch ( $action ) {
		case 'increment' :
			$id++;
			break;
		case 'reset' :
			$id = 0;
			break;
		}

		return $id;
	}

	function reset_task_list_counter( $content ) {
		$this->task_list_counter( 'reset' );

		return $content;
	}

	function task_list_item_id( $action = 'get' ) {
		static $item_ids = array();

		$object_id = $this->get_object_id();

		if ( !isset( $item_ids[$object_id] ) ) {
			$item_ids[$object_id] = 0;
		}

		switch ( $action ) {
		case 'increment' :
			$item_ids[$object_id]++;
			break;
		case 'reset' :
			$item_ids[$object_id] = 0;
			break;
		}

		return $item_ids[$object_id];
	}

	function reset_task_list_item_id( $content ) {
		$this->task_list_item_id( 'reset' );

		return $content;
	}

	/**
	 * Adds UL/OL markup, adds FORM markup for task lists.  Calls internal functions for adding LI markup.
	 *
	 * @param array $matches Regex matches from ::parse_list()
	 * @return string HTML
	 */
	function _do_list_callback( $matches ) {
		$id = $this->task_list_counter();

		$doing_recursion = $this->doing_recursion;

		$indent = strlen( $matches[1] );
		switch ( $matches[2] ) {
		case '*' : // UL
		case '-' : // UL
		case '#' : // OL
			if ( '#' == $matches[2] ) {
				$tag = 'ol';
			} else {
				$tag = 'ul';
			}

			// Easy peasy, lemon squeezy.
			return "<$tag>\n" . $this->process_list_items( $matches[0], $indent, $matches[2] ) . "\n</$tag>\n\n";
			break;
		case 'x' : // Task List
		case 'o' : // Task List
			$return = "<ul>\n" . $this->process_task_list_items( $matches[0], $indent ) . "\n</ul>\n\n";

			if ( !$this->current_user_can( $this->get_object_id() ) ) {
				// User is not allowed to edit the post/comment.  No form required.
				return $return;
			}

			// Don't nest form elements
			if ( $doing_recursion ) {
				return $return;
			}

			$id = $this->task_list_counter( 'increment' );

			// Add form
			$ajax_url = remove_query_arg( 'p3ajax', P3_JS::ajax_url() );
			$return  = sprintf( '<form class="p3-task-list" id="p3-task-list-%d" action="%s" method="post">', $id, esc_url( $ajax_url ) ) . $return;
			$return .= "<p class='hide-if-js submit'>\n";
			$return .= "<input type='hidden' name='id' value='$id' />\n";
			$return .= sprintf( "<input type='hidden' name='action' value='%s' />\n", $this->form_action_name );
			$return .= "<input type='submit' value='Save' />\n";
			$return .= wp_nonce_field( "p3-task-list_$id", "_p3_task_list_nonce_$id", true, false );
			$return .= "\n</p>\n</form>";

			return $return;
		}
	}

	/**
	 * Adds LI markup.  Recursively calls ::parse_list() to handle nested lists.
	 *
	 * @param string $text   Plaintext list items
	 * @param int    $indent Number of padding spacess (nesting level)
	 * @param string $marker Which list item marker is being processed (#, *, -)
	 * @return string HTML
	 */
	function process_list_items( $text, $indent, $marker ) {
		// Break list into list items with the same nesting level and item marker
		$items = array_map( 'trim', preg_split( '/^[ ]{' . $indent . '}[' . $marker . ']/m', $text, -1, PREG_SPLIT_NO_EMPTY ) );
		$out = array();
		foreach ( $items as $item ) {
			if ( false !== strpos( $item, "\n" ) ) {
				// Has a nested list.  Newlines for parseability in recursion
				$out[] = "<li>\n$item\n</li>";
			} else {
				$out[] = "<li>$item</li>";
			}
		}
		$text = join( "\n", $out );
		return $this->parse_list( $text, true );
	}

	/**
	 * Adds LI markup, adds INPUT markup.  Recursively calls ::parse_list() to handle nested lists.
	 *
	 * @param string $text    Plaintext list items
	 * @param int    $indent  Number of padding spacess (nesting level)
	 * @param string $context 'display' or 'edit'
	 * @return string HTML
	 */
	function process_task_list_items( $text, $indent, $context = 'display' ) {
		global $post, $comment;

		$object_id = $this->get_object_id();
		$current_user_can = $this->current_user_can();

		$item_id = $this->task_list_item_id();

		// Break list into list items with the same nesting level and note item marker (x, o)
		$items = array_map( 'trim', preg_split( '/^[ ]{' . $indent . '}([xo])/m', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE ) );

		// Heinous sprintf
		if ( 'edit' == $context ) {
			$format = '%9$s%7$s %5$s%10$s';
		} else {
			$format = '<li id="p3-task-%1$d-%2$d"><label>%8$s<input type="checkbox" name="p3_task[%1$d][%2$d]"%3$s%4$s value="1" /><input type="hidden" name="p3_task_ids[%1$d][%2$d]" value="%6$s" /> %5$s%10$s%8$s</label></li>';
		}

		$out = array();
		foreach ( $items as $i => $item ) {
			if ( !( $i % 2 ) ) {
				// Item marker: x, o
				$checked_in_post_state = $item;
				$checked_in_post = 'x' == $checked_in_post_state;
				continue;
			}

			$item_id = $this->task_list_item_id( 'increment' );

			$checked_in_meta = $this->get_item_data( $item_id );
			if ( $checked_in_meta ) {
				list( $checked, $checker, $check_timestamp ) = $checked_in_meta;
				if ( $checked ) {
					$user = get_user_by( 'id', $checker );
					$task_meta = " (@{$user->user_login})";
					$check_time = ' datetime="' . esc_attr( gmdate( 'Y-m-d\TH:i:s+0000', $check_timestamp ) ) . '"';
				} else {
					$task_meta = '';
					$check_time = '';
				}
			} else {
				$checked = $checked_in_post;
				$task_meta = '';
				$check_time = '';
			}

			$disabled = $current_user_can ? '' : ' disabled="disabled"';
			if ( 'edit' != $context ) {
				if ( $checked ) {
					$item = "<del{$check_time}>" . preg_replace( '/\n|\z/', '</del>$0', $item, 1 );
				}
			}

			// Heinous sprintf
			$out[] = sprintf(
				$format,
				$object_id,
				$item_id,
				checked( $checked, true, false ),
				$disabled,
				$item,
				$checked_in_post_state,
				$checked ? 'X' : 'O', // uppercase to not cause infinite loop in recursion @see ::unparse_list()
				false === strpos( $item, "\n" ) ? '' : "\n",
				str_repeat( ' ', $indent ),
				$task_meta
			);
		}

		$text = join( "\n", $out ) . "\n";

		if ( 'edit' == $context ) {
			return $this->unparse_list( $text );
		}

		return $this->parse_list( $text, true );
	}

	/**
	 * Handles form submission (AJAX or traditional)
	 */
	function submit() {
		$id = (int) $_POST['id'];

		$is_ajax = isset( $_POST['ajax'] ) && $_POST['ajax'];

		if ( $is_ajax ) {
			check_ajax_referer( "p3-task-list_$id", "_p3_task_list_nonce_$id" );
		} else {
			check_admin_referer( "p3-task-list_$id", "_p3_task_list_nonce_$id" );
		}

		foreach ( $_POST['p3_task_ids'] as $object_id => $tasks ) {
			foreach ( $tasks as $task_id => $checked_in_post_state ) {
				$checked_now = isset( $_POST['p3_task'][$object_id][$task_id] ) && $_POST['p3_task'][$object_id][$task_id];
				$checked_in_post = 'x' == $checked_in_post_state;
				$checked_in_meta = $this->get_item_data( $task_id, $object_id );

				if ( $checked_in_meta ) {
					list( $checked ) = $checked_in_meta;
				} else {
					$checked = $checked_in_post;
				}

				if ( $checked_now == $checked ) {
					continue;
				}

				if ( $checked_now ) {
					$this->put_item_data( $task_id, true, $object_id );
				} else {
					if ( $checked_in_post ) {
						$this->put_item_data( $task_id, false, $object_id );
					} else {
						$this->delete_item_data( $task_id, $object_id );
					}
				}
			}
		}

		// @todo send back new list item content with DEL tag, @mention, etc.
		if ( $is_ajax ) {
			die( '1' );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Renormalizes (meta) checked state to ASCII x's and o's
	 *
	 * @param string $text
	 * @return string
	 */
	function unparse_list( $text ) {
		$text = preg_replace( '/(\r\n|\r|\n)/', "\n", $text );
		$text = preg_replace_callback( $this->task_list_regex_to_unparse(), array( $this, '_fix_task_list_callback' ), $text );
		return preg_replace_callback( '/^[ ]*[XO]/m', array( $this, '_strtolower' ), $text );
	}

	function _strtolower( $matches ) {
		return strtolower( $matches[0] );
	}

	function _fix_task_list_callback( $matches ) {
		$indent = strlen( $matches[1] );
		return $this->process_task_list_items( $matches[0], $indent, 'edit' );
	}
}
