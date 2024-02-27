<?php
/**
 * Class Sensei_Tour_Test
 *
 * @package sensei
 */

use Sensei\Admin\Tour\Sensei_Tour;

class Sensei_Tour_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Test instance.
	 *
	 * @var Sensei_Tour
	 */
	protected $instance;

	/**
	 * Test factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Setup the test instance.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory  = new Sensei_Factory();
		$this->instance = Sensei_Tour::instance();
	}

	public function tearDown(): void {
		parent::tearDown();

		wp_dequeue_script( 'sensei-course-tour' );
		wp_dequeue_script( 'sensei-lesson-tour' );
		wp_dequeue_style( 'sensei-tour-styles' );
	}

	public function testInit_WhenCalled_EnqueuesTheProperFunction() {
		/* Act */
		$this->instance->init();

		/* Assert */
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $this->instance, 'enqueue_admin_scripts' ] ) );
	}

	public function testEnqueueAdminScripts_WhenPostTypeIsNotLessonOrCourse_DoesNotEnqueueTheirScriptsAndStyles() {
		global $post;
		$this->login_as_admin();
		$post = $this->factory->post->create_and_get();

		$this->instance->enqueue_admin_scripts( 'post-new.php' );

		$this->assertFalse( wp_script_is( 'sensei-lesson-tour' ) );
		$this->assertFalse( wp_script_is( 'sensei-course-tour' ) );
		$this->assertFalse( wp_style_is( 'sensei-tour-styles' ) );
	}

	public function testEnqueueAdminScripts_WhenPostTypeIsCourse_EnqueuesCourseScriptsAndStyle() {
		/* Arrange */
		global $post;
		$this->login_as_admin();
		$post = $this->factory->course->create_and_get();

		/* Act */
		$this->instance->enqueue_admin_scripts( 'post-new.php' );

		/* Assert */
		$this->assertTrue( wp_script_is( 'sensei-course-tour' ) );
		$this->assertFalse( wp_script_is( 'sensei-lesson-tour' ) );
		$this->assertTrue( wp_style_is( 'sensei-tour-styles' ) );
	}

	public function testEnqueueAdminScripts_WhenPostTypeIsLesson_EnqueuesLessonScriptsAndStyles() {
		/* Arrange */
		global $post;
		$this->login_as_admin();
		$post = $this->factory->lesson->create_and_get();

		/* Act */
		$this->instance->enqueue_admin_scripts( 'post-new.php' );

		/* Assert */
		$this->assertTrue( wp_script_is( 'sensei-lesson-tour' ) );
		$this->assertFalse( wp_script_is( 'sensei-course-tour' ) );
		$this->assertTrue( wp_style_is( 'sensei-tour-styles' ) );
	}

	public function testEnqueueAdminScripts_WhenPostTypeIsLessonAndEditPage_EnqueuesLessonScriptsAndStyles() {
		/* Arrange */
		global $post;
		$this->login_as_admin();
		$post = $this->factory->lesson->create_and_get();

		/* Act */
		$this->instance->enqueue_admin_scripts( 'post.php' );

		/* Assert */
		$this->assertTrue( wp_script_is( 'sensei-lesson-tour' ) );
		$this->assertFalse( wp_script_is( 'sensei-course-tour' ) );
		$this->assertTrue( wp_style_is( 'sensei-tour-styles' ) );
	}

	public function testEnqueueAdminScripts_WhenPostTypeCorrectButPageDifferent_DoesNotEnqueueTheirScriptsAndStyles() {
		global $post;
		$this->login_as_admin();
		$post = $this->factory->course->create_and_get();

		$this->instance->enqueue_admin_scripts( 'edit.php' );

		$this->assertFalse( wp_script_is( 'sensei-lesson-tour' ) );
		$this->assertFalse( wp_script_is( 'sensei-course-tour' ) );
		$this->assertFalse( wp_style_is( 'sensei-tour-styles' ) );
	}

	public function testEnqueueAdminScripts_WhenTourLoadersModifiedUsingHook_UsesTheModifiedTourScripts() {
		/* Arrange */
		global $post;
		$this->login_as_admin();
		$post = $this->factory->course->create_and_get();

		/* Act */
		add_filter(
			'sensei_tour_loaders',
			function () {
				$modified_scripts['modified-course-tour'] = [
					'path' => 'modified-course-tour.js',
				];
				return $modified_scripts;
			}
		);

		$this->instance->enqueue_admin_scripts( 'post-new.php' );

		/* Assert */
		$this->assertTrue( wp_script_is( 'modified-course-tour' ) );
		$this->assertFalse( wp_script_is( 'sensei-course-tour' ) );
		$this->assertTrue( wp_style_is( 'sensei-tour-styles' ) );
	}

	public function testSetTourCompletionStatus_WhenCalled_SetsNewMetaProperly() {
		/* Arrange */
		$this->login_as_admin();
		$user_id = get_current_user_id();
		$before  = get_user_meta( $user_id, 'sensei_tours', true );

		/* Act */
		$this->instance->set_tour_completion_status( 'test-tour-id', true, $user_id );

		/* Assert */
		$this->assertNotEquals( $before, get_user_meta( $user_id, 'sensei_tours', true ) );
		$this->assertTrue( get_user_meta( $user_id, 'sensei_tours', true )['test-tour-id'] );
	}
}
