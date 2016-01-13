<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Downloads available language packs.
 *
 * The class uses the language configured within your WordPress
 * configuration file.
 *
 * @package Core
 * @author Automattic
 *
 * @since  1.9.0
 */
class Sensei_Language_Pack_Manager {

	/**
	 * Languages repository
	 *
	 * @var string
	 */
	protected static $repo = 'https://github.com/woothemes/sensei-language-packs/raw/';

	/**
	 * Initialize the language pack manager
	 */
	public function __construct() {
		add_action( 'update_option_WPLANG', array( $this, 'updated_language_option' ), 10, 2 );
		add_filter( 'admin_init', array( $this, 'language_package_actions' ), 10 );
	}

	/**
	 * Get translation package URI.
	 *
	 * @param  string $locale
	 *
	 * @return string
	 */
	public static function get_package_uri( $locale ) {
		return self::$repo . Sensei()->version . '/packages/' . $locale . '.zip';
	}

	/**
	 * Get settings URI.
	 *
	 * @param  string $action
	 *
	 * @return string
	 */
	protected static function get_settings_uri( $action ) {
		return wp_nonce_url( admin_url( 'admin.php?page=woothemes-sensei-settings&action=' . $action ), 'language_pack', '_sensei_language_nonce' );
	}

	/**
	 * Get the install language package URI.
	 *
	 * @return string
	 */
	public static function get_install_uri() {
		return self::get_settings_uri( 'language_pack_install' );
	}

	/**
	 * Get the dismiss language package message URI.
	 *
	 * @return string
	 */
	public static function get_dismiss_uri() {
		return self::get_settings_uri( 'dismiss_language_pack_notice' );
	}

	/**
	 * Triggered when WPLANG is changed
	 *
	 * @param string $old
	 * @param string $new
	 */
	public function updated_language_option( $old, $new ) {
		self::has_language_pack_available( $new );
	}

	/**
	 * Check if has available language pack install
	 *
	 * @param  string $locale
	 *
	 * @return bool
	 */
	public static function has_language_pack_available( $locale = null ) {

        if ( is_null( $locale ) ) {

			$locale = get_locale();

		}

		if ( 'en_US' === $locale ) {

			return false;

		}

		if ( 'yes' === get_option( 'sensei_needs_language_pack_install' ) ) {

			return true;

		}

        if( isset( $_GET['translation_updated'] ) && 5 ==  $_GET['translation_updated'] ){

            return false;

        }

		$version = get_option( 'woothemes_sensei_language_pack_version', array( '0', $locale ) );

		if ( ! is_array( $version ) || version_compare( $version[0], Sensei()->version, '<' ) || $version[1] !== $locale ) {
			if ( self::check_if_language_pack_exists( $locale ) ) {
				update_option( 'sensei_needs_language_pack_install', 'yes' );

				return true;
			} else {
				// Updated the woothemes_sensei_language_pack_version to avoid searching translations for this release again
				self::update_language_pack_version( $locale );
			}
		}

		return false;

	}

