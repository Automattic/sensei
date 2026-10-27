<?php
/**
 * Tests for Sensei_Block_Patterns.
 *
 * @covers Sensei_Block_Patterns
 */
class Sensei_Block_Patterns_Test extends WP_UnitTestCase {

	/**
	 * The default course pattern can be read outside the editor, where patterns are not registered.
	 *
	 * @covers Sensei_Block_Patterns::get_pattern_content
	 */
	public function testGetPatternContent_CourseDefaultGiven_ReturnsCourseOutlineMarkup() {
		$content = Sensei_Block_Patterns::get_pattern_content( 'course', 'course-default' );

		$this->assertIsString( $content, 'The pattern content should be returned.' );
		$this->assertStringContainsString( '<!-- wp:sensei-lms/course-outline /-->', $content, 'The pattern should contain the course outline block.' );
		$this->assertStringContainsString( 'sensei-content-description', $content, 'The pattern should contain the description placeholder.' );
	}

	/**
	 * Only Sensei's own pattern names resolve to a file.
	 *
	 * @covers Sensei_Block_Patterns::get_pattern_content
	 * @dataProvider providerUnknownPatterns
	 *
	 * @param string $post_type Post type.
	 * @param string $name      Pattern name.
	 */
	public function testGetPatternContent_UnknownPatternGiven_ReturnsNull( string $post_type, string $name ) {
		$content = Sensei_Block_Patterns::get_pattern_content( $post_type, $name );

		$this->assertNull( $content );
	}

	/**
	 * Pattern names that do not exist.
	 *
	 * @return array[]
	 */
	public function providerUnknownPatterns(): array {
		return array(
			'unknown name'            => array( 'course', 'does-not-exist' ),
			'unknown post type'       => array( 'quiz', 'course-default' ),
			'name of other post type' => array( 'course', 'landing-page' ),
			'path traversal'          => array( 'course', '../course/course-default' ),
		);
	}

	/**
	 * A paragraph whose className contains the placeholder gets the given content.
	 *
	 * @covers Sensei_Block_Patterns::replace_placeholders
	 */
	public function testReplacePlaceholders_ClassNameMatched_FillsParagraph() {
		$content = "<!-- wp:paragraph {\"placeholder\":\"Describe it\",\"className\":\"sensei-content-description\"} -->\n<p class=\"sensei-content-description\"></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:sensei-lms/course-outline /-->";

		$actual = Sensei_Block_Patterns::replace_placeholders( $content, array( 'sensei-content-description' => 'Root and fifth.' ) );

		$this->assertStringContainsString( '<p class="sensei-content-description">Root and fifth.</p>', $actual, 'The placeholder paragraph should be filled.' );
		$this->assertStringContainsString( '"className":"sensei-content-description"', $actual, 'The block attributes should be kept.' );
		$this->assertStringContainsString( '<!-- wp:sensei-lms/course-outline /-->', $actual, 'Other blocks should be kept.' );
	}

	/**
	 * A styled paragraph, as in the life-coach pattern, keeps the markup its attributes describe.
	 *
	 * @covers Sensei_Block_Patterns::replace_placeholders
	 */
	public function testReplacePlaceholders_StyledParagraphMatched_KeepsWrapperMarkup() {
		$content = "<!-- wp:paragraph {\"style\":{\"typography\":{\"lineHeight\":1.2,\"fontSize\":\"17px\"}},\"textColor\":\"foreground\",\"className\":\"sensei-content-description\"} -->\n<p class=\"has-foreground-color has-text-color sensei-content-description\" style=\"font-size:17px;line-height:1.2\">Old text</p>\n<!-- /wp:paragraph -->";

		$actual = Sensei_Block_Patterns::replace_placeholders( $content, array( 'sensei-content-description' => 'Root and fifth.' ) );

		$this->assertStringContainsString( '<p class="has-foreground-color has-text-color sensei-content-description" style="font-size:17px;line-height:1.2">Root and fifth.</p>', $actual, 'The styled wrapper should be kept around the new content.' );
		$this->assertStringNotContainsString( 'Old text', $actual, 'The old content should be replaced.' );
		$this->assertStringContainsString( '"textColor":"foreground"', $actual, 'The block attributes should be kept.' );
	}

	/**
	 * Placeholders nested inside container blocks are filled too.
	 *
	 * @covers Sensei_Block_Patterns::replace_placeholders
	 */
	public function testReplacePlaceholders_NestedBlockMatched_FillsInnerBlock() {
		$content = "<!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:paragraph {\"className\":\"sensei-content-title\"} -->\n<p class=\"sensei-content-title\"></p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->";

		$actual = Sensei_Block_Patterns::replace_placeholders( $content, array( 'sensei-content-title' => 'Power chords' ) );

		$this->assertStringContainsString( '<p class="sensei-content-title">Power chords</p>', $actual, 'The nested placeholder should be filled.' );
		$this->assertStringContainsString( '<!-- wp:group -->', $actual, 'The container block should be kept.' );
	}

	/**
	 * Blocks without a matching className are left untouched.
	 *
	 * @covers Sensei_Block_Patterns::replace_placeholders
	 */
	public function testReplacePlaceholders_NoClassNameMatched_LeavesContentUnchanged() {
		$content = "<!-- wp:paragraph {\"className\":\"other\"} -->\n<p class=\"other\">Keep me</p>\n<!-- /wp:paragraph -->";

		$actual = Sensei_Block_Patterns::replace_placeholders( $content, array( 'sensei-content-description' => 'Root and fifth.' ) );

		$this->assertSame( $content, $actual );
	}
}
