<?php
/**
 * List Creator.
 *
 * Loosely based on a version of the doLists and related functions from the Markdown PHP Library
 * @see http://michelf.com/projects/php-markdown/
 *
 * Parses unordered and ordered lists on save (storing the HTML in the database).
 * If you switch themes, the list markup is preserved.
 *
 * Parses task lists on display: if you switch themes, you no
 * longer have a handler for checkbox state toggles, so just
 * show ASCII x's and o's.
 *
 * The checked state for each task list item is initially stored
 * as an x (checked) or o (unchecked) in the post/comment text.
 *
 * When a task list item is updated from the blog (via the HTML form),
 * the checked state is stored in post/comment meta.
 *
 * When the post/comment is edited, the checked states stored in
 * post/comment meta in copied to the post/comment text
 * (updating x's and o's) and deleted.
 *
 * This way, the order of the task list items can be
 * changed while editing the post/comment without breaking
 * the data stored in post/comment meta.
 *
 * @package P3
 */

/**
 * Parses lists for posts.
 *
 * @package P3
 */
class P3_Post_List_Creator extends P3_List_Creator {
	var $form_action_name = 'p3-post-task-list';

	public function __construct() {
		parent::P3_List_Creator();

		// Parse everything on display
		add_filter( 'the_content', array( $this, 'reset_task_list_counter' ), 0 );
		add_filter( 'the_content', array( $this, 'reset_task_list_item_id' ), 0 );
		add_filter( 'the_content', array( $this, 'parse_list' ), 1 );

		// Renormalize task list meta into ASCII x's and o's
		add_filter( 'edit_post_content', array( $this, 'edit_post_content' ), 10, 2 );
		add_action( 'post_updated', array( $this, 'delete_all_item_data' ) );

		// Parse UL/OL on save
		add_filter( 'content_save_pre', array( $this, 'parse_list' ), 11 );
	}

	/**
	 * Returns ID of current/given post
	 *
	 * @param int $post_id (optional) post ID
	 * @return int post ID
	 */
	function get_object_id( $post_id = 0 ) {
		$post = get_post( $post_id );
		return (int) $post->ID;
	}

	/**
	 * Determines if the current user has permission to edit the current/given post
	 *
	 * @param int $post_id (optional) post ID
	 * @return bool
	 */
	function current_user_can( $post_id = 0 ) {
		return current_user_can( 'edit_post', $this->get_object_id( $post_id ) );
	}

	/**
	 * Whether we should look for and parse task lists
	 *
	 * @return bool
	 */
	function parse_task_lists() {
		return 'content_save_pre' != current_filter();
	}

	/**
	 * Gets the (meta) checked state for the given task list item on the current/given post.
	 *
	 * @param int $task_id
	 * @param int $post_id (optional)
	 * @return array ( checked, checked_by_user_id, checked_timestamp )
	 */
	function get_item_data( $task_id, $post_id = 0 ) {
		$meta = get_post_meta( $this->get_object_id( $post_id ), "p3_task_{$task_id}", true );
		if ( !$meta ) {
			return array();
		}

		return explode( ':', $meta );
	}

	/**
	 * Sets the (meta) checked state for the given task list item on the current/given post
	 *
	 * @param int  $task_id
	 * @param bool $done
	 * @param int  $post_id (optional)
	 */
	function put_item_data( $task_id, $done = true, $post_id = 0 ) {
		update_post_meta( $this->get_object_id( $post_id ), "p3_task_{$task_id}", sprintf( '%d:%d:%s', $done, get_current_user_id(), time() ) );
	}

	/**
	 * Deletes the (meta) checked state for the given task list item on the current/given post.
	 * The x/o checked state stored in post_content is not changed
	 *
	 * @param int $task_id
	 * @param int $post_id (optional)
	 */
	function delete_item_data( $task_id, $post_id = 0 ) {
		delete_post_meta( $this->get_object_id( $post_id ), "p3_task_{$task_id}" );
	}

	/**
	 * Gets the post meta keys for each (meta) checked state for all task list items in the current/given post.
	 *
	 * @param int $post_id (optional)
	 * @return array
	 */
	function get_all_item_data( $post_id = 0 ) {
		$meta_keys = get_post_custom_keys( $this->get_object_id( $post_id ) );
		if ( !$meta_keys ) {
			return array();
		}

		$task_id_meta_keys = preg_grep( '/p3_task_\d/', $meta_keys );
		if ( !$task_id_meta_keys ) {
			return array();
		}

		return $task_id_meta_keys;
	}

	/**
	 * Deletes all (meta) checked states for the current/given post.
	 *
	 * @param int $post_id (optional)
	 */
	function delete_all_item_data( $post_id = 0 ) {
		$post_id = $this->get_object_id( $post_id );

		$task_id_meta_keys = $this->get_all_item_data( $post_id );
		foreach ( $task_id_meta_keys as $task_id_meta_key ) {
			delete_post_meta( $post_id, $task_id_meta_key );
		}
	}

	/**
	 * Wrapper for renormalizing task list meta into ASCII x's and o's during post edit.
	 * Copies the post meta checked state to x's and o's in post_content
	 */
	function edit_post_content( $text, $post_id ) {
		$task_id_meta_keys = $this->get_all_item_data( $post_id );
		if ( !$task_id_meta_keys ) {
			return $text;
		}

		$old_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$GLOBALS['post'] = get_post( $post_id );
		$text = $this->unparse_list( $text );
		$GLOBALS['post'] = $old_post;
		return $text;
	}
}

