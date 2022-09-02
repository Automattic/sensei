<?php
/**
 * The class file for Sensei_LM_Template_Default.
 *
 * @author      Automattic
 * @package     Sensei
 * @version     $$next-version$$
 */

/**
 * Class Sensei_LM_Template_Default
 */
class Sensei_LM_Template_Default {
	/**
	 * The version of this template.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * The name of the template.
	 *
	 * @var string
	 */
	const NAME = 'default';

	/**
	 * Returns the template info.
	 */
	public static function get_info() {
		$base_path = Sensei_Course_Theme::instance()->get_course_theme_root() . '/templates';
		$base_url  = Sensei_Course_Theme::instance()->get_course_theme_root_url() . '/templates';
		$name      = self::NAME;

		return [
			'name'        => $name,
			'title'       => __( 'Default', 'sensei-lms' ),
			'content'     => [
				'lesson' => "$base_path/$name/lesson.html",
				'quiz'   => "$base_path/$name/quiz.html",
			],
			'version'     => self::VERSION,
			'styles'      => [],
			'scripts'     => [],
			'screenshots' => [
				'thumbnail' => "$base_url/$name/img-thumb.jpg",
				'full'      => "$base_url/$name/img-full.jpg",
			],
		];
	}

}
