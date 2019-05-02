<?php

class Sensei_Template_Functions_Test extends WP_UnitTestCase {
	public function setup() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * @covers sensei_get_modules_and_lessons
	 */
	public function testGetModulesAndLessons() {
		$course_id           = $this->factory->get_course_with_modules();
		$modules_and_lessons = sensei_get_modules_and_lessons( $course_id );

		// 9 module lessons + 1 other lesson + 1 module = 11
		$this->assertEquals( 11, count( $modules_and_lessons ) );
	}

	/**
	 * @covers sensei_get_modules_and_lessons
	 */
	public function testGetModulesAndLessonsEmptyModule() {
		// Create a course.
		$course_id = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		// Create some modules.
		$modules   = array();
		$modules[] = $this->factory->term->create_and_get( array( 'taxonomy' => 'module' ) );
		$modules[] = $this->factory->term->create_and_get( array( 'taxonomy' => 'module' ) );

		// Add modules to course.
		foreach ( $modules as $module ) {
			wp_set_object_terms( $course_id, array( $module->term_id ), 'module', true );
		}

		// Create some lessons.
		$lesson_ids = $this->factory->post->create_many(
			3,
			array(
				'post_status' => 'publish',
				'post_type'   => 'lesson',
			)
		);

		foreach ( $lesson_ids as $lesson_id ) {
			// Add lesson to course.
			add_post_meta( $lesson_id, '_lesson_course', $course_id );

			// Add lesson to first module.
			wp_set_object_terms( $lesson_id, $modules[0]->term_id, 'module' );
			add_post_meta( $lesson_id, '_order_module_' . $modules[0]->term_id, 0 );
		}

		$modules_and_lessons = sensei_get_modules_and_lessons( $course_id );

		// 3 module lessons + 1 non-empty module = 4
		$this->assertEquals( 4, count( $modules_and_lessons ) );
	}

	/**
	 * @covers sensei_get_other_lessons
	 */
	public function testGetOtherLessons() {
		$course_id        = $this->factory->get_course_with_modules();
		$lesson_ids       = $this->factory->get_lessons();
		$other_lesson_ids = $this->factory->get_other_lessons();
		$other_lessons    = sensei_get_other_lessons( $course_id, array_diff( $lesson_ids, $other_lesson_ids ) );

		$this->assertEquals( 1, count( $other_lessons ), 'Other lessons count' );
		$this->assertEquals( $other_lesson_ids[0], $other_lessons[0]->ID, 'Other lesson ID' );
	}

	/**
	 * @covers sensei_get_navigation_url
	 */
	public function testGetNavigationModuleURL() {
		$course_id = $this->factory->get_course_with_modules();
		$modules   = wp_get_post_terms( $course_id, 'module' );
		$module    = $modules[0];
		$url       = sensei_get_navigation_url( $course_id, $module );

		$this->assertEquals( get_term_link( $module, 'module' ) . '&course_id=' . $course_id, $url );
	}

	/**
	 * @covers sensei_get_navigation_url
	 */
	public function testGetNavigationLessonURL() {
		$course_id = $this->factory->get_course_with_modules();
		$lesson_id = $this->factory->get_random_lesson_id();
		$lesson    = get_post( $lesson_id );
		$url       = sensei_get_navigation_url( $course_id, $lesson );

		$this->assertEquals( get_permalink( $lesson->ID ), $url );
	}

	/**
	 * @covers sensei_get_navigation_link_text
	 */
	public function testGetNavigationModuleText() {
		$course_id = $this->factory->get_course_with_modules();
		$modules   = wp_get_post_terms( $course_id, 'module' );
		$module    = $modules[0];
		$text      = sensei_get_navigation_link_text( $module );

		$this->assertEquals( $module->name, $text );
	}

	/**
	 * @covers sensei_get_navigation_link_text
	 */
	public function testGetNavigationLessonText() {
		$course_id = $this->factory->get_course_with_modules();
		$lesson_id = $this->factory->get_random_lesson_id();
		$lesson    = get_post( $lesson_id );
		$text      = sensei_get_navigation_link_text( $lesson );

		$this->assertEquals( $lesson->post_title, $text );
	}

