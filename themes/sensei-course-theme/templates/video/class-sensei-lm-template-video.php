<?php
/**
 * The class file for Sensei_LM_Template_Video.
 *
 * @author      Automattic
 * @package     Sensei
 * @version     $$next-version$$
 */

/**
 * Class Sensei_LM_Template_Video
 */
class Sensei_LM_Template_Video {
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
	const NAME = 'video';

	/**
	 * Returns the template info.
	 */
	public static function get_info() {
		$base_url = Sensei_Course_Theme::instance()->get_course_theme_root_url() . '/templates';
		$name     = self::NAME;

		return [
			'name'        => $name,
			'title'       => __( 'Video Course', 'sensei-lms' ),
			'content'     => [
				'lesson' => '',
				'quiz'   => '',
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
