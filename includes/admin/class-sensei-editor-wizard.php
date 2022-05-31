<?php
/**
 * File containing the class Sensei_Editor_Wizard.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles editor wizards.
 *
 * @since $$next-version$$
 */
class Sensei_Editor_Wizard {
	const PATTERNS_CATEGORY = 'sensei-lms';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Editor_Wizard constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_metas' ] );
		add_action( 'init', [ $this, 'register_block_patterns_category' ] );
		add_action( 'current_screen', [ $this, 'register_block_patterns' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Register post metas.
	 *
	 * @access private
	 */
	public function register_post_metas() {
		$meta_key = '_new_post';
		$args     = [
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'boolean',
			'auth_callback' => function( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		];

		register_post_meta( 'lesson', $meta_key, $args );
		register_post_meta( 'course', $meta_key, $args );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @access private
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		$post_type   = get_post_type();
		$post_id     = get_the_ID();
		$new_post    = get_post_meta( $post_id, '_new_post', true );
		$post_types  = [ 'course', 'lesson' ];
		$is_new_post = 'post-new.php' === $hook_suffix || $new_post;

		if ( $is_new_post && in_array( $post_type, $post_types, true ) ) {
			Sensei()->assets->enqueue( 'sensei-editor-wizard-script', 'admin/editor-wizard/index.js' );
			Sensei()->assets->enqueue( 'sensei-editor-wizard-style', 'admin/editor-wizard/style.css' );

			// Preload extensions (needed to identify if Sensei Pro is installed, and extension details).
			Sensei()->assets->preload_data( [ '/sensei-internal/v1/sensei-extensions?type=plugin' ] );
		}
	}

	/**
	 * Register Sensei block patterns category.
	 *
	 * @access private
	 */
	public function register_block_patterns_category() {
		register_block_pattern_category(
			self::PATTERNS_CATEGORY,
			array( 'label' => __( 'Sensei LMS', 'sensei-lms' ) )
		);
	}

	/**
	 * Register block patterns.
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 *
	 * @access private
	 */
	public function register_block_patterns( $current_screen ) {
		$post_type = $current_screen->post_type;

		if ( 'course' === $post_type ) {
			$this->register_course_block_patterns();
		} elseif ( 'lesson' === $post_type ) {
			$this->register_lesson_block_patterns();
		}
	}

	/**
	 * Register course block patterns.
	 */
	private function register_course_block_patterns() {
		register_block_pattern(
			'sensei-lms/video-hero',
			[
				'title'         => __( 'Video Hero', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)","bottom":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)"}},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}}},"backgroundColor":"foreground","textColor":"secondary"} -->
									<div class="wp-block-group alignfull has-secondary-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:var(--wp--custom--spacing--large, 8rem);padding-bottom:var(--wp--custom--spacing--large, 8rem)"><!-- wp:group {"align":"full","layout":{"inherit":false}} -->
									<div class="wp-block-group alignfull"><!-- wp:heading {"level":1,"align":"wide","style":{"typography":{"fontSize":"clamp(3rem, 6vw, 4.5rem)"}},"textColor":"tertiary"} -->
									<h1 class="alignwide has-tertiary-color has-text-color" id="warble-a-film-about-hobbyist-bird-watchers-1" style="font-size:clamp(3rem, 6vw, 4.5rem)">' . esc_html__( 'Welcome to the Film Direction Course', 'sensei-lms' ) . '</h1>
									<!-- /wp:heading -->

									<!-- wp:spacer {"height":"32px"} -->
									<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:video {"align":"wide"} -->
									<figure class="wp-block-video alignwide"><video controls src="https://sensei-demo.mystagingwebsite.com/wp-content/themes/twentytwentytwo/assets/videos/birds.mp4"></video></figure>
									<!-- /wp:video -->

									<!-- wp:columns {"align":"wide","textColor":"tertiary"} -->
									<div class="wp-block-columns alignwide has-tertiary-color has-text-color"><!-- wp:column {"width":"50%"} -->
									<div class="wp-block-column" style="flex-basis:50%"><!-- wp:paragraph -->
									<p><strong>Doug Stilton</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column -->

									<!-- wp:column -->
									<div class="wp-block-column"><!-- wp:paragraph {"className":"sensei-pattern-description"} -->
									<p class="sensei-pattern-description">' . esc_html__( 'Start learning about Film Direction with Doug, a senior VP at Films. You will learn all the secrets and how to prepare your project even before touching the camera.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column -->

									<!-- wp:column -->
									<div class="wp-block-column"><!-- wp:sensei-lms/button-take-course {"align":"right","backgroundColor":"tertiary","textColor":"foreground"} -->
									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><button class="wp-block-button__link has-foreground-color has-tertiary-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:group -->

