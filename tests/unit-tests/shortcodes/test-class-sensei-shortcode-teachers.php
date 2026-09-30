<?php

/**
 * Tests for Sensei_Shortcode_Teachers class.
 *
 * @group shortcodes
 */
class Sensei_Shortcode_Teachers_Test extends WP_UnitTestCase {

	public function testRender_TeacherNameContainingMarkupGiven_EscapesTheNameAndClosesTheAnchor() {
		/* Arrange */
		$user_id = $this->factory->user->create( array( 'role' => 'teacher' ) );

		/*
		 * Written straight to user meta: `wp_insert_user()` runs these fields through the core
		 * `pre_user_*` filters, which strip the markup. Writing the meta directly reproduces the
		 * paths that skip those filters (other plugins, importers, wp-cli), which is the vector.
		 */
		update_user_meta( $user_id, 'first_name', '<img src=x onerror=alert(1)>' );
		update_user_meta( $user_id, 'last_name', 'Doe' );

		/* Act */
		$output = ( new Sensei_Shortcode_Teachers( array(), '', 'sensei_teachers' ) )->render();

		/* Assert */
		$expected = '<li class="teacher"><a href="' . esc_url( get_author_posts_url( $user_id ) ) . '">'
			. '&lt;img src=x onerror=alert(1)&gt; Doe</a></li>';
		self::assertStringContainsString( $expected, $output );
	}
}
