<?php
/**
 * File containing the \Sensei\WPML\Slug class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Slug
 *
 * Compatibility code with WPML.
 *
 * @since 4.23.1
 *
 * @internal
 */
class Slug {
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		if ( ! $this->is_wpml_active() ) {
			return;
		}

		add_filter( 'sensei_course_slug', array( $this, 'get_course_slug' ) );
		add_filter( 'sensei_lesson_slug', array( $this, 'get_lesson_slug' ) );
		add_filter( 'sensei_quiz_slug', array( $this, 'get_quiz_slug' ) );
	}

	/**
	 * Get course slug.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param string $slug The course slug.
	 * @return string
	 */
	public function get_course_slug( $slug ) {
		if ( Sensei()->settings->get( 'wpml_slug_translation' ) ) {
			return 'course';
		}

		return $slug;
	}

	/**
	 * Get lesson slug.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param string $slug Lesson slug.
	 * @return string
	 */
	public function get_lesson_slug( $slug ) {
		if ( Sensei()->settings->get( 'wpml_slug_translation' ) ) {
			return 'lesson';
		}

		return $slug;
	}

	/**
	 * Get quiz slug.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param string $slug The quiz slug.
	 * @return string
	 */
	public function get_quiz_slug( $slug ) {
		if ( Sensei()->settings->get( 'wpml_slug_translation' ) ) {
			return 'quiz';
		}

		return $slug;
	}
}