	/**
	 * @covers sensei_get_prev_next_lessons
	 */
	public function testGetPrevNextLessons() {
		$course_id = $this->factory->get_course_with_modules();
		$lessons   = $this->factory->get_lessons();
		$modules   = wp_get_post_terms( $course_id, 'module' );
		$previous  = $modules[0];
		$current   = get_post( $lessons[0] );
		$next      = get_post( $lessons[1] );

		add_filter( 'sensei_do_link_to_module', '__return_true' );
		$nav_links = sensei_get_prev_next_lessons( $current->ID );
		remove_filter( 'sensei_do_link_to_module', '__return_true' );

		// Previous - Module
		$this->assertArrayHasKey( 'previous', $nav_links, 'Previous - Key' );
		$this->assertArrayHasKey( 'url', $nav_links['previous'], 'Previous - URL key' );
		$this->assertArrayHasKey( 'name', $nav_links['previous'], 'Previous - Name key' );
		$this->assertEquals( get_term_link( $previous, 'module' ) . '&course_id=' . $course_id, $nav_links['previous']['url'], 'Previous - URL' );
		$this->assertEquals( $previous->name, $nav_links['previous']['name'], 'Previous - Name' );

		// Next - Lesson
		$this->assertArrayHasKey( 'next', $nav_links, 'Next - Key' );
		$this->assertArrayHasKey( 'url', $nav_links['next'], 'Next - URL key' );
		$this->assertArrayHasKey( 'name', $nav_links['next'], 'Next - Name key' );
		$this->assertEquals( get_permalink( $next->ID ), $nav_links['next']['url'], 'Next - URL' );
		$this->assertEquals( $next->post_title, $nav_links['next']['name'], 'Next - Name' );
	}

	/**
	 * @covers sensei_get_prev_next_lessons
	 */
	public function testGetPrevNextLessonsNoModules() {
		$course_id = $this->factory->get_course_with_modules();
		$lessons   = $this->factory->get_lessons();
		$current   = get_post( $lessons[0] );
		$next      = get_post( $lessons[1] );

		add_filter( 'sensei_do_link_to_module', '__return_false' );
		$nav_links = sensei_get_prev_next_lessons( $current->ID );
		remove_filter( 'sensei_do_link_to_module', '__return_false' );

		// Previous - Module
		$this->assertArrayNotHasKey( 'previous', $nav_links, 'Previous - Key' );

		// Next - Lesson
		$this->assertArrayHasKey( 'next', $nav_links, 'Next - Key' );
		$this->assertArrayHasKey( 'url', $nav_links['next'], 'Next - URL key' );
		$this->assertArrayHasKey( 'name', $nav_links['next'], 'Next - Name key' );
		$this->assertEquals( get_permalink( $next->ID ), $nav_links['next']['url'], 'Next - URL' );
		$this->assertEquals( $next->post_title, $nav_links['next']['name'], 'Next - Name' );
	}

	/**
	 * @covers sensei_get_prev_next_lessons
	 */
	public function testGetPrevNextLessonsNoPrevious() {
		global $wp_query;

		$course_id = $this->factory->get_course_with_modules();
		$lessons   = $this->factory->get_lessons();
		$modules   = wp_get_post_terms( $course_id, 'module' );
		$current   = $modules[0];
		$next      = get_post( $lessons[0] );

		// Set test up so that it thinks we're on the module page.
		$wp_query->is_tax         = true;
		$wp_query->queried_object = $current;
		$nav_links                = sensei_get_prev_next_lessons( $next->ID );

		// Previous
		$this->assertArrayNotHasKey( 'previous', $nav_links, 'Previous - No key' );

		// Next
		$this->assertArrayHasKey( 'next', $nav_links, 'Next - Key' );
		$this->assertArrayHasKey( 'url', $nav_links['next'], 'Next - URL key' );
		$this->assertArrayHasKey( 'name', $nav_links['next'], 'Next - Name key' );
		$this->assertEquals( get_permalink( $next->ID ), $nav_links['next']['url'], 'Next - URL' );
		$this->assertEquals( $next->post_title, $nav_links['next']['name'], 'Next - Name' );
	}

	/**
	 * @covers sensei_get_prev_next_lessons
	 */
	public function testGetPrevNextLessonsNoNext() {
		$lessons   = $this->factory->get_lessons();
		$previous  = get_post( $lessons[ count( $lessons ) - 2 ] );
		$current   = get_post( $lessons[ count( $lessons ) - 1 ] );
		$nav_links = sensei_get_prev_next_lessons( $current->ID );

		// Previous
		$this->assertArrayHasKey( 'previous', $nav_links, 'Previous - Key' );
		$this->assertArrayHasKey( 'url', $nav_links['previous'], 'Previous - URL key' );
		$this->assertArrayHasKey( 'name', $nav_links['previous'], 'Previous - Name key' );
		$this->assertEquals( get_permalink( $previous->ID ), $nav_links['previous']['url'], 'Previous - URL' );
		$this->assertEquals( $previous->post_title, $nav_links['previous']['name'], 'Previous - Name' );

		// Next
		$this->assertArrayNotHasKey( 'next', $nav_links, 'Next - No key' );
	}
}
