<?php
/**
 * File containing Sensei_Home_Help_Provider class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

/**
 * Class responsible for generating the Help structure for Sensei Home screen.
 */
class Sensei_Home_Help_Provider {

	/**
	 * Return a list of categories which each contain multiple help items.
	 *
	 * @return array[]
	 */
	public function get(): array {
		return [
			$this->create_category(
				__( 'Get the most out of Sensei', 'sensei-lms' ),
				[

					$this->create_item( __( 'Sensei documentation', 'sensei-lms' ), 'https://senseilms.com/docs/' ),
					$this->create_item( __( 'Support forums', 'sensei-lms' ), 'https://wordpress.org/support/plugin/sensei-lms/' ),
					$this->create_support_ticket_item(),
				]
			),
		];
	}

	/**
	 * Create category array structure.
	 *
	 * @param string  $title The category title.
	 * @param array[] $items The items in the category.
	 * @return array
	 */
	private function create_category( $title, $items ) {
		return [
			'title' => $title,
			'items' => $items,
		];
	}

	/**
	 * Create item array structure.
	 *
	 * @param string      $title The item title.
	 * @param string|null $url Optional. Action url.
	 * @param string|null $icon Optional. Icon to be used.
	 * @param array|null  $extra_link Optional. An extra link normally used to explain why the action url is missing.
	 *
	 * @return array
	 */
	private function create_item( string $title, ?string $url = null, ?string $icon = null, ?array $extra_link = null ) {
		return [
			'title'      => $title,
			'url'        => $url,
			'icon'       => $icon,
			'extra_link' => $extra_link,
		];
	}

	/**
	 * Create extra link array structure.
	 *
	 * @param string $label The label for the link.
	 * @param string $url The url for the link.
	 *
	 * @return array
	 */
	private function create_extra_link( string $label, string $url ) {
		return [
			'label' => $label,
			'url'   => $url,
		];
	}

	/**
	 * Generates the item to create a support ticket or a CTA if Sensei Pro is not installed.
	 *
	 * @return array
	 */
	private function create_support_ticket_item() {
		$url        = null;
		$extra_link = null;
		$icon       = null;

		/**
		 * Filter to disable upsell to Sensei Pro in Sensei Home's action to create support tickets.
		 *
		 * @hook sensei_home_support_ticket_creation_upsell_show
		 * @since 4.8.0
		 *
		 * @param {bool} $show_upsell True if upsell must be shown.
		 *
		 * @return {bool}
		 */
		if ( apply_filters( 'sensei_home_support_ticket_creation_upsell_show', true ) ) {
			$extra_link = $this->create_extra_link( __( 'Upgrade to Sensei Pro', 'sensei-lms' ), 'https://senseilms.com/pricing/' );
			$icon       = 'lock';
		} elseif ( Sensei_Utils::has_wpcom_subscription() ) {
			$url = 'https://wordpress.com/help/contact';
		} else {
			$url = 'https://senseilms.com/contact/';
		}

		return $this->create_item( __( 'Create a support ticket', 'sensei-lms' ), $url, $icon, $extra_link );
	}

}
