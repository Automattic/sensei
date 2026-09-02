<?php
/**
 * Tests for Sensei_Blocks class.
 */
class Sensei_Blocks_Test extends WP_UnitTestCase {

	/**
	 * Test that the anchor href is set to the given URL.
	 */
	public function testUpdateButtonBlockUrl_UrlGiven_SetsAnchorHref() {
		$block_content = '<div class="wp-block-button view-certificate"><a class="wp-block-button__link">View Certificate</a></div>';
		$block         = array(
			'blockName' => 'core/button',
			'attrs'     => array( 'className' => 'view-certificate' ),
		);

		$actual = Sensei_Blocks::update_button_block_url( $block_content, $block, 'view-certificate', 'https://example.com/certificate' );

		$this->assertStringContainsString( 'href="https://example.com/certificate"', $actual );
	}
}
