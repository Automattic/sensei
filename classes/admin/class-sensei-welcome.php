<?php
/**
 * Sensei Welcome Page Class
 *
 * Shows a feature overview for the new version (major)
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WooCommerce and WP
 *
 * @author      WooThemes
 * @category    Admin
 * @package     Sensei/Admin
 * @version     1.8.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei_Welcome class
 *
 * @since 1.8.0
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

            <div class="changelog">
                <h4><?php _e( 'UI Overhaul', 'woocommerce' ); ?></h4>
                <p><?php _e( 'We\'ve updated the user interface on both the front and backend of WooCommerce 2.3 "Handsome Hippo".', 'woocommerce' ); ?></p>

                <div class="changelog about-integrations">
                    <div class="sensei-feature feature-section col three-col">
                        <div>
                            <h4><?php _e( 'Frontend UI Improvements', 'woocommerce' ); ?></h4>
                            <p><?php _e( 'On the frontend there are several UX enhancements such as the undo-remove-from cart link and responsive table design as well as a fresh, modern look which meshes more fluidly with the current design trends of default WordPress themes.', 'woocommerce' ); ?></p>
                        </div>
                        <div>
                            <h4><?php _e( 'Backend UI Improvements', 'woocommerce' ); ?></h4>
                            <p><?php _e( 'On the backend, settings have been re-organised and perform better on hand-held devices for an all round improved user experience. ', 'woocommerce' ); ?></p>
                        </div>
                        <div class="last-feature">
                            <h4><?php _e( 'Webhooks UI', 'woocommerce' ); ?></h4>
                            <p><?php printf( __( 'As part of the API, we\'ve introduced a UI for the Webhook system in WooCommerce 2.3. This makes it easier for 3rd party apps to integrate with WooCommerce. Read more in our %sdocs%s.', 'woocommerce' ), '<a href="http://docs.woothemes.com/document/webhooks/">', '</a>' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="changelog">
                <div class="feature-section col three-col">
                    <div>
                        <h4><?php _e( 'Geo-locating Customer Location', 'woocommerce' ); ?></h4>
                        <p><?php printf( __( 'We have added a new option to geolocate the "Default Customer Location". Coupled with ability to show taxes in your store based on this location, you can show relevant prices store-wide. Enable this in the %ssettings%s.', 'woocommerce' ), '<a href="#">', '</a>' ); ?></p>
                    </div>
                    <div>
                        <h4><?php _e( 'Color Customization', 'woocommerce' ); ?></h4>
                        <p><?php printf( __( 'If you\'re looking to customise the look and feel of the frontend in 2.3, take a look at the free %sWooCommerce Colors plugin%s. This lets you change the colors with a live preview.', 'woocommerce' ), '<a href="https://wordpress.org/plugins/woocommerce-colors/">', '</a>' ); ?></p>
                    </div>
                    <div class="last-feature">
                        <h4><?php _e( 'Improved Reports', 'woocommerce' ); ?></h4>
                        <p><?php _e( 'Sales reports can now show net and gross amounts, we\'ve added a print stylesheet, and added extra data on refunds to reports.', 'woocommerce' ); ?></p>
                    </div>
                </div>
                <div class="feature-section col three-col">
                    <div>
                        <h4><?php _e( 'Improved Simplify Gateway', 'woocommerce' ); ?></h4>
                        <p><?php printf( __( 'The built in Simplify Commerce Gateway (available in the US) now supports %sHosted Payments%s - a PCI Compliant hosted payment platform.', 'woocommerce' ), '<a href="https://www.simplify.com/commerce/docs/tools/hosted-payments">', '</a>' ); ?></p>
                    </div>
                    <div>
                        <h4><?php _e( 'Email Template Improvements', 'woocommerce' ); ?></h4>
                        <p><?php printf( __( 'To make email customization simpler, we\'ve included a CSS Inliner in this release, some new template files for styling emails, and some additional hooks for developers. Read more on our %sdeveloper blog%s.', 'woocommerce' ), '<a href="http://develop.woothemes.com/woocommerce/2014/10/2-3-emails/">', '</a>' ); ?></p>
                    </div>
                    <div class="last-feature">
                        <h4><?php _e( 'Simplified Coupon System', 'woocommerce' ); ?></h4>
                        <p><?php printf( __( 'We have simplified the coupon system to ensure discounts are never applied to taxes, and we\'ve improved support for discounting products inclusive of tax. Read more on our %sdevelop blog%s.', 'woocommerce' ), '<a href="http://develop.woothemes.com/woocommerce/2014/12/upcoming-coupon-changes-in-woocommerce-2-3/">', '</a>' ); ?></p>
                    </div>
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
                color: white;
                text-shadow: 0.05em 0.05em 1px black;
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

            p.sensei-actions a.button-primary {
                background: #42A2CE;
                border-color: #849DAD;
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
            'I\'ve just installed Sensei. A premium Learning Management plugin for #WordPress that helps you create courses. Beautifully'
		);
		shuffle( $tweets );
		?>
		<h1><?php printf( __( 'Welcome to Sensei %s', 'woothemes-sensei' ), $major_version ); ?></h1>

		<div class="about-text sensei-about-text">
			<?php
				if ( ! empty( $_GET['sensei-installed'] ) ) {
					$message = __( 'Thanks, all done!', 'woothemes-sensei' );
				} elseif ( ! empty( $_GET['sensei-updated'] ) ) {
					$message = __( 'Thank you for updating to the latest version!', 'woothemes-sensei' );
				} else {
					$message = __( 'Thanks for installing!', 'woothemes-sensei' );
				}

				printf( __( '%s WooCommerce %s is more powerful, stable and secure than ever before. We hope you enjoy using it.', 'woothemes-sensei' ), $message, $major_version );
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
			<a href="<?php echo admin_url('admin.php?page=woothemes-sensei-settings'); ?>" class="button button-primary"><?php _e( 'Settings', 'woothemes-sensei' ); ?></a>
			<a href="<?php echo esc_url( apply_filters( 'sensei_docs_url', 'http://docs.woothemes.com/documentation/plugins/sensei/', 'woothemes-sensei' ) ); ?>" class="docs button button-primary"><?php _e( 'Docs', 'woothemes-sensei' ); ?></a>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/products/sensei" data-text="<?php echo esc_attr( $tweets[0] ); ?>" data-via="WooThemes" data-size="large">Tweet</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>

		<h2 > <?php _e( "What's New", 'woothemes-sensei' ); ?> </h2>

		<?php
	}
} // end class sensei welcome
