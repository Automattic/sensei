<?php
/**
 * This file contains the Sensei_Data_Port_Utilities_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Data_Port_Utilities class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Utilities_Test extends WP_UnitTestCase {


	public function testUserIsCreatedIfDoesNotExist() {
		$user_id = Sensei_Data_Port_Utilities::create_user( 'testuser', 'testemail@test.com' );

		$this->assertEquals( $user_id, get_user_by( 'login', 'testuser' )->ID );
	}

	/**
	 * Tests a simple term name path with one entry.
	 */
	public function testGetTermSingleCourseCategory() {
		$term_path     = [ 'Dinosaurs & Dogs' ];
		$taxonomy_name = 'course-category';

		$term = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path ), $taxonomy_name );
		$this->assertTermPathValid( $term_path, $term, $taxonomy_name );
	}

	/**
	 * Tests a simple term name path.
	 */
	public function testGetTermSimplePathCourseCategory() {
		$term_path     = [ 'Just', 'A Very Nice', 'Simple', 'A Very Nice', 'Dinosaur' ];
		$taxonomy_name = 'course-category';

		$term = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path ), $taxonomy_name );
		$this->assertTermPathValid( $term_path, $term, $taxonomy_name );
	}

	/**
	 * Tests a matching non-parent name from a different path is not used.
	 */
	public function testGetTermComplexPathCourseCategory() {
		$term_path_a   = [ 'Dinosaur', 'Pizza', 'Taco' ];
		$term_path_b   = [ 'Pizza', 'Taco', 'Dinosaur' ];
		$taxonomy_name = 'course-category';

		$term_a = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path_a ), $taxonomy_name );
		$term_b = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path_b ), $taxonomy_name );

		$this->assertTermPathValid( $term_path_a, $term_a, $taxonomy_name );
		$this->assertTermPathValid( $term_path_b, $term_b, $taxonomy_name );

		$term_path_a_ids = [];
		while ( $term_a ) {
			$term_path_a_ids[] = $term_a->term_id;

			if ( ! $term_a->parent ) {
				break;
			}

			$term_a = get_term_by( 'id', $term_a->parent, $taxonomy_name );
		}

		$term_path_b_ids = [];
		while ( $term_b ) {
			$term_path_b_ids[] = $term_b->term_id;

			if ( ! $term_b->parent ) {
				break;
			}

			$term_b = get_term_by( 'id', $term_b->parent, $taxonomy_name );
		}

		$this->assertEquals( count( $term_path_a ), count( $term_path_a_ids ), 'A: IDs should match size of path' );
		$this->assertEquals( count( $term_path_b ), count( $term_path_b_ids ), 'B: IDs should match size of path' );
		$this->assertEmpty( array_intersect( $term_path_a_ids, $term_path_b_ids ), 'There should be no similar IDs in the paths' );
	}

	/**
	 * Tests a matching parent name is shared among paths.
	 */
	public function testGetTermComplexSharedPathCourseCategory() {
		$term_path_a   = [ 'Dinosaur', 'Pizza', 'Taco' ];
		$term_path_b   = [ 'Dinosaur', 'Taco' ];
		$taxonomy_name = 'course-category';

		$term_a = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path_a ), $taxonomy_name );
		$term_b = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path_b ), $taxonomy_name );

		$this->assertTermPathValid( $term_path_a, $term_a, $taxonomy_name );
		$this->assertTermPathValid( $term_path_b, $term_b, $taxonomy_name );

		$term_path_a_ids = [];
		while ( $term_a ) {
			$term_path_a_ids[] = $term_a->term_id;

			if ( ! $term_a->parent ) {
				break;
			}

			$term_a = get_term_by( 'id', $term_a->parent, $taxonomy_name );
		}

		$term_path_b_ids = [];
		while ( $term_b ) {
			$term_path_b_ids[] = $term_b->term_id;

			if ( ! $term_b->parent ) {
				break;
			}

			$term_b = get_term_by( 'id', $term_b->parent, $taxonomy_name );
		}

		$this->assertEquals( count( $term_path_a ), count( $term_path_a_ids ), 'A: IDs should match size of path' );
		$this->assertEquals( count( $term_path_b ), count( $term_path_b_ids ), 'B: IDs should match size of path' );
		$this->assertEquals( 1, count( array_intersect( $term_path_a_ids, $term_path_b_ids ) ), 'There should be no similar IDs in the paths' );
		$this->assertEquals( $term_path_a_ids[2], $term_path_b_ids[1], 'The first term in each path should be the same' );
	}

	/**
	 * Tests a term path on a non-hierarchical taxonomy.
	 */
	public function testGetTermNonHierarchicalPath() {
		$term_path     = [ 'Just > A > Sneaky > Dinosaur' ];
		$taxonomy_name = 'lesson-tag';

		$term = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path ), $taxonomy_name );
		$this->assertTermPathValid( $term_path, $term, $taxonomy_name );
	}

	/**
	 * Tests a term path for modules with a teacher.
	 */
	public function testGetTermModulePathTeacher() {
		$teacher_id      = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$term_path       = [ 'Module A', 'Module A.1', 'Module A.2', 'Module A.3' ];
		$term_path_slugs = [ $teacher_id . '-module-a', $teacher_id . '-module-a-1', $teacher_id . '-module-a-2', $teacher_id . '-module-a-3' ];

		$taxonomy_name = 'module';

		$term = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path ), $taxonomy_name, $teacher_id );
		$this->assertTermPathValid( $term_path_slugs, $term, $taxonomy_name, 'slug' );
	}

	/**
	 * Tests a term path for modules with a teacher.
	 */
	public function testGetTermModulePathAdmin() {
		$admin_id        = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$term_path       = [ 'Module A', 'Module A.1', 'Module A.2', 'Module A.3' ];
		$term_path_slugs = [ 'module-a', 'module-a-1', 'module-a-2', 'module-a-3' ];

		$taxonomy_name = 'module';

		$term = Sensei_Data_Port_Utilities::get_term( implode( ' > ', $term_path ), $taxonomy_name, $admin_id );
		$this->assertTermPathValid( $term_path_slugs, $term, $taxonomy_name, 'slug' );
	}

	/**
	 * Assert a term path is valid by traversing up the parent path of the last term and making
	 * sure it matches the array `$term_path`.
	 *
	 * @param array         $term_path     Remaining term path.
	 * @param WP_Term|false $last_term     Latest term to be found.
	 * @param string        $taxonomy_name Name of taxonomy.
	 * @param string        $validate_with Name or slug.
	 */
	private function assertTermPathValid( $term_path, $last_term, $taxonomy_name, $validate_with = 'name' ) {
		$term_path_ids = [];
		while ( ! empty( $term_path ) ) {
			$latest_term_name = array_pop( $term_path );
			$this->assertNotFalse( $last_term, "When we expected the term '{$latest_term_name}', no term was found." );

			/**
			 * Last term object.
			 *
			 * @var WP_Term $last_term
			 */
			$this->assertFalse( in_array( $last_term->term_id, $term_path_ids, true ), 'Detected a loop in the term path' );

			$term_path_ids[] = $last_term->term_id;
			$this->assertEquals( $taxonomy_name, $last_term->taxonomy, "Term should be taxonomy '{$taxonomy_name}' but is instead '{$last_term->taxonomy}'" );
			$this->assertEquals( html_entity_decode( $last_term->{$validate_with} ), $latest_term_name, "Expected term {$validate_with} of '{$latest_term_name}', but found '{$last_term->{$validate_with}}'" );

			if ( empty( $last_term->parent ) ) {
				$this->assertEmpty( $term_path, 'Latest term had no parent. We expected no further path, but instead found: ' . implode( ' > ', $term_path ) );
			}

			$last_term = get_term_by( 'id', $last_term->parent, $taxonomy_name );
		}
	}

	/**
	 * Get curly quote test strings.
	 */
	public function curlyStrings() {
		return [
			[
				'I think “scary” dinosaurs aren\'t that scary',
				'I think "scary" dinosaurs aren\'t that scary',
			],
			[
				'“mean dog”',
				'"mean dog"',
			],
		];
	}

	/**
	 * Make sure curly quotes are replaced with straight quotes.
	 *
	 * @dataProvider curlyStrings
	 */
	public function testReplaceCurlyQuotes( $curly, $straight ) {
		$this->assertEquals( $straight, Sensei_Data_Port_Utilities::replace_curly_quotes( $curly ) );
	}

	/**
	 * Get a list separated by comma..
	 */
	public function commaSeparatedLists() {
		return [
			[
				'A, B, "C, D", E',
				[ 'A', 'B', 'C, D', 'E' ],
				[ 'A', 'B', '"C, D"', 'E' ],
			],
			[
				'My favorite animal is a dinosaur, "This is a long, long sentence", "This doesn\'t have any commas"',
				[ 'My favorite animal is a dinosaur', 'This is a long, long sentence', 'This doesn\'t have any commas' ],
				[ 'My favorite animal is a dinosaur', '"This is a long, long sentence"', '"This doesn\'t have any commas"' ],
			],
			[
				'“Dogs, Cats”, "Mixed quotes", Awesome',
				[ 'Dogs, Cats', 'Mixed quotes', 'Awesome' ],
				[ '"Dogs, Cats"', '"Mixed quotes"', 'Awesome' ],
			],
		];
	}

	/**
	 * Make sure curly quotes are replaced with straight quotes.
	 *
	 * @param string $list_str         List as a string.
	 * @param array  $list_no_quotes   List with the quotes stripped.
	 * @param array  $list_with_quotes List with the quotes still.
	 *
	 * @dataProvider commaSeparatedLists
	 */
	public function testSplitListSafely( $list_str, $list_no_quotes, $list_with_quotes ) {
		$this->assertEquals( $list_no_quotes, Sensei_Data_Port_Utilities::split_list_safely( $list_str, true ) );
		$this->assertEquals( $list_with_quotes, Sensei_Data_Port_Utilities::split_list_safely( $list_str, false ) );
	}
}
