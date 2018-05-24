<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 *
 * An Exception to be thrown when a Renderer does not have all of the fields
 * it needs in order to complete the rendering.
 *
 * @author Automattic
 *
 * @since 1.11.0
 */
class Sensei_Renderer_Missing_Fields_Exception extends Exception {
	public function __construct( $fields ) {
		$message = sprintf(
			__( 'Missing fields: %s', 'woothemes-sensei' ),
			join( ', ', $fields )
		);
		parent::__construct( $message );
	}
}
