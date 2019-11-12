<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles Sensei's blocks.
 */
final class Sensei_Blocks {
	/**
	 * Stores singleton of self.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetch singleton of class.
	 *
	 * @return Sensei_Blocks
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes blocks.
	 */
	public function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		add_action( 'init', [ $this, 'register_blocks' ], 11 );
	}

	/**
	 * Registers all the blocks.
	 */
	public function register_blocks() {
		// Silence is golden.
	}
}
