<?php
/**
 * Tests for Sensei_Abilities.
 *
 * @covers Sensei_Abilities
 */
class Sensei_Abilities_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function testInit_Always_RegistersCategoryHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_categories_init', array( Sensei_Abilities::class, 'register_category' ) )
		);
	}

	public function testInit_Always_RegistersAbilitiesHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_init', array( Sensei_Abilities::class, 'register_abilities' ) )
		);
	}

	public function testGetCourses_WhenCalled_ReturnsCourses() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create( array( 'post_title' => 'Alpha' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );
		$this->assertNotNull( $ability );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result );
		$course_ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $course_id, $course_ids );

		$factory->tearDown();
	}

	public function testGetCourses_WithIdsFilter_ReturnsOnlyMatchingCourses() {
		$factory  = new Sensei_Factory();
		$course_a = $factory->course->create();
		$course_b = $factory->course->create();

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array( 'ids' => array( $course_a ) ) );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $course_a, $course_ids );
		$this->assertNotContains( $course_b, $course_ids );

		$factory->tearDown();
	}

	public function testGetCourses_WhenUserLacksCapability_ReturnsFalseFromPermission() {
		wp_set_current_user( 0 );
		$ability = wp_get_ability( 'sensei/get-courses' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetCourses_WhenCallerIsTeacher_ReturnsOnlyOwnCourses() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_course   = $factory->course->create( array( 'post_author' => $teacher_a ) );
		$other_course = $factory->course->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array( 'teachers' => array( $teacher_b ) ) );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $own_course, $course_ids );
		$this->assertNotContains( $other_course, $course_ids );

		$factory->tearDown();
	}

	public function testGetCourses_WithCategoriesFilter_NarrowsByTaxonomy() {
		$factory  = new Sensei_Factory();
		$course_a = $factory->course->create();
		$course_b = $factory->course->create();

		$term = wp_insert_term( 'Yoga', 'course-category', array( 'slug' => 'yoga' ) );
		wp_set_object_terms( $course_a, array( $term['term_id'] ), 'course-category' );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array( 'categories' => array( 'yoga' ) ) );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $course_a, $course_ids );
		$this->assertNotContains( $course_b, $course_ids );

		$factory->tearDown();
	}
}