	/**
	 * Check if language pack exists
	 *
	 * @return bool
	 */
	public static function check_if_language_pack_exists( $locale ) {
		$response = wp_safe_remote_get( self::get_package_uri( $locale ), array( 'timeout' => 60 ) );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update language pack version.
	 *
	 * @param string $locale
	 */
	public static function update_language_pack_version( $locale ) {
		// Update the language pack version
		update_option( 'woothemes_sensei_language_pack_version', array( Sensei()->version, $locale ) );

		// Remove the translation upgrade notice
		update_option( 'sensei_needs_language_pack_install', 'no' );
	}

	/**
	 * Manual language update.
	 */
	public function language_package_actions() {
		if (
			is_admin()
			&& current_user_can( 'update_plugins' )
			&& isset( $_GET['page'] )
			&& 'woothemes-sensei-settings' === $_GET['page']
			&& isset( $_GET['action'] )
		) {

			if ( 'language_pack_install' === $_GET['action'] ) {
				$this->language_pack_install();
			}

			if ( 'dismiss_language_pack_notice' ) {
				$this->dismiss_language_pack_notice();
			}
		}
	}

	/**
	 * Install language pack.
	 */
	protected function language_pack_install() {
		$url          = wp_nonce_url( admin_url( 'admin.php?page=woothemes-sensei-settings&action=language_pack_install' ), 'language_install' );
		$settings_url = admin_url( 'admin.php?page=woothemes-sensei-settings' );
		$locale       = get_locale();

		if ( ! isset( $_REQUEST['_sensei_language_nonce'] ) && wp_verify_nonce( $_REQUEST['_sensei_language_nonce'], 'language_pack' ) ) {
			wp_redirect( add_query_arg( array( 'translation_updated' => 2 ), $settings_url ) );
			exit;
		}

		if ( 'en_US' === $locale || ! self::check_if_language_pack_exists( $locale ) ) {
			wp_redirect( add_query_arg( array( 'translation_updated' => 3 ), $settings_url ) );
			exit;
		}

		if ( false === ( $creds = request_filesystem_credentials( $url, '', false, false, null ) ) ) {
			wp_redirect( add_query_arg( array( 'translation_updated' => 4 ), $settings_url ) );
			exit;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, false, null );

			wp_redirect( add_query_arg( array( 'translation_updated' => 4 ), $settings_url ) );
			exit;
		}

		// Download the language pack
		$response = wp_safe_remote_get( self::get_package_uri( $locale ), array( 'timeout' => 60 ) );
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			global $wp_filesystem;

			$upload_dir = wp_upload_dir();
			$file       = trailingslashit( $upload_dir['path'] ) . $locale . '.zip';

			// Save the zip file
			if ( ! $wp_filesystem->put_contents( $file, $response['body'], FS_CHMOD_FILE ) ) {
				wp_redirect( add_query_arg( array( 'translation_updated' => 4 ), $settings_url ) );
				exit;
			}

			// Unzip the file to wp-content/languages/plugins directory
			$dir   = trailingslashit( WP_LANG_DIR ) . 'plugins/';
			$unzip = unzip_file( $file, $dir );
			if ( true !== $unzip ) {
				wp_redirect( add_query_arg( array( 'translation_updated' => 4 ), $settings_url ) );
				exit;
			}

			// Delete the package file
			$wp_filesystem->delete( $file );

			// Update version and remove notice
			self::update_language_pack_version( $locale );

			// Redirect and show a success message
			wp_redirect( add_query_arg( array( 'translation_updated' => 1 ), $settings_url ) );
			exit;
		} else {
			// Don't have a valid package for the current language!
			wp_redirect( add_query_arg( array( 'translation_updated' => 5 ), $settings_url ) );
			exit;
		}
	}

	/**
	 * Hide language pack notice.
	 */
	protected function dismiss_language_pack_notice() {
		if ( ! isset( $_REQUEST['_sensei_language_nonce'] ) && wp_verify_nonce( $_REQUEST['_sensei_language_nonce'], 'language_pack' ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woothemes-sensei' ) );
		}

		// Update version and remove notice
		self::update_language_pack_version( get_locale() );
	}

	/**
	 * Language pack messages
	 */
	public static function messages() {
		if ( empty( $_GET['translation_updated'] ) ) {
			return;
		}

		switch ( $_GET['translation_updated'] ) {
			case 2 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woothemes-sensei' ) . ' ' . __( 'Seems you don\'t have permission to do this!', 'woothemes-sensei' ) . '</p></div>';
				break;
			case 3 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woothemes-sensei' ) . ' ' . __( 'There is no translation available for your language!', 'woothemes-sensei' ) . '</p></div>';
				break;
			case 4 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woothemes-sensei' ) . ' ' . sprintf( __( 'An authentication error occurred while updating the translation. Please try again or configure your %sUpgrade Constants%s.', 'woothemes-sensei' ), '<a href="http://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants">', '</a>' ) . '</p></div>';
				break;
			case 5 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woothemes-sensei' ) . ' ' . __( 'Sorry but there is no translation available for your language =/', 'woothemes-sensei' ) . '</p></div>';
				break;

			default :
				echo '<div class="updated"><p>' . __( 'Translations installed/updated successfully!', 'woothemes-sensei' ) . '</p></div>';
				break;
		}
	}
}

new Sensei_Language_Pack_Manager();
