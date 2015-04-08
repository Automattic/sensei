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
		add_filter( 'wp_title', array( $this, 'page_title' ), 10, 2 );

		// Load content for learner profiles
		add_action( 'sensei_learner_profile_content', array( $this, 'content' ), 10 );

		// Load user info on learner profiles
		add_action( 'sensei_learner_profile_info', array( $this, 'learner_profile_user_info' ), 10, 1 );

		// Set heading for courses section of learner profiles
		add_action( 'sensei_before_learner_course_content', array( $this, 'learner_profile_courses_heading' ), 10, 1 );

		// Add class to body tag
		add_filter( 'body_class', array( $this, 'learner_profile_body_class' ), 10, 1 );
	} // End __construct()

	/**
	 * Setup permalink structure for learner profiles
	 * @since  1.4.0
	 * @return void
	 */
	public function setup_permastruct() {
		global $woothemes_sensei;

		if( isset( $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) && $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) {
			add_rewrite_rule( '^' . $this->profile_url_base . '/([^/]*)/?', 'index.php?learner_profile=$matches[1]', 'top' );
			add_rewrite_tag( '%learner_profile%', '([^&]+)' );
		}
	}

	/**
	 * Adding page title for course results page
	 * @param  string $title Original title
	 * @param  string $sep   Seeparator string
	 * @return string        Modified title
	 */
	public function page_title( $title, $sep = null ) {
		global $wp_query, $woothemes_sensei;
		if( isset( $wp_query->query_vars['learner_profile'] ) ) {
			$learner_user = get_user_by( 'login', $wp_query->query_vars['learner_profile'] );

            $name = $woothemes_sensei->learners->get_learner_full_name( $learner_user->ID );

			$title = apply_filters( 'sensei_learner_profile_courses_heading', sprintf( __( 'Courses %s is taking', 'woothemes-sensei' ), $name ) ) . ' ' . $sep . ' ';
		}
		return $title;
	}

	/**
	 * Get permalink for learner profile
	 * @since  1.4.0
	 * @param  integer $user_id ID of user
	 * @return string           The learner profile permalink
	 */
	public function get_permalink( $user_id = 0 ) {
		$user = false;
		if( $user_id == 0 ) {
			global $current_user;
			wp_get_current_user();
			$user = $current_user;
		} else {
			$user = get_userdata( $user_id );
		}

		$permalink = '';

		if( $user ) {
			if ( get_option('permalink_structure') ) {
				$permalink = trailingslashit( get_site_url() ) . $this->profile_url_base . '/' . $user->user_nicename;
			} else {
				$permalink = trailingslashit( get_site_url() ) . '?learner_profile=' . $user->user_nicename;
			}
		}

		return $permalink;
	}

	/**
	 * Load content for learner profiles
	 * @since  1.4.0
	 * @return void
	 */
	public function content() {
		global $wp_query, $woothemes_sensei, $learner_user, $current_user;

		if( isset( $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) && $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) {

			if( isset( $wp_query->query_vars['learner_profile'] ) ) {

				// Get user object for learner
				$learner_user = get_user_by( 'slug', $wp_query->query_vars['learner_profile'] );

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
				<div class="description"><?php echo wpautop( $learner_bio ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adding class to body tag
	 * @param  array $classes Existing classes
	 * @return array          Modified classes
	 */
	public function learner_profile_body_class( $classes ) {
		global $wp_query;
		if( isset( $wp_query->query_vars['learner_profile'] ) ) {
			$classes[] = 'learner-profile';
		}
		return $classes;
	}

} // End Class