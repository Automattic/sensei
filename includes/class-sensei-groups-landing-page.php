<?php
/**
 * File Sensei_Groups_Landing_Page class.
 *
 * @package sensei-lms
 * @since 4.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Student Groups Landing Page.
 *
 * Displaying landing page for Sensei pro groups feature.
 *
 * @since 4.5.2
 */
class Sensei_Groups_Landing_Page {

	/**
	 * Add student groups menu item and display student groups landing page.
	 *
	 * @access public
	 * @since 4.5.2
	 */
	public function add_groups_landing_page_menu_item() {

		// Add new menu item.
		$menu_item_title = __( 'Groups', 'sensei-lms' );
		$badge_text      = __( 'Pro', 'sensei-lms' );
		add_submenu_page(
			'sensei',
			__( 'Groups', 'sensei-lms' ),
			// Translators: first placeholder value is menu item title, second is badge text.
			sprintf( '%s <span class="awaiting-mod sensei-promo-groups__badge">%s</span>', $menu_item_title, $badge_text ),
			'edit_courses',
			'student_groups',
			[ $this, 'display_student_groups_landing_page' ]
		);
	}

	/**
	 * Display student groups promo landing page.
	 *
	 * @access public
	 * @since 4.5.2
	 */
	public function display_student_groups_landing_page() {
		// Get the price of Pro. Return if it's not available.
		$sensei_pro_product = Sensei_Extensions::instance()->get_extension( Sensei_Extensions::PRODUCT_SENSEI_PRO_SLUG );
		$sensei_pro_price   = $sensei_pro_product ? str_replace( '.00', '', $sensei_pro_product->price ) : '-';

		// Enqueue styles.
		Sensei()->assets->enqueue( 'sensei-settings-api', 'css/settings.css' );

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
			<h1 class="sensei-promo-groups__header">
					<?php echo esc_html( __( 'Get Sensei Pro', 'sensei-lms' ) ); ?>
			</h1>
			<p class="sensei-promo-groups__body">
				<?php echo esc_html( __( 'Unlock Groups by upgrading to Sensei Pro and get features like:', 'sensei-lms' ) ); ?>
			</p>
			<ul class="sensei-promo-groups__list">
				<li class="sensei-promo-groups__list_item">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'Assign students per group', 'sensei-lms' ) ); ?></span>
				</li>
				<li class="sensei-promo-groups__list_item">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'Automatically enroll groups to defined courses', 'sensei-lms' ) ); ?></span>
				</li>
				<li class="sensei-promo-groups__list_item">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'Set Access Period for courses in a group', 'sensei-lms' ) ); ?></span>
				</li>
				<li class="sensei-promo-groups__list_item">
					<span class="dashicons dashicons-saved"></span>
					<span><?php echo esc_html( __( 'All the Sensei Pro features', 'sensei-lms' ) ); ?></span>
				</li>
			</ul>
			<h3 class="sensei-promo-groups__important-info">
			<?php
				// translators: Placeholder is the price of Sensei Pro.
				echo esc_html( sprintf( __( '%s USD', 'sensei-lms' ), $sensei_pro_price ) );
			?>
			<span class="sensei-promo-groups__price-period"><?php esc_html_e( 'per year, 1 site', 'sensei-lms' ); ?></span>
			</h3>
			<div class="sensei-promo-groups__actions">
				<a
				class="button button-primary sensei-promo-groups__primary-action"
				href="<?php echo esc_url( 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=groups' ); ?>"
				target="_blank"
				>
				<?php echo esc_html( __( 'Get Sensei Pro', 'sensei-lms' ) ); ?>
			</a>
			<a	class="sensei-promo-groups__secondary_action"
				href="<?php echo esc_url( 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=groups#features' ); ?>"
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
	 * @since 4.5.2
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