/**
 * Parses lists for comments.
 *
 * @package P3
 */
class P3_Comment_List_Creator extends P3_List_Creator {
	var $form_action_name = 'p3-comment-task-list';

	function P3_Comment_List_Creator() {
		parent::P3_List_Creator();

		// Parse everything on display
		add_filter( 'comment_text', array( $this, 'reset_task_list_counter' ), 0 );
		add_filter( 'comment_text', array( $this, 'reset_task_list_item_id' ), 0 );
		add_filter( 'comment_text', array( $this, 'comment_text' ), 11, 2 );

		// Renormalize task list meta into ASCII x's and o's
		add_filter( 'p3_get_comment_content', array( $this, 'unparse_comment_list' ), 10, 2 );
		add_action( 'edit_comment', array( $this, 'delete_all_item_data' ) );

		// Parse UL/OL on save
		add_filter( 'pre_comment_content', array( $this, 'parse_list' ) );
	}

	/**
	 * Parses all lists on display
	 *
	 * Fires on 'comment_text'
	 *
	 * @param string $comment_text
	 * @param object $comment Comment row object
	 * @return string
	 */
	function comment_text( $comment_text, $comment = null ) {
		$old_comment = isset( $GLOBALS['comment'] ) ? $GLOBALS['comment'] : null;
		$GLOBALS['comment'] = $comment;
		$comment_text = $this->parse_list( $comment_text );
		$GLOBALS['comment'] = $old_comment;
		return $comment_text;
	}

	/**
	 * Returns ID of current/given comment
	 *
	 * @param int $comment_id (optional) comment ID
	 * @return int comment ID
	 */
	function get_object_id( $comment_id = 0 ) {
		$comment = get_comment( $comment_id );
		return is_object( $comment ) ? $comment->comment_ID : 0;
	}

	/**
	 * Determines if the current user has permission to edit the current/given comment
	 *
	 * @param int $comment_id (optional) comment ID
	 * @return bool
	 */
	function current_user_can( $comment_id = 0 ) {
		return current_user_can( 'edit_comment', $this->get_object_id( $comment_id ) );
	}

	/**
	 * Whether we should look for and parse task lists
	 *
	 * @return bool
	 */
	function parse_task_lists() {
		return 'pre_comment_content' != current_filter();
	}

	/**
	 * Gets the (meta) checked state for the given task list item on the current/given comment.
	 *
	 * @param int $task_id
	 * @param int $comment_id (optional)
	 * @return array ( checked, checked_by_user_id, checked_timestamp )
	 */
	function get_item_data( $task_id, $comment_id = 0 ) {
		$meta = get_comment_meta( $this->get_object_id( $comment_id ), "p3_task_{$task_id}", true );
		if ( !$meta ) {
			return array();
		}

		return explode( ':', $meta );
	}

	/**
	 * Sets the (meta) checked state for the given task list item on the current/given comment
	 *
	 * @param int  $task_id
	 * @param bool $done
	 * @param int  $comment_id (optional)
	 */
	function put_item_data( $task_id, $done = true, $comment_id = 0 ) {
		update_comment_meta( $this->get_object_id( $comment_id ), "p3_task_{$task_id}", sprintf( '%d:%d:%s', $done, get_current_user_id(), time() ) );
	}

	/**
	 * Deletes the (meta) checked state for the given task list item on the current/given comment.
	 * The x/o checked state stored in comment_content is not changed
	 *
	 * @param int $task_id
	 * @param int $comment_id (optional)
	 */
	function delete_item_data( $task_id, $comment_id = 0 ) {
		delete_comment_meta( $this->get_object_id( $comment_id ), "p3_task_{$task_id}" );
	}

	/**
	 * Gets the comment meta keys for each (meta) checked state for all task list items in the current/given comment.
	 *
	 * @param int $comment_id (optional)
	 * @return array
	 */
	function get_all_item_data( $comment_id = 0 ) {
		$comment_id = $this->get_object_id( $comment_id );
		$meta = get_metadata( 'comment', $comment_id );
		if ( !$meta ) {
			return array();
		}

		$meta_keys = array_keys( $meta );
		if ( !$meta_keys ) {
			return array();
		}

		$task_id_meta_keys = preg_grep( '/p3_task_\d/', $meta_keys );
		if ( !$task_id_meta_keys ) {
			return array();
		}

		return $task_id_meta_keys;
	}

	/**
	 * Deletes all (meta) checked states for the current/given comment.
	 *
	 * @param int $comment_id (optional)
	 */
	function delete_all_item_data( $comment_id = 0 ) {
		$comment_id = $this->get_object_id( $comment_id );

		$task_id_meta_keys = $this->get_all_item_data( $comment_id );
		foreach ( $task_id_meta_keys as $task_id_meta_key ) {
			delete_comment_meta( $comment_id, $task_id_meta_key );
		}
	}

	/**
	 * Wrapper for renormalizing task list meta into ASCII x's and o's during comment edit.
	 * Copies the comment meta checked state to x's and o's in comment_content
	 */
	function unparse_comment_list( $text, $comment_id ) {
		if ( !$this->get_all_item_data( $comment_id ) ) {
			return $text;
		}

		$old_comment = isset( $GLOBALS['comment'] ) ? $GLOBALS['comment'] : null;
		$GLOBALS['comment'] = get_comment( $comment_id );
		$text = $this->unparse_list( $text );
		$GLOBALS['comment'] = $old_comment;
		return $text;
	}
}
