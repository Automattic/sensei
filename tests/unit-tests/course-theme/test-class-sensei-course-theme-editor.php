<?php
/**
 * This file contains the Sensei_Course_Theme_Editor_Test class.
 *
 * @package sensei
 *
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Test class.
 *
 * @covers Sensei_Course_Theme_Editor
 * @group course-theme
 */
class Sensei_Course_Theme_Editor_Test extends WP_UnitTestCase {
	/**
	 * Setup method. Run first on every test execution.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$_SERVER['REQUEST_URI'] = '';
	}

	/**
	 * @dataProvider providerInit_WhenCalled_AddsHooks
	 */
	public function testInit_WhenCalled_AddsHooks( $hook_name, $method_name, $priority ) {
		/* Act. */
		$this->resetCourseThemeEditorInstance();

		Sensei_Course_Theme_Editor::instance()->init();

		/* Assert */
		$this->assertSame(
			$priority,
			has_action(
				$hook_name,
				[ Sensei_Course_Theme_Editor::instance(), $method_name ]
			)
		);
	}

	public function providerInit_WhenCalled_AddsHooks() {
		return [
			[ 'setup_theme', 'override_site_editor_theme_for_non_block_themes', 1 ],
			[ 'setup_theme', 'maybe_add_site_editor_hooks', 1 ],
			[ 'setup_theme', 'maybe_override_lesson_theme', 1 ],
			[ 'rest_api_init', 'maybe_add_site_editor_hooks', 10 ],
			[ 'enqueue_block_editor_assets', 'enqueue_site_editor_assets', 10 ],
			[ 'admin_menu', 'add_admin_menu_site_editor_item', 20 ],
		];
	}

	public function testOverrideSiteEditorThemeForNonBlockThemes_WhenCalledForNonBlockTheme_OverridesTheme() {
		/* Arrange. */
		$this->resetCourseThemeEditorInstance();

		$course_theme_mock = $this->createMock( Sensei_Course_Theme::class );

		$_SERVER['REQUEST_URI'] = '/wp-admin/site-editor.php';

		/* Assert. */
		$course_theme_mock
			->expects( $this->once() )
			->method( 'override_theme' );

		/* Act. */
		Sensei_Course_Theme_Editor::instance( $course_theme_mock )->override_site_editor_theme_for_non_block_themes();
	}

	public function testOverrideSiteEditorThemeForNonBlockThemes_WhenCalledForBlockTheme_DoesNothing() {
		/* Arrange. */
		$this->resetCourseThemeEditorInstance();

		$course_theme_mock = $this->createMock( Sensei_Course_Theme::class );

		$_SERVER['REQUEST_URI'] = '/wp-admin/site-editor.php';

		switch_theme( 'block-theme' );

		/* Assert. */
		$course_theme_mock
			->expects( $this->never() )
			->method( 'override_theme' );

		/* Act. */
		Sensei_Course_Theme_Editor::instance( $course_theme_mock )->override_site_editor_theme_for_non_block_themes();
	}

	public function testOverrideSiteEditorThemeForNonBlockThemes_WhenCalledNoInTheSiteEditor_DoesNothing() {
		/* Arrange. */
		$this->resetCourseThemeEditorInstance();

		$course_theme_mock = $this->createMock( Sensei_Course_Theme::class );

		/* Assert. */
		$course_theme_mock
			->expects( $this->never() )
			->method( 'override_theme' );

		/* Act. */
		Sensei_Course_Theme_Editor::instance( $course_theme_mock )->override_site_editor_theme_for_non_block_themes();
	}

	private function resetCourseThemeEditorInstance() {
		$instance = new ReflectionProperty( Sensei_Course_Theme_Editor::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null );
	}
}

