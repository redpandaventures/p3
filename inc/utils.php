<?php
/**
 * Utility functions.
 *
 * @package P3
 * @since unknown
 */

function p3_maybe_define( $constant, $value, $filter = '' ) {
	if ( defined( $constant ) )
		return;

	if ( !empty( $filter ) )
		$value = apply_filters( $filter, $value );

	define( $constant, $value );
}
