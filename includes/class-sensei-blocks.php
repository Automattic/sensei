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
		$this->register_messages();
	}

	/**
	 * Registers the messages block.
	 */
	private function register_messages() {
		$asset_info_editor = include Sensei()->plugin_path . 'assets/block-editor/build/messages-block.asset.php';
		wp_register_script(
			'sensei-messages-block',
			Sensei()->plugin_url . 'assets/block-editor/build/messages-block.js',
			$asset_info_editor['dependencies'],
			$asset_info_editor['version'],
			true
		);

		$asset_info_frontend = include Sensei()->plugin_path . 'assets/block-editor/build/messages-block-frontend.asset.php';
		wp_register_script(
			'sensei-messages-block-frontend',
			Sensei()->plugin_url . 'assets/block-editor/build/messages-block-frontend.js',
			$asset_info_frontend['dependencies'],
			$asset_info_frontend['version'],
			true
		);

		wp_register_style(
			'sensei-messages-block',
			Sensei()->plugin_url . 'assets/block-editor/build/messages-block.css',
			[],
			Sensei()->version
		);

		register_block_type(
			'sensei-lms/messages-block',
			[
				'editor_script' => 'sensei-messages-block',
				'script'        => 'sensei-messages-block-frontend',
				'editor_style'  => 'sensei-messages-block',
				'style'         => 'sensei-messages-block',
			]
		);
	}
}
