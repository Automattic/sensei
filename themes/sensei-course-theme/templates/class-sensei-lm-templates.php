<?php
/**
 * The class file for Sensei_LM_Templates.
 *
 * @author      Automattic
 * @package     Sensei
 * @version     $$next-version$$
 */

// Import required classes.
require_once __DIR__ . '/class-sensei-lm-template.php';

/**
 * Class Sensei_LM_Templates
 */
class Sensei_LM_Templates {
	/**
	 * Returns the templates info.
	 *
	 * @return Sensei_LM_Template[]
	 */
	public static function get_templates(): array {
		$base_path = Sensei_Course_Theme::instance()->get_course_theme_root() . '/templates';
		$base_url  = Sensei_Course_Theme::instance()->get_course_theme_root_url() . '/templates';

		return [

			'default'    => new Sensei_LM_Template(
				[
					'name'        => 'default',
					'title'       => __( 'Default', 'sensei-lms' ),
					'content'     => [
						'lesson' => "$base_path/default/lesson.html",
						'quiz'   => "$base_path/default/quiz.html",
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/default/img-thumb.jpg",
						'full'      => "$base_url/default/img-full.jpg",
					],
				]
			),

			'modern'     => new Sensei_LM_Template(
				[
					'name'        => 'modern',
					'title'       => __( 'Modern', 'sensei-lms' ),
					'content'     => [
						'lesson' => '',
						'quiz'   => '',
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/modern/img-thumb.jpg",
						'full'      => "$base_url/modern/img-full.jpg",
					],
				]
			),

			'video'      => new Sensei_LM_Template(
				[
					'name'        => 'video',
					'title'       => __( 'Video', 'sensei-lms' ),
					'content'     => [
						'lesson' => '',
						'quiz'   => '',
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/video/img-thumb.jpg",
						'full'      => "$base_url/video/img-full.jpg",
					],
				]
			),

			'video-full' => new Sensei_LM_Template(
				[
					'name'        => 'video-full',
					'title'       => __( 'Video Full', 'sensei-lms' ),
					'content'     => [
						'lesson' => '',
						'quiz'   => '',
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/video-full/img-thumb.jpg",
						'full'      => "$base_url/video-full/img-full.jpg",
					],
				]
			),

		];
	}

}
