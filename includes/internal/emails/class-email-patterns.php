<?php
/**
 * Email Patterns.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

/**
 * Email Patterns class.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Patterns {

	public function __construct() {}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init() {
		add_action( 'current_screen', [ $this, 'register_email_editor_block_patterns' ] );
		add_action( 'init', [ $this, 'register_block_patterns_category' ] );
	}

	/**
	 * Register Sensei block patterns category.
	 *
	 * @access private
	 */
	public function register_block_patterns_category() {
		register_block_pattern_category(
			'sensei-emails',
			[ 'label' => __( 'Sensei Emails', 'sensei-lms' ) ]
		);
	}

	/**
	 * Register block patterns.
	 *
	 * @access private
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_Screen $current_screen Current screen.
	 */
	public function register_email_editor_block_patterns( $current_screen ) {
		$post_type = $current_screen->post_type;

		if ( 'sensei_email' === $post_type ) {
			$this->register_email_block_patterns();
		}
	}

	/**
	 * Register email block patterns.
	 *
	 * @access private
	 *
	 * @since $$next-version$$
	 */
	public function register_email_block_patterns() {
		$patterns = [
			'student-completes-course' =>
				[
					'title'      => __( 'Email sent to teacher after a student completes a course', 'sensei-lms' ),
					'categories' => [ 'sensei-emails' ],
					'content'    => '<!-- wp:post-title {"style":{"typography":{"textTransform":"capitalize","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#020202"}}} /-->

<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"blockGap":"0px","padding":{"top":"40px","right":"40px","bottom":"40px","left":"40px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="background-color:#f6f7f7;padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"32px"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#010101"}}} -->
<p class="has-text-color" style="color:#010101;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:32px"><strong>[student:displayname]</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;margin-top:24px;margin-bottom:0px;font-size:16px"><strong>Course Name</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;font-size:16px">[course:name]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;margin-top:24px;margin-bottom:0px;font-size:16px"><strong>Your Grade</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"bottom":"40px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;margin-bottom:40px;font-size:24px"><strong>[grade:percentage]</strong></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#020202","text":"#fefefe"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="[manage.students]" style="color:#fefefe;background-color:#020202;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Manage Students</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
				],
		];

		foreach ( $patterns as $key => $pattern ) {
			register_block_pattern(
				'sensei-lms/' . $key,
				$pattern
			);
		}
	}
}