									<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}},"color":{}},"textColor":"primary","layout":{"inherit":false}} -->
									<div class="wp-block-group alignfull has-primary-color has-text-color has-link-color" style="padding-top:6rem;padding-bottom:6rem"><!-- wp:group {"align":"wide"} -->
									<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
									<p class="has-text-align-center has-large-font-size">' . esc_html__( "Get to know Doug's network of professionals by taking the Course today!", 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"300"}},"fontSize":"x-large"} -->
									<p class="has-text-align-center has-x-large-font-size" style="font-weight:300"><a href="#">Jesús Rodriguez</a>, <a href="#">Emery Driscoll</a>, <a href="#">Megan Perry</a>, <a href="#">Rowan Price</a>, <a href="#">Angelo Tso</a>, <a href="#">Edward Stilton</a>, <a href="#">Amy Jensen</a>, <a href="#">Boston Bell</a>, <a href="#">Shay Ford</a>, <a href="#">Lee Cunningham</a>, <a href="#">Evelynn Ray</a>, <a href="#">Landen Reese</a>, <a href="#">Ewan Hart</a>, <a href="#">Jenna Chan</a>, <a href="#">Phoenix Murray</a>, <a href="#">Mel Saunders</a>, <a href="#">Aldo Davidson</a>, <a href="#">Zain Hall</a>.</p>
									<!-- /wp:paragraph -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:sensei-lms/button-take-course {"align":"center","backgroundColor":"foreground","textColor":"background"} -->
									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-center"><button class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course --></div>
									<!-- /wp:group --></div>
									<!-- /wp:group -->

									<!-- wp:group {"align":"full","style":{"color":{"background":"#f8f4e4"}}} -->
									<div class="wp-block-group alignfull has-background" style="background-color:#f8f4e4"><!-- wp:spacer {"height":"24px"} -->
									<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:columns {"align":"wide"} -->
									<div class="wp-block-columns alignwide"><!-- wp:column -->
									<div class="wp-block-column"><!-- wp:spacer -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:heading {"level":6,"style":{"color":{"text":"#000000"}}} -->
									<h6 class="has-text-color" id="ecosystem" style="color:#000000">' . esc_html__( 'INTRODUCTION', 'sensei-lms' ) . '</h6>
									<!-- /wp:heading -->

									<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.1","fontSize":"5vw"},"color":{"text":"#000000"}}} -->
									<p class="has-text-color" style="color:#000000;font-size:5vw;line-height:1.1"><strong>' . esc_html__( 'Film Direction', 'sensei-lms' ) . '</strong></p>
									<!-- /wp:paragraph -->

									<!-- wp:spacer {"height":"5px"} -->
									<div style="height:5px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:columns {"align":"wide"} -->
									<div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.38%"} -->
									<div class="wp-block-column" style="flex-basis:33.38%"><!-- wp:paragraph {"style":{"color":{"text":"#000000"}},"fontSize":"extra-small"} -->
									<p class="has-text-color has-extra-small-font-size" style="color:#000000">' . wp_kses_post( __( "A <strong>film director</strong> controls a film's artistic and dramatic aspects and visualizes the screenplay (or script) while guiding the film crew and actors in the fulfillment of that vision. The director has a key role in choosing the cast members, production design, and all the creative aspects of filmmaking.", 'sensei-lms' ) ) . '</p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column -->

									<!-- wp:column {"width":"33%"} -->
									<div class="wp-block-column" style="flex-basis:33%"><!-- wp:spacer -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
									<figure class="wp-block-image size-large"><img src="https://s.w.org/images/core/5.8/outside-01.jpg" alt="The sun setting through a dense forest."/></figure>
									<!-- /wp:image --></div>
									<!-- /wp:column -->

									<!-- wp:column {"width":"33.62%"} -->
									<div class="wp-block-column" style="flex-basis:33.62%"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
									<figure class="wp-block-image size-large"><img src="https://s.w.org/images/core/5.8/outside-02.jpg" alt="Wind turbines standing on a grassy plain, against a blue sky."/></figure>
									<!-- /wp:image --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:columns {"align":"wide"} -->
									<div class="wp-block-columns alignwide"><!-- wp:column {"width":"67%"} -->
									<div class="wp-block-column" style="flex-basis:67%"><!-- wp:image {"align":"right","sizeSlug":"large","linkDestination":"none"} -->
									<figure class="wp-block-image alignright size-large"><img src="https://s.w.org/images/core/5.8/outside-03.jpg" alt="The sun shining over a ridge leading down into the shore. In the distance, a car drives down a road."/></figure>
									<!-- /wp:image --></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center","width":"33%"} -->
									<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33%"><!-- wp:paragraph {"style":{"color":{"text":"#000000"}},"fontSize":"extra-small"} -->
									<p class="has-text-color has-extra-small-font-size" style="color:#000000">' . esc_html__( 'There are many pathways to becoming a film director. Some film directors started as screenwriters, cinematographers, producers, film editors, or actors. Directors use different approaches. In this course you will also learn about each of these points and figure out which one is for you.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:spacer {"height":"8px"} -->
									<div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:sensei-lms/button-take-course {"align":"left","backgroundColor":"foreground","textColor":"background"} -->
									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:spacer -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:group -->

									<!-- wp:spacer -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:group {"backgroundColor":"foreground","textColor":"background"} -->
									<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background"><!-- wp:spacer {"height":"24px"} -->
									<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:heading -->
									<h2>' . esc_html__( "Let's get started", 'sensei-lms' ) . '</h2>
									<!-- /wp:heading -->

									<!-- wp:spacer {"height":"24px"} -->
									<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:sensei-lms/course-progress /-->

									<!-- wp:sensei-lms/course-outline -->
									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Introduction', 'sensei-lms' ) . '"} /-->

									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( "Meeting Doug's network", 'sensei-lms' ) . '"} /-->

									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Start your journey', 'sensei-lms' ) . '"} /-->

									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'From script to film', 'sensei-lms' ) . '"} /-->
									<!-- /wp:sensei-lms/course-outline -->

									<!-- wp:spacer {"height":"8px"} -->
									<div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
									<div class="wp-block-group"><!-- wp:sensei-lms/button-take-course {"align":"left","backgroundColor":"background","textColor":"foreground"} -->
									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course -->

									<!-- wp:sensei-lms/button-contact-teacher -->
									<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">' . esc_html__( 'Contact Teacher', 'sensei-lms' ) . '</a></div>
									<!-- /wp:sensei-lms/button-contact-teacher --></div>
									<!-- /wp:group -->

									<!-- wp:spacer {"height":"24px"} -->
									<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:group -->',
			]
		);

		register_block_pattern(
			'sensei-lms/longe-sales-page',
			[
				'title'         => __( 'Long Sales Page', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => '<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaId":1298,"mediaLink":"https://sensei-demo.mystagingwebsite.com/course/pattern-long-sales-page/11423305963_79ef26ea28_b/","mediaType":"image","mediaWidth":58,"mediaSizeSlug":"full","verticalAlignment":"center","imageFill":false,"style":{"color":{"background":"#121c1c"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background"} -->
									<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile is-vertically-aligned-center has-background-color has-text-color has-background has-link-color" style="background-color:#121c1c;grid-template-columns:auto 58%"><figure class="wp-block-media-text__media"><img src="https://sensei-demo.mystagingwebsite.com/wp-content/uploads/2022/05/11423305963_79ef26ea28_b.jpeg" alt="" class="wp-image-1298 size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:group {"style":{"spacing":{"padding":{"top":"2em","right":"2em","bottom":"2em","left":"2em"}},"elements":{"link":{"color":{"text":"#fffdc7"}}}},"layout":{"inherit":false}} -->
									<div class="wp-block-group has-link-color" style="padding-top:2em;padding-right:2em;padding-bottom:2em;padding-left:2em"><!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"700","fontSize":"48px","lineHeight":"1.15"}}} -->
									<h1 style="font-size:48px;font-weight:700;line-height:1.15"><strong>' . esc_html__( 'Deep dive into portrait photography', 'sensei-lms' ) . '</strong></h1>
									<!-- /wp:heading -->

									<!-- wp:paragraph {"className":"sensei-pattern-description"} -->
									<p class="sensei-pattern-description">' . esc_html__( 'Learn from Jeff Bronson how to shoot photography like a pro in any outside light conditions.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:sensei-lms/button-take-course {"textColor":"background"} -->
									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-text-color">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course --></div>
									<!-- /wp:group --></div></div>
									<!-- /wp:media-text -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:heading -->
									<h2>' . esc_html__( 'What is portrait photography', 'sensei-lms' ) . '</h2>
									<!-- /wp:heading -->

									<!-- wp:paragraph -->
									<p>' . wp_kses_post( __( '<strong>Portrait photography</strong>, or <strong>portraiture</strong>, is a type of photography aimed at capturing the personality of a person or group of people by using effective lighting, backdrops, and poses. A portrait photograph may be artistic or clinical.', 'sensei-lms' ) ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:columns -->
									<div class="wp-block-columns"><!-- wp:column -->
									<div class="wp-block-column"><!-- wp:image {"id":1309,"sizeSlug":"full","linkDestination":"none"} -->
									<figure class="wp-block-image size-full"><img src="https://sensei-demo.mystagingwebsite.com/wp-content/uploads/2022/05/15881510678_2bb334c5ec_b.jpeg" alt="" class="wp-image-1309"/></figure>
									<!-- /wp:image --></div>
									<!-- /wp:column -->

									<!-- wp:column -->
									<div class="wp-block-column"></div>
									<!-- /wp:column -->

									<!-- wp:column {"width":"10%"} -->
									<div class="wp-block-column" style="flex-basis:10%"></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:columns -->
									<div class="wp-block-columns"><!-- wp:column -->
									<div class="wp-block-column"></div>
									<!-- /wp:column -->

									<!-- wp:column -->
									<div class="wp-block-column"><!-- wp:quote -->
									<blockquote class="wp-block-quote"><p>' . esc_html__( 'A photographic portrait means to consider who we have before us and what we want to show about that person.', 'sensei-lms' ) . '</p><cite>Jeff Bronson</cite></blockquote>
									<!-- /wp:quote --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:spacer {"height":"24px"} -->
									<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)","bottom":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)"}},"elements":{"link":{"color":{"text":"var:preset|color|background"}}},"color":{"background":"#121c1c"}},"textColor":"background","layout":{"inherit":true}} -->
									<div class="wp-block-group alignfull has-background-color has-text-color has-background has-link-color" style="background-color:#121c1c;padding-top:var(--wp--custom--spacing--large, 8rem);padding-bottom:var(--wp--custom--spacing--large, 8rem)"><!-- wp:heading -->
									<h2>' . esc_html__( 'What you will learn to master', 'sensei-lms' ) . '</h2>
									<!-- /wp:heading -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:group {"align":"full","layout":{"inherit":true}} -->
									<div class="wp-block-group alignfull"><!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
									<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
									<!-- /wp:separator -->

									<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
									<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
									<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
									<h3>' . esc_html__( 'Lighting for portraiture', 'sensei-lms' ) . '</h3>
									<!-- /wp:heading --></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
									<p>' . esc_html__( "There are many techniques available to light a subject's face.", 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
									<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
									<!-- /wp:separator -->

									<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
									<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
									<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
									<h3>' . esc_html__( 'Three-point lighting', 'sensei-lms' ) . '</h3>
									<!-- /wp:heading --></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
									<p>' . esc_html__( 'Three-point lighting is one of the most common lighting setups. It is traditionally used in a studio, but photographers may use it on-location in combination with ambient light.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
									<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
									<!-- /wp:separator -->

									<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
									<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
									<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
									<h3>' . esc_html__( 'Key light', 'sensei-lms' ) . '</h3>
									<!-- /wp:heading --></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
									<p>' . esc_html__( "The key light, also known as the main light, is placed either to the left, right, or above the subject's face, typically 30 to 60 degrees from the camera.", 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
									<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
									<!-- /wp:separator -->

									<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
									<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
									<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
									<h3>' . esc_html__( 'Fill light', 'sensei-lms' ) . '</h3>
									<!-- /wp:heading --></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"></div>
									<!-- /wp:column -->

									<!-- wp:column {"verticalAlignment":"center"} -->
									<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
									<p>' . esc_html__( 'The fill light, also known as the secondary main light, is typically placed opposite the key light.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->

									<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
									<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
									<!-- /wp:separator --></div>
									<!-- /wp:group --></div>
									<!-- /wp:group -->

									<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"background","textColor":"foreground","layout":{"inherit":true}} -->
									<div class="wp-block-group alignfull has-foreground-color has-background-background-color has-text-color has-background has-link-color" style="padding-top:6rem;padding-bottom:6rem"><!-- wp:media-text {"mediaId":1337,"mediaLink":"https://sensei-demo.mystagingwebsite.com/course/pattern-long-sales-page/11423305963_79ef26ea28_b-1/","mediaType":"image","verticalAlignment":"bottom","imageFill":false} -->
									<div class="wp-block-media-text alignwide is-stacked-on-mobile is-vertically-aligned-bottom"><figure class="wp-block-media-text__media"><img src="https://sensei-demo.mystagingwebsite.com/wp-content/uploads/2022/05/11423305963_79ef26ea28_b-1.jpeg" alt="" class="wp-image-1337 size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:heading -->
									<h2>' . esc_html__( 'Meet Jeff Bronson', 'sensei-lms' ) . '</h2>
									<!-- /wp:heading -->

									<!-- wp:paragraph -->
									<p>' . esc_html__( 'You will begin by getting to know the work of Jeff de Bronson, who will also teach you how to learn the best tricks he accumulated throughout his 25+ years of experience.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:paragraph -->
									<p>' . esc_html__( 'Jeff lives in NYC and has worked for many world-famous publications. In his free time, he likes to discover new ways of how he can pass on the skills and artistic views accumulated in his journey.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:sensei-lms/button-contact-teacher -->
									<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">' . esc_html__( 'Contact Teacher', 'sensei-lms' ) . '</a></div>
									<!-- /wp:sensei-lms/button-contact-teacher --></div></div>
									<!-- /wp:media-text --></div>
									<!-- /wp:group -->

									<!-- wp:group {"align":"full","style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}},"spacing":{"padding":{"top":"6rem","bottom":"4rem"}}},"backgroundColor":"foreground","textColor":"background","layout":{"inherit":true}} -->
									<div class="wp-block-group alignfull has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:6rem;padding-bottom:4rem"><!-- wp:columns {"align":"wide"} -->
									<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
									<div class="wp-block-column" style="flex-basis:50%"><!-- wp:heading {"fontSize":"x-large"} -->
									<h2 class="has-x-large-font-size" id="extended-trailer">' . esc_html__( 'Jeff at work', 'sensei-lms' ) . '</h2>
									<!-- /wp:heading -->

									<!-- wp:paragraph -->
									<p>' . esc_html__( 'Meet Jeff in his studio and see firsthand how he approaches a photoshoot in this exclusive trailer.', 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:paragraph -->
									<p>' . esc_html__( "Unlock the full video by signing up for Jeff's course.", 'sensei-lms' ) . '</p>
									<!-- /wp:paragraph -->

									<!-- wp:sensei-lms/button-take-course {"textColor":"background"} -->

									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-text-color">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course --></div>
									<!-- /wp:column -->

									<!-- wp:column {"width":"66.66%"} -->
									<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:video -->
									<figure class="wp-block-video"><video controls src="https://sensei-demo.mystagingwebsite.com/wp-content/themes/twentytwentytwo/assets/videos/birds.mp4"></video></figure>
									<!-- /wp:video --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:image {"align":"center","width":150,"height":150,"sizeSlug":"large","linkDestination":"none","className":"is-style-rounded"} -->
									<figure class="wp-block-image aligncenter size-large is-resized is-style-rounded"><img src="https://s.w.org/images/core/5.8/portrait.jpg" alt="A side profile of a woman in a russet-colored turtleneck and white bag. She looks up with her eyes closed." width="150" height="150"/></figure>
									<!-- /wp:image -->

									<!-- wp:quote {"align":"center","className":"is-style-large"} -->
									<blockquote class="wp-block-quote has-text-align-center is-style-large"><p>' . esc_html__( '"Jeff\'s course really help me understand how to work with light and my closer to my subjects. Amazing course!"', 'sensei-lms' ) . '</p><cite>— Anna Wong, <em>' . esc_html__( 'Volunteer', 'sensei-lms' ) . '</em></cite></blockquote>
									<!-- /wp:quote -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:group {"backgroundColor":"foreground","textColor":"background","layout":{"inherit":false}} -->
									<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background"><!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->

									<!-- wp:heading {"textAlign":"left"} -->
									<h2 class="has-text-align-left">' . esc_html__( 'Course Lessons', 'sensei-lms' ) . '</h2>
									<!-- /wp:heading -->

									<!-- wp:sensei-lms/course-progress /-->

									<!-- wp:sensei-lms/course-outline -->
									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Lighting for portraiture', 'sensei-lms' ) . '"} /-->

									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Three-point lighting', 'sensei-lms' ) . '"} /-->

									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Key light', 'sensei-lms' ) . '"} /-->

									<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Fill light', 'sensei-lms' ) . '"} /-->
									<!-- /wp:sensei-lms/course-outline -->

									<!-- wp:sensei-lms/button-take-course {"textColor":"background"} -->
									<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-text-color">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
									<!-- /wp:sensei-lms/button-take-course -->

									<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:group -->

									<!-- wp:paragraph -->
									<p></p>
									<!-- /wp:paragraph -->',
			]
		);
	}

	/**
	 * Register lesson block patterns.
	 */
	private function register_lesson_block_patterns() {
		register_block_pattern(
			'sensei-lms/artists',
			array(
				'title'         => __( 'Artists', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => "<!-- wp:group {\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"20px\",\"bottom\":\"20px\"}},\"color\":{\"gradient\":\"linear-gradient(135deg,rgb(253,239,230) 0%,rgba(155,81,224,0.09) 100%)\"}},\"layout\":{\"inherit\":false,\"contentSize\":\"900px\"}} --><div class=\"wp-block-group alignfull has-background\" style=\"background:linear-gradient(135deg,rgb(253,239,230) 0%,rgba(155,81,224,0.09) 100%);padding-top:20px;padding-bottom:20px\"><!-- wp:columns {\"align\":\"wide\"} --><div class=\"wp-block-columns alignwide\"><!-- wp:column {\"verticalAlignment\":\"center\",\"style\":{\"color\":{\"gradient\":\"radial-gradient(rgba(101,0,202,0.07) 0%,rgba(155,81,224,0) 64%)\"}}} --><div class=\"wp-block-column is-vertically-aligned-center has-background\" style=\"background:radial-gradient(rgba(101,0,202,0.07) 0%,rgba(155,81,224,0) 64%)\"><!-- wp:heading {\"style\":{\"typography\":{\"fontStyle\":\"normal\",\"fontWeight\":\"200\"},\"spacing\":{\"margin\":{\"bottom\":\"0px\"}}},\"textColor\":\"vivid-purple\",\"className\":\"sensei-pattern-title\"} --><h2 class=\"sensei-pattern-title has-vivid-purple-color has-text-color\" id=\"2022-artists-line-up\" style=\"font-style:normal;font-weight:200;margin-bottom:0px\">2022 Artists' Line Up</h2><!-- /wp:heading --><!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"16px\"}},\"className\":\"sensei-pattern-description\"} --><p class=\"sensei-pattern-description\" style=\"font-size:16px\">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class=\"wp-block-column\"><!-- wp:columns --><div class=\"wp-block-columns\"><!-- wp:column {\"verticalAlignment\":\"bottom\"} --><div class=\"wp-block-column is-vertically-aligned-bottom\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\",\"style\":{\"border\":{\"radius\":{\"topLeft\":\"20px\",\"topRight\":null,\"bottomLeft\":null}}}} --><figure class=\"wp-block-image size-full\" style=\"border-top-left-radius:20px\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/a010-markusspiske-dec18-msp_1811_1118.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=7dd58e5d4caaa44d0b5c2db731af7c6d\" alt=\"\"/></figure><!-- /wp:image --><!-- wp:cover {\"customOverlayColor\":\"#fadcde\",\"minHeight\":208,\"contentPosition\":\"bottom right\",\"isDark\":false} --><div class=\"wp-block-cover is-light has-custom-content-position is-position-bottom-right\" style=\"min-height:208px\"><span aria-hidden=\"true\" class=\"has-background-dim-100 wp-block-cover__gradient-background has-background-dim\" style=\"background-color:#fadcde\"></span><div class=\"wp-block-cover__inner-container\"><!-- wp:paragraph {\"style\":{\"elements\":{\"link\":{\"color\":{\"text\":\"#81252a\"}}},\"color\":{\"text\":\"#81252a\"}}} --><p class=\"has-text-color has-link-color\" style=\"color:#81252a\"><a href=\"#\">Explore artist</a></p><!-- /wp:paragraph --></div></div><!-- /wp:cover --></div><!-- /wp:column --><!-- wp:column {\"verticalAlignment\":\"top\",\"style\":{\"spacing\":{\"padding\":{\"bottom\":\"80px\"}}}} --><div class=\"wp-block-column is-vertically-aligned-top\" style=\"padding-bottom:80px\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\",\"style\":{\"border\":{\"radius\":{\"topLeft\":null,\"topRight\":\"20px\",\"bottomLeft\":null,\"bottomRight\":null}}}} --><figure class=\"wp-block-image size-full\" style=\"border-top-right-radius:20px\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pdmonet-etretaalaise-d-amont.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=c7b12dd89b757159e23703bb9cd53d12\" alt=\"\"/></figure><!-- /wp:image --><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\",\"style\":{\"border\":{\"radius\":{\"topLeft\":null,\"topRight\":null,\"bottomLeft\":null,\"bottomRight\":\"20px\"}}}} --><figure class=\"wp-block-image size-full\" style=\"border-bottom-right-radius:20px\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/a010-markusspiske-dec18-msp_1811_1121.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=919c2505ef3ef5f04a2d28ca4027a841\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->",
			)
		);

		register_block_pattern(
			'sensei-lms/testimonials',
			array(
				'title'         => __( 'Testimonials', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => "<!-- wp:paragraph {\"align\":\"center\",\"style\":{\"typography\":{\"textTransform\":\"uppercase\",\"fontStyle\":\"normal\",\"fontWeight\":\"400\"}},\"fontSize\":\"medium\"} --><p class=\"has-text-align-center has-medium-font-size\" style=\"font-style:normal;font-weight:400;text-transform:uppercase\">TESTIMONIALS</p><!-- /wp:paragraph --><!-- wp:paragraph {\"align\":\"center\",\"style\":{\"color\":{\"text\":\"#359756\"},\"typography\":{\"fontStyle\":\"normal\",\"fontWeight\":\"700\"}},\"className\":\"sensei-pattern-title\",\"fontSize\":\"x-large\"} --><p class=\"has-text-align-center sensei-pattern-title has-text-color has-x-large-font-size\" style=\"color:#359756;font-style:normal;font-weight:700\">What Clients Say</p><!-- /wp:paragraph --><!-- wp:paragraph {\"align\":\"center\",\"className\":\"sensei-pattern-description\"} --><p class=\"has-text-align-center sensei-pattern-description\">We place huge value on strong relationships and have seen the benefit' they bring to our business. Customer feedback is vital in helping us to get it right.</p><!-- /wp:paragraph --><!-- wp:columns {\"verticalAlignment\":\"center\",\"align\":\"wide\",\"style\":{\"spacing\":{\"padding\":{\"bottom\":\"30px\"}}}} --><div class=\"wp-block-columns alignwide are-vertically-aligned-center\" style=\"padding-bottom:30px\"><!-- wp:column {\"verticalAlignment\":\"center\",\"style\":{\"color\":{\"background\":\"#f2f2f2\"},\"spacing\":{\"padding\":{\"top\":\"30px\",\"bottom\":\"20px\",\"right\":\"20px\",\"left\":\"20px\"}}}} --><div class=\"wp-block-column is-vertically-aligned-center has-background\" style=\"background-color:#f2f2f2;padding-top:30px;padding-right:20px;padding-bottom:20px;padding-left:20px\"><!-- wp:image {\"align\":\"center\",\"width\":200,\"height\":200,\"linkDestination\":\"none\",\"className\":\"size-full is-style-default\"} --><div class=\"wp-block-image size-full is-style-default\"><figure class=\"aligncenter is-resized\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/a010-markusspiske-12040825.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=fa18848d8bc2bf80df3713426b6e8d69\" alt=\"\" width=\"200\" height=\"200\"/></figure></div><!-- /wp:image --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">\"Vitae suscipit tellus mauris a diam maecenas sed enim ut. Mauris augue neque gravida in fermentum. Praesent semper feugiat nibh sed pulvinar.\"</p><!-- /wp:paragraph --><!-- wp:heading {\"textAlign\":\"center\",\"level\":5,\"style\":{\"color\":{\"text\":\"#359756\"}}} --><h5 class=\"has-text-align-center has-text-color\" id=\"nat-reynolds\" style=\"color:#359756\">Nat Reynolds</h5><!-- /wp:heading --><!-- wp:heading {\"textAlign\":\"center\",\"level\":6,\"style\":{\"color\":{\"text\":\"#808080\"}}} --><h6 class=\"has-text-align-center has-text-color\" id=\"chief-accountant\" style=\"color:#808080\">Chief Accountant</h6><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {\"verticalAlignment\":\"center\",\"style\":{\"color\":{\"background\":\"#f2f2f2\"},\"spacing\":{\"padding\":{\"top\":\"30px\",\"bottom\":\"20px\",\"right\":\"20px\",\"left\":\"20px\"}}}} --><div class=\"wp-block-column is-vertically-aligned-center has-background\" style=\"background-color:#f2f2f2;padding-top:30px;padding-right:20px;padding-bottom:20px;padding-left:20px\"><!-- wp:image {\"align\":\"center\",\"width\":200,\"height\":200,\"linkDestination\":\"none\",\"className\":\"size-full is-style-default\"} --><div class=\"wp-block-image size-full is-style-default\"><figure class=\"aligncenter is-resized\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/a010-markusspiske-12040825.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=fa18848d8bc2bf80df3713426b6e8d69\" alt=\"\" width=\"200\" height=\"200\"/></figure></div><!-- /wp:image --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">\"Pharetra vel turpis nunc eget lorem. Quisque id diam vel quam elementum pulvinar etiam. Urna porttitor rhoncus dolor purus non enim.\"</p><!-- /wp:paragraph --><!-- wp:heading {\"textAlign\":\"center\",\"level\":5,\"style\":{\"color\":{\"text\":\"#359756\"}}} --><h5 class=\"has-text-align-center has-text-color\" id=\"celia-almeda\" style=\"color:#359756\">Celia Almeda</h5><!-- /wp:heading --><!-- wp:heading {\"textAlign\":\"center\",\"level\":6,\"style\":{\"color\":{\"text\":\"#808080\"}}} --><h6 class=\"has-text-align-center has-text-color\" id=\"secretary\" style=\"color:#808080\">Secretary</h6><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {\"verticalAlignment\":\"center\",\"style\":{\"color\":{\"background\":\"#f2f2f2\"},\"spacing\":{\"padding\":{\"top\":\"30px\",\"bottom\":\"20px\",\"right\":\"20px\",\"left\":\"20px\"}}}} --><div class=\"wp-block-column is-vertically-aligned-center has-background\" style=\"background-color:#f2f2f2;padding-top:30px;padding-right:20px;padding-bottom:20px;padding-left:20px\"><!-- wp:image {\"align\":\"center\",\"width\":200,\"height\":200,\"linkDestination\":\"none\",\"className\":\"size-full is-style-default\"} --><div class=\"wp-block-image size-full is-style-default\"><figure class=\"aligncenter is-resized\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/a010-markusspiske-12040825.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=fa18848d8bc2bf80df3713426b6e8d69\" alt=\"\" width=\"200\" height=\"200\"/></figure></div><!-- /wp:image --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">\"Mauris augue neque gravida in fermentum. Praesent semper feugiat nibh sed pulvinar proin. Nibh nisl dictumst vestibulum rhoncus.\"</p><!-- /wp:paragraph --><!-- wp:heading {\"textAlign\":\"center\",\"level\":5,\"style\":{\"color\":{\"text\":\"#359756\"}}} --><h5 class=\"has-text-align-center has-text-color\" id=\"bob-roberts\" style=\"color:#359756\">Bob Roberts</h5><!-- /wp:heading --><!-- wp:heading {\"textAlign\":\"center\",\"level\":6,\"style\":{\"color\":{\"text\":\"#808080\"}}} --><h6 class=\"has-text-align-center has-text-color\" id=\"sales-manager\" style=\"color:#808080\">Sales Manager</h6><!-- /wp:heading --></div><!-- /wp:column --></div><!-- /wp:columns -->",
			)
		);

		register_block_pattern(
			'sensei-lms/featured',
			array(
				'title'         => __( 'Featured', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => "<!-- wp:group {\"align\":\"full\",\"style\":{\"color\":{\"gradient\":\"linear-gradient(180deg,rgb(230,255,231) 0%,rgb(255,255,255) 100%)\"},\"spacing\":{\"padding\":{\"top\":\"20px\",\"right\":\"20px\",\"bottom\":\"20px\",\"left\":\"20px\"}}},\"layout\":{\"contentSize\":\"1320px\"}} --><div class=\"wp-block-group alignfull has-background\" style=\"background:linear-gradient(180deg,rgb(230,255,231) 0%,rgb(255,255,255) 100%);padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px\"><!-- wp:columns {\"verticalAlignment\":\"bottom\",\"style\":{\"color\":{\"gradient\":\"radial-gradient(rgba(7,227,152,0.38) 0%,rgba(255,255,255,0) 65%)\"},\"spacing\":{\"padding\":{\"top\":\"0px\",\"right\":\"0px\",\"bottom\":\"0px\",\"left\":\"0px\"},\"blockGap\":\"10px\"}}} --><div class=\"wp-block-columns are-vertically-aligned-bottom has-background\" style=\"background:radial-gradient(rgba(7,227,152,0.38) 0%,rgba(255,255,255,0) 65%);padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px\"><!-- wp:column {\"verticalAlignment\":\"bottom\"} --><div class=\"wp-block-column is-vertically-aligned-bottom\"><!-- wp:paragraph {\"style\":{\"typography\":{\"textTransform\":\"uppercase\",\"fontStyle\":\"normal\",\"fontWeight\":\"500\",\"fontSize\":\"16px\"},\"color\":{\"text\":\"#6c6c6c\"}}} --><p class=\"has-text-color\" style=\"color:#6c6c6c;font-size:16px;font-style:normal;font-weight:500;text-transform:uppercase\">officia deserunt mollit</p><!-- /wp:paragraph --><!-- wp:heading {\"style\":{\"typography\":{\"textTransform\":\"capitalize\"},\"color\":{\"text\":\"#acbf17\"},\"spacing\":{\"margin\":{\"top\":\"0px\",\"bottom\":\"0px\"}}},\"className\":\"sensei-pattern-title\"} --><h2 class=\"sensei-pattern-title has-text-color\" id=\"lorem-ipsum-dolor\" style=\"color:#acbf17;margin-top:0px;margin-bottom:0px;text-transform:capitalize\">Lorem ipsum' dolor</h2><!-- /wp:heading --><!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"14px\",\"lineHeight\":\"1.5\",\"fontStyle\":\"italic\",\"fontWeight\":\"400\"},\"color\":{\"text\":\"#3a3a3a\"},\"elements\":{\"link\":{\"color\":{\"text\":\"#acbf17\"}}}},\"className\":\"sensei-pattern-description\"} --><p class=\"sensei-pattern-description has-text-color has-link-color\" style=\"color:#3a3a3a;font-size:14px;font-style:italic;font-weight:400;line-height:1.5\">Consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id <a href=\"#\">est laborum</a>.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {\"verticalAlignment\":\"bottom\"} --><div class=\"wp-block-column is-vertically-aligned-bottom\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pd22-tong-002240364.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=75ddeb1930b166fc00c40e73c6a03af3\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->",
			)
		);
	}
}
