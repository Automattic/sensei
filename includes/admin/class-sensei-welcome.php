<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Shows a feature overview for the new version (major)
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WooCommerce and WP
 *
 * @package Views
 * @author Automattic
 *
 * @version     1.8.0
*/
class Sensei_Welcome {

	/**
	 * Hook in tabs.
     * @since 1.8.0
	 */
	public function __construct() {

        add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );

	}

    /**
     * Sends user to the welcome page on first activation.
     *
     * Hooked into activated_plugin
     * @since 1.8.0
     */
    public static function redirect( $plugin ) {

        // Bail if activating from network, or bulk, or within an iFrame
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {

            return;

        }
        // don't run for upgrades and for pages already on the welcome screen
        if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] )
            || ( ! empty( $_GET['page'] ) && $_GET['page'] === 'sensei-welcome' ) ) {

            return;

        }

        wp_redirect( admin_url( 'index.php?page=sensei-welcome' ) );
        exit;
    }

	/**
	 * Add admin menus/screens.
     *
     * @since 1.8.0
	 */
	public function admin_menus() {

		if ( ! isset( $_GET['page'] ) || 'sensei-welcome' != $_GET[ 'page' ] ) {
			return;
		}

		$welcome_page_name  = __( 'Sensei Activation', 'woothemes-sensei' );
		$welcome_page_title = __( 'Welcome to Sensei', 'woothemes-sensei' );
        add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'sensei-welcome', array( $this, 'welcome_screen' ) );

	}

    /**
     * Output the Welcome  screen.
     *
     * @since 1.8.0
     */
    public function welcome_screen() {
        ?>
        <div class="wrap about-wrap">

            <?php  $this->intro(); ?>

            <div class="feature-section two-col">
						<div class="col">
							<div class="media-container">
								<img src="https://docs.woothemes.com/wp-content/uploads/2015/12/Screen-Shot-2015-12-01-at-15.25.24.png">
							</div>
						</div>
						<div class="col">
							<h3>New Sensei Shortcodes</h3>
							<p>Display your Sensei courses, teachers and messages wherever you want them with a selection of new shortcodes, giving you much more control over the display of your Sensei content.</p>
						</div>
			</div>
			<div class="feature-section two-col">
						<div class="col">
							<div class="media-container">
								<img src="https://docs.woothemes.com/wp-content/uploads/2015/12/language.jpg">
							</div>
						</div>
						<div class="col">
							<h3>Sensei in Your Language</h3>
							<p>Language packs can now be downloaded directly from the dashboard, enabling one-click translation updates.</p>
						</div>
			</div>

            <div class="feature-section three-col">
						<div class="col">
				<div class="media-container">
										<img src="https://docs.woothemes.com/wp-content/uploads/2015/12/next.png">
									</div>
				<h3>Intuitive Lesson Navigation</h3>
				<p>When you complete a lesson, you will now see an obvious link to guide you to the next lesson.</p>
			</div>
						<div class="col">
				<div class="media-container">
										<img src="https://docs.woothemes.com/wp-content/uploads/2015/12/zero.png">
									</div>
				<h3>Zero-grade Questions</h3>
				<p>Not all questions require a grade. Now you have the option to assign a grade of zero to any question.</p>
			</div>
						<div class="col">
				<div class="media-container">
										<img src="https://docs.woothemes.com/wp-content/uploads/2015/12/templates.png">
									</div>
				<h3>Updated Template System</h3>
				<p>A revised and optimized template system, including a new default Course Archive page, complete with filters for quickly displaying free, paid and featured courses.</p>
			</div>
					</div>

            <hr />

            <div class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'woothemes-sensei-settings' ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to Sensei Settings', 'woothemes-sensei' ); ?></a>
            </div>
        </div>
    <?php
    }

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 */
	public function admin_head() {
        // remove the menu page so it is not visible in the admin side bar
		remove_submenu_page( 'index.php', 'sensei-welcome' );
		?>
		<style type="text/css">
			/*<![CDATA[*/
			.sensei-badge:before {
				font-family: dashicons !important;
				content: "\f118";
				color: #fff;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				font-size: 80px;
				font-weight: normal;
				width: 165px;
				height: 165px;
				line-height: 165px;
				text-align: center;
				position: absolute;
				top: 0;
				<?php echo is_rtl() ? 'right' : 'left'; ?>: 0;
				margin: 0;
				vertical-align: middle;
			}
            .sensei-badge {
                position: relative;
                background: #71b02f;
                text-rendering: optimizeLegibility;
                padding-top: 150px;
                height: 52px;
                width: 165px;
                font-weight: 600;
                font-size: 14px;
                text-align: center;
                color: rgba(255,255,255,0.8);
                text-shadow: 0.05em 0.05em 1px rgba(0,0,0,0.1);
                margin: 5px 0 0 0;
                -webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2);
                box-shadow: 0 1px 3px rgba(0,0,0,.2);
            }
			.about-wrap .sensei-badge {
				position: absolute;
				top: 0;
				<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
			}
			.about-wrap .sensei-feature {
				overflow: visible !important;
				*zoom:1;
			}
			.about-wrap h3 + .sensei-feature {
				margin-top: 0;
			}
			.about-wrap .sensei-feature:before,
			.about-wrap .sensei-feature:after {
				content: " ";
				display: table;
			}
			.about-wrap .sensei-feature:after {
				clear: both;
			}
			.about-wrap .feature-rest div {
				width: 50% !important;
				padding-<?php echo is_rtl() ? 'left' : 'right'; ?>: 100px;
				-moz-box-sizing: border-box;
				box-sizing: border-box;
				margin: 0 !important;
			}
			.about-wrap .feature-rest div.last-feature {
				padding-<?php echo is_rtl() ? 'right' : 'left'; ?>: 100px;
				padding-<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
			}
			.about-wrap div.icon {
				width: 0 !important;
				padding: 0;
				margin: 20px 0 !important;
			}
			.about-wrap .feature-rest div.icon:before {
				font-weight: normal;
				width: 100%;
				font-size: 170px;
				line-height: 125px;
				color: #9c5d90;
				display: inline-block;
				position: relative;
				text-align: center;
				speak: none;
				margin: <?php echo is_rtl() ? '0 -100px 0 0' : '0 0 0 -100px'; ?>;
				content: "\e01d";
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}
			.about-integrations {
				background: #fff;
				margin: 20px 0;
				padding: 1px 20px 10px;
			}
			.changelog h4 {
				line-height: 1.4;
			}

            p.sensei-actions .twitter-share-button {
				margin-top: -3px;
				margin-left: 3px;
				vertical-align: middle;
			}
			/*]]>*/
		</style>
		<?php
	}

	/**
	 * Intro text/links shown on all about pages.
	 */
	private function intro() {

		// Drop minor version if 0
		$major_version = substr( Sensei()->version, 0, 3 );

		// Random tweet - must be kept to 102 chars to "fit"
		$tweets        = array(
            'I\'ve just installed Sensei - A premium Learning Management plugin for #WordPress.'
		);
		shuffle( $tweets );
		?>
		<h1><?php printf( __( 'Welcome to Sensei', 'woothemes-sensei' ) ); ?></h1>

		<div class="about-text sensei-about-text">
			<?php
				if ( ! empty( $_GET['sensei-installed'] ) ) {
					$message = __( 'Thanks, all done!', 'woothemes-sensei' );
				} elseif ( ! empty( $_GET['sensei-updated'] ) ) {
					$message = __( 'Thank you for updating to the latest version!', 'woothemes-sensei' );
				} else {
					$message = __( 'Thanks for installing!', 'woothemes-sensei' );
				}

				printf( __( '%s We hope you enjoy using Sensei.', 'woothemes-sensei' ), $message );
			?>
		</div>

		<div class="sensei-badge">
            <?php
                _e('Sensei by WooThemes','woothemes-sensei');
                echo '<br />';

                printf( __( 'Version %s', 'woothemes-sensei' ), Sensei()->version );
            ?>
        </div>

		<p class="sensei-actions">
			<a href="<?php echo admin_url('admin.php?page=woothemes-sensei-settings'); ?>" class="button"><?php _e( 'Settings', 'woothemes-sensei' ); ?></a>
			<a href="<?php echo esc_url( apply_filters( 'sensei_docs_url', 'http://docs.woothemes.com/documentation/plugins/sensei/', 'woothemes-sensei' ) ); ?>" class="docs button"><?php _e( 'Docs', 'woothemes-sensei' ); ?></a>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/products/sensei" data-text="<?php echo esc_attr( $tweets[0] ); ?>" data-via="WooThemes" data-size="large">Tweet</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>

		<h2 > <?php _e( "What's New", 'woothemes-sensei' ); ?> </h2>

		<?php
	}
} // end class sensei welcome
