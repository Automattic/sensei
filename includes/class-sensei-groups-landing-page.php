<?php
/**
 * File Sensei_Groups_Landing_Page class.
 *
 * @package sensei-lms
 * @since x.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Student Groups Landing Page.
 *
 * Displaying landing page for Sensei pro groups feature.
 *
 * @since x.x.x
 */
class Sensei_Groups_Landing_Page {

	/**
	 * Add student groups menu item and display student groups landing page.
	 *
	 * @access public
	 * @since x.x.x
	 */
	public function add_groups_landing_page_menu_item() {
		Sensei()->assets->enqueue( 'sensei-settings-api', 'css/settings.css' );

		add_submenu_page(
			'edit.php?post_type=course',
			__( 'Groups', 'sensei-lms' ),
			__( 'Groups', 'sensei-lms' ),
			'edit_courses',
			'student_groups',
			[ $this, 'display_student_groups_landing_page' ]
		);
	}

	/**
	 * Display student groups promo landing page.
	 *
	 * @access public
	 * @since x.x.x
	 */
	public function display_student_groups_landing_page() {

		$this->wrapper_container( 'top' );

		$image_path_desktop = Sensei()->assets->get_image( 'groups-promo-desktop.png' );
		$image_path_mobile  = Sensei()->assets->get_image( 'groups-promo-mobile.png' );

		?>
		<div class="student-groups__header">
			<h1>
				<?php echo esc_html( __( 'Groups', 'sensei-lms' ) ); ?>
			</h1>
		</div>
		<div class='sensei-promo-groups__wrapper'>
		<div class="sensei-promo-groups__content-wrapper">
			<span class="sensei-promo-groups__header">
					<?php echo esc_html( __( 'Get Sensei Pro', 'sensei-lms' ) ); ?>
			</span>
			<span class="sensei-promo-groups__body">
				<?php echo esc_html( __( 'Unlock Groups by upgrading to Sensei Pro and get features like:', 'sensei-lms' ) ); ?>
			</span>
			<span class="sensei-promo-groups__body">
				<div class="sensei-promo-groups__body-list">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'Assign students per group', 'sensei-lms' ) ); ?></span>
				</div>
				<div class="sensei-promo-groups__body-list">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'Automatically enroll groups to defined courses', 'sensei-lms' ) ); ?></span>
				</div>
				<div class="sensei-promo-groups__body-list">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'Set Access Period for courses in a group', 'sensei-lms' ) ); ?></span>
				</div>
				<div class="sensei-promo-groups__body-list">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'All the Sensei Pro features', 'sensei-lms' ) ); ?></span>
				</div>
			</span>
			<span class="sensei-promo-groups__important-info">
					<?php echo esc_html( __( '$149.00 USD / year (1 site)', 'sensei-lms' ) ); ?>
			</span>
			<div class="sensei-promo-groups__actions">
				<a
				class="button button-primary sensei-promo-groups__primary-action"
				href="<?php echo esc_url( 'https://senseilms.com/pricing/' ); ?>"
				target="_blank"
				>
				<?php echo esc_html( __( 'Get Sensei Pro', 'sensei-lms' ) ); ?>
			</a>
			<a	class="sensei-promo-groups__secondary_action"
				href="<?php echo esc_url( 'https://senseilms.com/pricing/' ); ?>"
				target="_blank"
			>
				<?php echo esc_html( __( 'See all Sensei Pro Features', 'sensei-lms' ) ); ?>
			</a>
			</div>
		</div>
			<picture>
				<source media="(max-width:1007px)" srcset="<?php echo esc_url( $image_path_mobile ); ?>">
				<img class="sensei-promo-groups__image" src="<?php echo esc_url( $image_path_desktop ); ?>" alt="sensei-banner">
			</picture>
		</div>
		<?php

		$this->wrapper_container( 'bottom' );

	}

	/**
	 * Wrapper container wrapper_container wrapper.
	 *
	 * @access public
	 * @since  x.x.x
	 *
	 * @param string $which which wrapper top or bottom.
	 */
	public function wrapper_container( $which ) {
		if ( 'top' === $which ) {
			?>
			<div id="woothemes-sensei" class="wrap woothemes-sensei">
			<?php
		} elseif ( 'bottom' === $which ) {
			?>
			</div><!--/#woothemes-sensei-->
			<?php
		}
	}
}
