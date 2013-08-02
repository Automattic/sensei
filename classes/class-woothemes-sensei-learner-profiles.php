<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Learner Profiles Class
 *
 * All functionality pertaining to the learner profiles in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.4.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - setup_permastruct()
 * - learner_profile_content()
 * - learner_profile_courses_heading()
 * - learner_profile_user_info()
 * - learner_profile_menu_item()
 */
class WooThemes_Sensei_Learner_Profiles {
	private $profile_url_base;
	public $token;

	/**
	 * Constructor.
	 * @since  1.4.0
	 */
	public function __construct () {
		global $woothemes_sensei;

		// Setup learner profile URL base
		$this->profile_url_base = apply_filters( 'sensei_learner_profiles_url_base', __( 'learner', 'woothemes-sensei') );

		// Setup permalink structure for learner profiles
		add_action( 'init', array( $this, 'setup_permastruct' ) );

		// Load content for learner profiles
		add_action( 'sensei_learner_profile_content', array( $this, 'content' ), 10 );

		// Load user info on learner profiles
		add_action( 'sensei_learner_profile_info', array( $this, 'learner_profile_user_info' ), 10, 1 );

		// Set heading for courses section of learner profiles
		add_action( 'sensei_before_learner_course_content', array( $this, 'learner_profile_courses_heading' ), 10, 1 );

		// Add profile link to main navigation
		add_filter( 'wp_nav_menu_items', array( $this, 'learner_profile_menu_item' ), 11, 2 );
	} // End __construct()

	/**
	 * Setup permalink structure for learner profiles
	 * @since  1.4.0
	 * @return void
	 */
	public function setup_permastruct() {
		global $woothemes_sensei;

		$allow_public_profiles = $woothemes_sensei->settings->settings[ 'learner_profile_enable' ];

		if( isset( $allow_public_profiles ) && $allow_public_profiles ) {
			add_rewrite_rule( '^' . $this->profile_url_base . '/([^/]*)/?', 'index.php?learner_profile=true&learner_username=$matches[1]', 'top' );
			add_rewrite_tag( '%learner_username%', '([^&]+)' );
		}
	}

	/**
	 * Load content for learner profiles
	 * @since  1.4.0
	 * @return void
	 */
	public function content() {
		global $wp_query, $woothemes_sensei, $learner_user, $current_user;

		if( isset( $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) && $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) {

			if( isset( $wp_query->query_vars['learner_username'] ) && username_exists( $wp_query->query_vars['learner_username'] ) ) {

				// Get user object for learner
				$learner_user = get_user_by( 'login', $wp_query->query_vars['learner_username'] );

				if( ! is_wp_error( $learner_user ) ) {
					$woothemes_sensei->frontend->sensei_get_template( 'learner-profile/learner-info.php' );
				}
			}
		}
	}

	/**
	 * Set heading for courses section of learner profiles
	 * @since  1.4.0
	 * @param  object $user Queried user object
	 * @return void
	 */
	public function learner_profile_courses_heading( $user ) {
		if( strlen( $user->first_name ) > 0 ) {
			$name = $user->first_name;
		} else {
			$name = $user->display_name;
		}
		$name = apply_filters( 'sensei_learner_profile_courses_heading_name', $name );
		echo '<h2>' . apply_filters( 'sensei_learner_profile_courses_heading', sprintf( __( 'Courses %s is taking', 'woothemes-sensei' ), $name ) ) . '</h2>';
	}

	/**
	 * Load user info for learner profiles
	 * @since  1.4.0
	 * @param  object $user Queried user object
	 * @return void
	 */
	public function learner_profile_user_info( $user ) {
		$learner_avatar = apply_filters( 'sensei_learner_profile_info_avatar', get_avatar( $user->ID, 120 ), $user->ID );
		$learner_name = apply_filters( 'sensei_learner_profile_info_name', $user->display_name, $user->ID );
		$learner_bio = apply_filters( 'sensei_learner_profile_info_bio', $user->description, $user->ID );
		?>
		<div id="learner-info">
			<div class="learner-avatar"><?php echo $learner_avatar; ?></div>
			<div class="learner-content">
				<h2><?php echo $learner_name; ?></h2>
				<div class="description"><?php echo $learner_bio; ?></div>
			</div>
			<div class="fix"></div>
		</div>
		<?php
	}

	/**
	 * Add learner profile link to main navigation
	 * @since  1.4.0
	 * @param  string $items Current menu items
	 * @param  array  $args
	 * @return string        Modified menu items
	 */
	public function learner_profile_menu_item( $items, $args ) {
		global $woothemes_sensei, $wp_query, $current_user;

		$allow_public_profiles = $woothemes_sensei->settings->settings[ 'learner_profile_enable' ];

		if( isset( $allow_public_profiles ) && $allow_public_profiles ) {

			if( is_user_logged_in() ) {
				$add_menu_item = $woothemes_sensei->settings->settings[ 'learner_profile_menu_link' ];

				if( isset( $add_menu_item ) && $add_menu_item ) {

					// Get User Meta
					get_currentuserinfo();

					$profile_url = trailingslashit( get_site_url() ) . $this->profile_url_base . '/' . $current_user->user_login;
					$classes = '';
					if ( isset( $wp_query->query_vars['learner_username'] ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_learner_profile_menu_link', '<li class="learner-profile' . $classes . '"><a href="'. esc_url( $profile_url ) .'">' . apply_filters( 'sensei_learner_profile_menu_link_text', __( 'My Profile', 'woothemes-sensei' ) ) . '</a></li>' );
				} // End If Statement
			}
		}

		return apply_filters( 'sensei_custom_menu_links', $items );
	}

} // End Class
?>