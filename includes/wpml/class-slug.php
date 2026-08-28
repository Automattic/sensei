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
		add_filter( 'sensei_question_slug', array( $this, 'get_question_slug' ) );

		add_action( 'init', array( $this, 'maybe_activate_wpml_slug_translation' ), 20 );
	}

	/**
	 * Enable WPML's slug translation for the Sensei post types when the option is on.
	 *
	 * With the option enabled, Sensei registers fixed base slugs (see the filters
	 * below), and this wires the WPML side so those slugs can be translated per
	 * language in String Translation instead.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 */
	public function maybe_activate_wpml_slug_translation() {
		if ( ! Sensei()->settings->get( 'wpml_slug_translation' ) ) {
			return;
		}

		$sensei_types = array( 'course', 'lesson', 'quiz' );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$original      = apply_filters( 'wpml_setting', array(), 'posts_slug_translation' );
		$slug_settings = is_array( $original ) ? $original : array();

		foreach ( $sensei_types as $post_type ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'wpml_register_single_string', 'WordPress', 'URL slug: ' . $post_type, $post_type );

			$slug_settings['types'][ $post_type ] = 1;
		}

		// Turn the master switch on only when no other post type was configured before:
		// re-enabling it on a site that deliberately disabled slug translation would
		// change the URLs of those other post types.
		$foreign_types = array_diff_key( $original['types'] ?? array(), array_flip( $sensei_types ) );
		if ( empty( $slug_settings['on'] ) && empty( $foreign_types ) ) {
			$slug_settings['on'] = 1;
		}

		if ( $slug_settings !== $original ) {
			$this->save_slug_translation_settings( $slug_settings );
		}
	}

	/**
	 * Persist WPML's slug translation settings.
	 *
	 * @param array $slug_settings The `posts_slug_translation` WPML setting.
	 */
	protected function save_slug_translation_settings( $slug_settings ) {
		global $sitepress;

		if ( ! $sitepress || ! method_exists( $sitepress, 'set_setting' ) ) {
			return;
		}

		$sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
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
	 * Get question slug.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param string $slug Question slug.
	 * @return string
	 */
	public function get_question_slug( $slug ) {
		if ( Sensei()->settings->get( 'wpml_slug_translation' ) ) {
			return 'question';
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
