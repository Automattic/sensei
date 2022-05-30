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
			'sensei-lms/teachers',
			array(
				'title'         => __( 'Teachers', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => "<!-- wp:heading {\"textAlign\":\"center\",\"level\":5,\"style\":{\"typography\":{\"fontStyle\":\"normal\",\"fontWeight\":\"400\",\"textTransform\":\"uppercase\"}}} --><h5 class=\"has-text-align-center\" id=\"our-team\" style=\"font-style:normal;font-weight:400;text-transform:uppercase\"><strong>OUR TEAM</strong></h5><!-- /wp:heading --><!-- wp:heading {\"textAlign\":\"center\",\"style\":{\"typography\":{\"fontStyle\":\"normal\",\"fontWeight\":\"900\"}},\"textColor\":\"vivid-red\",\"className\":\"sensei-pattern-title\"} --><h2 class=\"has-text-align-center sensei-pattern-title has-vivid-red-color has-text-color\" id=\"experienced-professional\" style=\"font-style:normal;font-weight:900\">Experienced &amp; Professional</h2><!-- /wp:heading --><!-- wp:paragraph {\"align\":\"center\",\"className\":\"sensei-pattern-description\"} --><p class=\"has-text-align-center sensei-pattern-description\">We're Proud to present you our experienced and professional people who will work on your project. </p><!-- /wp:paragraph --><!-- wp:group --><div class=\"wp-block-group\"><!-- wp:columns --><div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"33.34%\"} --><div class=\"wp-block-column\" style=\"flex-basis:33.34%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\",\"style\":{\"color\":[]}} --><figure class=\"wp-block-image size-full\"><img src=\"https://ids.si.edu/ids/deliveryService?id=SAAM-1982.104.1_1\" alt=\"\"/><figcaption><strong>Jack Tommy Bishop - CEO</strong></figcaption></figure><!-- /wp:image --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">Jack Tommy Bishop is a 53-year-old senior politician who enjoys swimming, watching television and travelling. He is friendly and bright, but can also be very sneaky and a bit boring.</p><!-- /wp:paragraph --><!-- wp:social-links {\"iconColor\":\"white\",\"iconColorValue\":\"#ffffff\",\"iconBackgroundColor\":\"vivid-red\",\"iconBackgroundColorValue\":\"#cf2e2e\",\"openInNewTab\":true,\"size\":\"has-small-icon-size\",\"align\":\"center\",\"className\":\"is-style-default\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\",\"flexWrap\":\"nowrap\"},\"style\":{\"spacing\":{\"blockGap\":{\"top\":\"0px\",\"left\":\"10px\"}}}} --><ul class=\"wp-block-social-links aligncenter has-small-icon-size has-icon-color has-icon-background-color is-style-default\"><!-- wp:social-link {\"url\":\"#\",\"service\":\"facebook\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"linkedin\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"twitter\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"instagram\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"tiktok\"} /--></ul><!-- /wp:social-links --></div><!-- /wp:column --><!-- wp:column {\"width\":\"33.34%\"} --><div class=\"wp-block-column\" style=\"flex-basis:33.34%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://ids.si.edu/ids/deliveryService?id=SAAM-1970.285_1\" alt=\"\"/><figcaption><strong>Mike Daniel Grey - COO</strong></figcaption></figure><!-- /wp:image --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">Mike Daniel Grey is an 81-year-old teenager who enjoys painting, watching television and watching YouTube videos. He is considerate and friendly, but can also be very greedy and a bit unintelligent.</p><!-- /wp:paragraph --><!-- wp:social-links {\"iconColor\":\"white\",\"iconColorValue\":\"#ffffff\",\"iconBackgroundColor\":\"vivid-red\",\"iconBackgroundColorValue\":\"#cf2e2e\",\"openInNewTab\":true,\"size\":\"has-small-icon-size\",\"align\":\"center\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\",\"flexWrap\":\"wrap\"},\"style\":{\"spacing\":{\"blockGap\":{\"top\":\"0px\",\"left\":\"10px\"}}}} --><ul class=\"wp-block-social-links aligncenter has-small-icon-size has-icon-color has-icon-background-color\"><!-- wp:social-link {\"url\":\"#\",\"service\":\"facebook\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"linkedin\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"twitter\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"instagram\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"tiktok\"} /--></ul><!-- /wp:social-links --></div><!-- /wp:column --><!-- wp:column {\"width\":\"33.33%\"} --><div class=\"wp-block-column\" style=\"flex-basis:33.33%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://ids.si.edu/ids/deliveryService?id=NPG-NPG_2015_136\" alt=\"\"/><figcaption><strong>Ruth Sonya Malkovic - CTO</strong></figcaption></figure><!-- /wp:image --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">Ruth Sonya Malkovich is a 23-year-old town counsellor who enjoys listening to music, chess and drone photography. She is exciting and considerate, but can also be very sneaky and a bit lazy.</p><!-- /wp:paragraph --><!-- wp:social-links {\"iconColor\":\"white\",\"iconColorValue\":\"#ffffff\",\"iconBackgroundColor\":\"vivid-red\",\"iconBackgroundColorValue\":\"#cf2e2e\",\"openInNewTab\":true,\"size\":\"has-small-icon-size\",\"align\":\"center\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\",\"flexWrap\":\"wrap\"},\"style\":{\"spacing\":{\"blockGap\":{\"top\":\"0px\",\"left\":\"10px\"}}}} --><ul class=\"wp-block-social-links aligncenter has-small-icon-size has-icon-color has-icon-background-color\"><!-- wp:social-link {\"url\":\"#\",\"service\":\"facebook\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"linkedin\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"twitter\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"instagram\"} /--><!-- wp:social-link {\"url\":\"#\",\"service\":\"tiktok\"} /--></ul><!-- /wp:social-links --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group --><!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-lesson {\"title\":\"Lesson 1\"} /--><!-- wp:sensei-lms/course-outline-lesson {\"title\":\"Lesson 2\"} /--><!-- /wp:sensei-lms/course-outline -->",
			)
		);

		register_block_pattern(
			'sensei-lms/clients',
			array(
				'title'         => __( 'Clients', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => "<!-- wp:heading {\"textAlign\":\"center\",\"level\":5,\"style\":{\"typography\":{\"fontStyle\":\"normal\",\"fontWeight\":\"400\",\"textTransform\":\"uppercase\"}}} --><h5 class=\"has-text-align-center\" id=\"portfolio\" style=\"font-style:normal;font-weight:400;text-transform:uppercase\"><strong>Portfolio</strong></h5><!-- /wp:heading --><!-- wp:heading {\"textAlign\":\"center\",\"style\":{\"typography\":{\"fontStyle\":\"normal\",\"fontWeight\":\"900\"}},\"className\":\"sensei-pattern-title\"} --><h2 class=\"has-text-align-center sensei-pattern-title\" id=\"some-of-our-clients\" style=\"font-style:normal;font-weight:900\">Some of Our Clients</h2><!-- /wp:heading --><!-- wp:paragraph {\"align\":\"center\",\"className\":\"sensei-pattern-description\"} --><p class=\"has-text-align-center sensei-pattern-description\">We're Proud to Have Established Relationships with Thousands of Clients in All Industries. Below represents a small sampling of our clients.</p><!-- /wp:paragraph --><!-- wp:group --><div class=\"wp-block-group\"><!-- wp:columns --><div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"20%\"} --><div class=\"wp-block-column\" style=\"flex-basis:20%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\",\"style\":{\"color\":[]}} --><figure class=\"wp-block-image size-full\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pd43-0204-0005-eye.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=a7b969294a062395edd76dee10211c0f\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {\"width\":\"20%\"} --><div class=\"wp-block-column\" style=\"flex-basis:20%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pd43-0204-0006-eye_0.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=f19a66777643026dbd41f6019a356b32\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {\"width\":\"20%\"} --><div class=\"wp-block-column\" style=\"flex-basis:20%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pd43-0204-0001-eye.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=2ad2604b9a4b8ca42a975f140605656c\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {\"width\":\"20%\"} --><div class=\"wp-block-column\" style=\"flex-basis:20%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pd43-0204-0002-eye.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=dac06ff4f1eb5d9556bbab3a48eb2cfd\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {\"width\":\"20%\"} --><div class=\"wp-block-column\" style=\"flex-basis:20%\"><!-- wp:image {\"sizeSlug\":\"full\",\"linkDestination\":\"none\"} --><figure class=\"wp-block-image size-full\"><img src=\"https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/pd43-0204-0004-eye.jpg?w=1200&amp;h=1200&amp;fit=clip&amp;crop=default&amp;dpr=1&amp;q=75&amp;vib=3&amp;con=3&amp;usm=15&amp;cs=srgb&amp;bg=F4F4F3&amp;ixlib=js-2.2.1&amp;s=89f20088326eb01fbded5dcb2c8e8d1a\" alt=\"\"/></figure><!-- /wp:image --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\">We'd love to add your company to our growing roster of happy customers!</p><!-- /wp:paragraph --><!-- wp:buttons --><div class=\"wp-block-buttons\"><!-- wp:button {\"backgroundColor\":\"white\",\"textColor\":\"black\",\"align\":\"center\",\"className\":\"is-style-outline\"} --><div class=\"wp-block-button aligncenter is-style-outline\"><a class=\"wp-block-button__link has-black-color has-white-background-color has-text-color has-background\"><strong>See all of our Clients</strong></a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-lesson {\"title\":\"Lesson 1\"} /--><!-- wp:sensei-lms/course-outline-lesson {\"title\":\"Lesson 2\"} /--><!-- /wp:sensei-lms/course-outline -->",
			)
		);

		register_block_pattern(
			'sensei-lms/prices',
			array(
				'title'         => __( 'Prices', 'sensei-lms' ),
				'categories'    => [ self::PATTERNS_CATEGORY ],
				'viewportWidth' => 800,
				'content'       => "<!-- wp:columns --><div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"100%\"} --><div class=\"wp-block-column\" style=\"flex-basis:100%\"><!-- wp:columns --><div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"100%\"} --><div class=\"wp-block-column\" style=\"flex-basis:100%\"><!-- wp:columns --><div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"20px\",\"right\":\"10px\",\"bottom\":\"20px\",\"left\":\"10px\"}},\"border\":{\"width\":\"0px\",\"style\":\"none\"}},\"gradient\":\"very-light-gray-to-cyan-bluish-gray\",\"layout\":{\"inherit\":false}} --><div class=\"wp-block-column has-very-light-gray-to-cyan-bluish-gray-gradient-background has-background\" style=\"border-style:none;border-width:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px\"><!-- wp:heading {\"textAlign\":\"center\",\"level\":3,\"textColor\":\"black\",\"fontSize\":\"large\"} --><h3 class=\"has-text-align-center has-black-color has-text-color has-large-font-size\"><strong>Free'</strong></h3><!-- /wp:heading --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\"><strong>$0</strong>/<em>Month</em></p><!-- /wp:paragraph --><!-- wp:separator {\"className\":\"is-style-wide\"} --><hr class=\"wp-block-separator has-alpha-channel-opacity is-style-wide\" /><!-- /wp:separator --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\"><strong>- Lorem Ipsum<br>- Pellentesque malesuada<br>- Maecenas vel velit<br>- Nam molestie<br>- Phasellus in turpis</strong><br><strong>- Nunc ornare enim</strong></p><!-- /wp:paragraph --><!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} --><div class=\"wp-block-buttons\"><!-- wp:button {\"style\":{\"spacing\":{\"padding\":{\"top\":\"10px\",\"right\":\"25px\",\"bottom\":\"10px\",\"left\":\"25px\"}},\"border\":{\"radius\":\"50px\"}},\"className\":\"is-style-fill\"} --><div class=\"wp-block-button is-style-fill\"><a class=\"wp-block-button__link\" style=\"border-radius:50px;padding-top:10px;padding-right:25px;padding-bottom:10px;padding-left:25px\">Buy Now</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column --><!-- wp:column {\"width\":\"\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"20px\",\"right\":\"10px\",\"bottom\":\"20px\",\"left\":\"10px\"}},\"border\":{\"width\":\"0px\",\"style\":\"none\"}},\"gradient\":\"very-light-gray-to-cyan-bluish-gray\",\"layout\":{\"inherit\":false}} --><div class=\"wp-block-column has-very-light-gray-to-cyan-bluish-gray-gradient-background has-background\" style=\"border-style:none;border-width:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px\"><!-- wp:heading {\"textAlign\":\"center\",\"level\":3,\"textColor\":\"black\",\"fontSize\":\"large\"} --><h3 class=\"has-text-align-center has-black-color has-text-color has-large-font-size\"><strong>Premium</strong></h3><!-- /wp:heading --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\"><strong>$50</strong>/<em>Month</em></p><!-- /wp:paragraph --><!-- wp:separator {\"className\":\"is-style-wide\"} --><hr class=\"wp-block-separator has-alpha-channel-opacity is-style-wide\" /><!-- /wp:separator --><!-- wp:paragraph {\"align\":\"center\"} --><p class=\"has-text-align-center\"><strong>- Lorem Ipsum<br>- Pellentesque malesuada<br>- Maecenas vel velit<br>- Nam molestie<br>- Phasellus in turpis</strong><br><strong>- Nunc ornare enim</strong></p><!-- /wp:paragraph --><!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} --><div class=\"wp-block-buttons\"><!-- wp:button {\"style\":{\"spacing\":{\"padding\":{\"top\":\"10px\",\"right\":\"25px\",\"bottom\":\"10px\",\"left\":\"25px\"}},\"border\":{\"radius\":\"50px\"}},\"className\":\"is-style-fill\"} --><div class=\"wp-block-button is-style-fill\"><a class=\"wp-block-button__link\" style=\"border-radius:50px;padding-top:10px;padding-right:25px;padding-bottom:10px;padding-left:25px\">Buy Now</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:column --></div><!-- /wp:columns -->",
			)
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
