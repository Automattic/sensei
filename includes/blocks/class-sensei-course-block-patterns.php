<?php
/**
 * File containing the Sensei_Course_Block_Patterns class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Block_Patterns
 */
class Sensei_Course_Block_Patterns {

	/**
	 * Sensei_Course_Block_Patterns constructor.
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'register_patterns' ] );
	}

	/**
	 * Register course block patterns.
	 */
	public function register_patterns() {

		$current_screen = get_current_screen();

		if ( 'course' !== $current_screen->post_type ) {
			return;
		}

		$this->register_sensei_pattern_category();
		$this->register_course_media_pattern();
		$this->register_course_cover_pattern();

	}

	/**
	 * Register block pattern for course layout with media.
	 */
	public function register_course_media_pattern() {

		$sample_img = 'https://images.unsplash.com/photo-1443948308135-d57fc66de368?&auto=format&fit=crop&w=1275&q=80';

		register_block_pattern(
			'sensei-lms/course-media',
			array(
				'title'         => __( 'Course layout with media', 'sensei-lms' ),
				'description'   => __( 'Course layout with an image and text header.', 'sensei-lms' ),
				'categories'    => [ 'sensei-lms' ],
				'viewportWidth' => 800,
				'content'       => '<!-- wp:media-text { "mediaLink": "' . $sample_img . '", "mediaType":"image"} -->
<div class="wp-block-media-text alignwide is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="' . $sample_img . '"  alt="" /></figure><div class="wp-block-media-text__content"><!-- wp:paragraph -->
<p>Course introduction.</p>
<!-- /wp:paragraph -->

<!-- wp:sensei-lms/button-take-course {"align":"full"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link">Take Course</button></div>
<!-- /wp:sensei-lms/button-take-course --></div></div>
<!-- /wp:media-text -->

<!-- wp:sensei-lms/course-progress /-->

<!-- wp:heading -->
<h2>Course Outline</h2>
<!-- /wp:heading -->

<!-- wp:sensei-lms/course-outline {"moduleBorder":false,"className":"is-style-minimal"} /-->

<!-- wp:sensei-lms/button-contact-teacher {"align":"right","className":"is-style-link"} -->
<div class="wp-block-sensei-lms-button-contact-teacher is-style-link wp-block-sensei-button wp-block-button has-text-align-right"><a class="">Contact Teacher</a></div>
<!-- /wp:sensei-lms/button-contact-teacher -->',
			)
		);
	}

	/**
	 * Register block pattern for course layout with cover.
	 */
	public function register_course_cover_pattern() {

		$sample_img = 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?&auto=format&fit=crop&w=2978&q=80';

		register_block_pattern(
			'sensei-lms/course-cover',
			array(
				'title'         => __( 'Course layout with cover', 'sensei-lms' ),
				'description'   => __( 'Course layout with a cover for introduction.', 'sensei-lms' ),
				'categories'    => [ 'sensei-lms' ],
				'viewportWidth' => 800,
				'content'       => '<!-- wp:cover {"url":"' . $sample_img . '","contentPosition":"center center","align":"full"} -->
<div class="wp-block-cover alignfull has-background-dim is-position-center-center" style="background-image:url(' . $sample_img . ')"><div class="wp-block-cover__inner-container"><!-- wp:paragraph -->
<p>Course Introduction</p>
<!-- /wp:paragraph -->

<!-- wp:sensei-lms/button-take-course {"className":"is-style-default"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Take Course</button></div>
<!-- /wp:sensei-lms/button-take-course -->

<!-- wp:sensei-lms/course-progress {} /--></div></div>
<!-- /wp:cover -->

<!-- wp:heading -->
<h2>Course Outline</h2>
<!-- /wp:heading -->

<!-- wp:sensei-lms/course-outline {"moduleBorder":false,"className":"is-style-minimal"} /-->

<!-- wp:sensei-lms/button-contact-teacher {"align":"right","className":"is-style-link"} -->
<div class="wp-block-sensei-lms-button-contact-teacher is-style-link wp-block-sensei-button wp-block-button has-text-align-right"><a class="">Contact Teacher</a></div>
<!-- /wp:sensei-lms/button-contact-teacher -->',
			)
		);
	}

	/**
	 * Register Sensei LMS block pattern category.
	 */
	public function register_sensei_pattern_category() {
		register_block_pattern_category( 'sensei-lms', [ 'label' => __( 'Sensei LMS', 'sensei-lms' ) ] );
	}

}
