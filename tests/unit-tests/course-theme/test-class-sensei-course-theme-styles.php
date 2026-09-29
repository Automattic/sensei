<?php
/**
 * This file contains the Sensei_Course_Theme_Styles_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for the Sensei_Course_Theme_Styles class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Styles_Test extends WP_UnitTestCase {

	/**
	 * Tests that a colour value cannot introduce a second style attribute.
	 */
	public function testApplyBlockSupport_BackreferenceInColorGiven_KeepsASingleStyleAttribute() {
		$block         = array(
			'attrs' => array(
				'style' => array(
					'color' => array(
						'background' => '$0',
					),
				),
			),
		);
		$block_content = '<p class="has-background" style="padding-top:1px">x</p>';

		$content = Sensei_Course_Theme_Styles::apply_block_support( $block_content, $block );

		$this->assertSame( 1, substr_count( $content, 'style="' ), 'The rendered block should contain exactly one style attribute.' );
	}

	/**
	 * Tests that a colour is prepended to an existing style attribute.
	 */
	public function testApplyBlockSupport_ElementWithStyleGiven_PrependsTheColor() {
		$block         = array(
			'attrs' => array(
				'style' => array(
					'color' => array(
						'background' => '#ff0000',
					),
				),
			),
		);
		$block_content = '<p class="has-background" style="padding-top:1px">x</p>';

		$content = Sensei_Course_Theme_Styles::apply_block_support( $block_content, $block );

		$this->assertStringContainsString( 'style="--sensei-background-color: #ff0000;', $content );
	}

	/**
	 * Tests that a colour is added when the element has no style attribute.
	 */
	public function testApplyBlockSupport_ElementWithoutStyleGiven_AddsAStyleAttribute() {
		$block         = array(
			'attrs' => array(
				'style' => array(
					'color' => array(
						'background' => '#00ff00',
					),
				),
			),
		);
		$block_content = '<p class="has-background">x</p>';

		$content = Sensei_Course_Theme_Styles::apply_block_support( $block_content, $block );

		$this->assertStringContainsString( ' style="--sensei-background-color: #00ff00;', $content );
	}
}
