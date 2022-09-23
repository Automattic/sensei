<?php
/**
 * File containing Sensei_Home_Quick_Links_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class responsible for generating the Quick Links structure for Sensei Home screen.
 */
class Sensei_Home_Quick_Links_Provider {

	/**
	 * Return a list of categories which each contain multiple quick link items.
	 *
	 * @return Sensei_Home_Quick_Links_Category[]
	 */
	public function get(): array {
		return [
			new Sensei_Home_Quick_Links_Category(
				__( 'Courses', 'sensei-lms' ),
				[
					new Sensei_Home_Quick_Links_Item( __( 'Create a Course', 'sensei-lms' ), admin_url( '/post-new.php?post_type=course' ) ),
					new Sensei_Home_Quick_Links_Item( __( 'Import a Course', 'sensei-lms' ), admin_url( '/edit.php?post_type=course&page=sensei-tools&tool=import-content' ) ),
					new Sensei_Home_Quick_Links_Item( __( 'Reports', 'sensei-lms' ), admin_url( '/edit.php?post_type=course&page=sensei_reports' ) ),
				]
			),
			new Sensei_Home_Quick_Links_Category(
				__( 'Settings', 'sensei-lms' ),
				[
					new Sensei_Home_Quick_Links_Item( __( 'Email notifications', 'sensei-lms' ), admin_url( '/edit.php?post_type=course&page=sensei-settings#email-notification-settings' ) ),
					new Sensei_Home_Quick_Links_Item( __( 'Learning Mode', 'sensei-lms' ), admin_url( '/edit.php?post_type=course&page=sensei-settings#course-settings' ) ),
					new Sensei_Home_Quick_Links_Item( __( 'WooCommerce', 'sensei-lms' ), admin_url( '/edit.php?post_type=course&page=sensei-settings#woocommerce-settings' ) ),
					new Sensei_Home_Quick_Links_Item( __( 'Content Drip', 'sensei-lms' ), admin_url( '/edit.php?post_type=course&page=sensei-settings#sensei-content-drip-settings' ) ),
				]
			),
			new Sensei_Home_Quick_Links_Category(
				__( 'Advanced Features', 'sensei-lms' ),
				[
					new Sensei_Home_Quick_Links_Item( __( 'Interactive Blocks', 'sensei-lms' ), 'https://senseilms.com/interactive-blocks' ),
					new Sensei_Home_Quick_Links_Item( __( 'Groups & Cohorts', 'sensei-lms' ), 'https://senseilms.com/groups-cohorts' ),
					new Sensei_Home_Quick_Links_Item( __( 'Quizzes', 'sensei-lms' ), 'https://senseilms.com/quizzes' ),
					new Sensei_Home_Quick_Links_Item( __( 'Integrations', 'sensei-lms' ), 'https://senseilms.com/sensei-lms-integrations/' ),
				]
			),
		];
	}

}
