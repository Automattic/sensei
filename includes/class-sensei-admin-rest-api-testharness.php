<?php


class Sensei_Admin_Rest_Api_Testharness {

    private $name;
    private $file;
    private $page_slug;

    function __construct( $file )
    {
        $this->file = $file;
        $this->page_slug = 'sensei_rest_api_testharness';

        add_action( 'init', array( $this, 'initialize' ) );

        // Admin functions
        if (is_admin()) {
            add_action('admin_menu', array($this, 'testharness_admin_menu'), 10);

            if ( $this->is_this_page() ) {

                add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

            }
        }
    }

    function initialize() {
        $this->name = __( 'REST Api TestHarness', 'woothemes-sensei' );
        $this->nonce =  wp_create_nonce( 'wp_rest' );
        $this->cookie = $_COOKIE;
    }

    function admin_enqueue_scripts() {
        if ( false === $this->is_this_page() ) {
            return;
        }

        wp_localize_script( 'wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw( rest_url() ),
            'nonce' => $this->nonce
        ) );
        $js = Sensei()->plugin_url . 'assets/js/admin/testharness.js';
        $react = Sensei()->plugin_url . 'assets/vendor/react/react.min.js';
        $react_dom = Sensei()->plugin_url . 'assets/vendor/react/react-dom.min.js';
        wp_enqueue_script( 'sensei-admin-react', $react, array() );
        wp_enqueue_script( 'sensei-admin-react-dom', $react_dom, array( 'sensei-admin-react' ) );
        wp_enqueue_script( 'sensei-admin-rest-testharness', $js, array( 'wp-api', 'sensei-admin-react-dom' ) );
    }

    protected function is_this_page() {
        return isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug );
    }

    function testharness_admin_menu() {
        global $menu, $woocommerce;

        if ( current_user_can( 'manage_sensei_grades' ) ) {

            add_submenu_page( 'sensei', $this->name,  $this->name , 'manage_sensei_grades', $this->page_slug, array( $this, 'render_page' ) );

        }
    }

    function render_page() {
      $cookie = '';
      foreach ($this->cookie as $key => $value) {
        $cookie .= $key . '=' . urlencode( $value ) . '; ';
      }
        ?>
        <h3>nonce</h3>
        <pre><?php echo $this->nonce; ?></pre>
        <h3>cookie</h3>
        <pre><?php echo $cookie; ?></pre>
        <h3>rest url</h3>
        <pre><?php echo esc_url_raw( rest_url() ); ?></pre>
        
        <div id="testharness-app">Testharness</div>
        <?php
    }
}
