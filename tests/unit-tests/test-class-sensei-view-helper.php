<?php

/**
 * Test Sensei_View_Helper class.
 *
 * @covers Sensei_View_Helper
 */
class Sensei_View_Helper_Test extends WP_UnitTestCase {

	/**
	 * Initial settings array.
	 *
	 * @var array
	 */
	private static $initial_settings;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_settings = Sensei()->settings->settings;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Sensei()->settings->settings = self::$initial_settings;
	}

	/**
	 * Test the format_question_points method.
	 *
	 * @param string $points
	 * @param string $settings
	 * @param string $expected
	 *
	 * @dataProvider providerFormatQuestionPoints_PointsGiven_ReturnsMatchingResult
	 */
	public function testFormatQuestionPoints_PointsGiven_ReturnsMatchingResult( string $points, array $settings, string $expected ) {
		/* Arrange */
		Sensei()->settings->settings = $settings;

		/* Act */
		$helper = new Sensei_View_Helper();
		$actual = $helper->format_question_points( $points );

		/* Assert */
		self::assertSame( $expected, $actual );
	}

	public function providerFormatQuestionPoints_PointsGiven_ReturnsMatchingResult(): array {
		return [
			'format: none'             => [
				'1',
				'settings' => [
					'quiz_question_points_format' => 'none',
				],
				'<span class="grade"></span>',
			],
			'format: number (default)' => [
				'1',
				[
					'quiz_question_points_format' => 'number',
				],
				'<span class="grade">1</span>',
			],
			'format: brackets'         => [
				'1',
				[
					'quiz_question_points_format' => 'brackets',
				],
				'<span class="grade">[1]</span>',
			],
			'format: text'             => [
				'1',
				[
					'quiz_question_points_format' => 'text',
				],
				'<span class="grade">Points: 1</span>',
			],
			'format: full'             => [
				'1',
				[
					'quiz_question_points_format' => 'full',
				],
				'<span class="grade">[Points: 1]</span>',
			],
		];
	}
}
