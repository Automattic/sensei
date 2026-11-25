<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Block_Helpers class.
 */
class Sensei_Block_Helpers_Test extends WP_UnitTestCase {

	public function testRenderStyleAttributes_ValueWithAnotherDeclarationGiven_DropsTheDeclaration() {
		/* Arrange. */
		$css = array(
			'inline_styles' => array(
				'color: #123456;',
				'border-color: red; background: blue;',
			),
		);

		/* Act. */
		$result = Sensei_Block_Helpers::render_style_attributes( 'block', $css );

		/* Assert. */
		$this->assertSame( 'class="block" style="color: #123456"', $result );
	}
}
