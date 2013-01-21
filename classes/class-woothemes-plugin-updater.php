<?php
/**
 * WooThemes Plugin Update Checker
 *
 * Use the WordPress Update Manager to check for plugin updates at WooThemes.com.
 *
 * @version 1.0.0
 * @category Plugins
 * @package WordPress
 * @subpackage WooFramework
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - var $api_url
 * - var $api_key
 *
 * - var $plugin_path
 * - var $plugin_url
 * - var $plugin_prefix
 * - var $plugin_base
 *
 * - var $username
 * - var $password
 
 * - Constructor()
 * - init()
 * - authenticate()
 * - load_user_data()
 * - register_nav_menu_link()
 * - admin_screen()
 * - admin_screen_logic()
 * - contextual_help()
 * - admin_notice()
 * - update_check()
 * - plugin_information()
 * - prepare_request()
 * - get_api_data()
 * - instance_exists()
 * - hide_admin_notice()
 */
	class WooThemes_Plugin_Updater {
		var $api_url = 'https://www.woothemes.com/api';
		var $api_key = ''; // Unique value, used to determine the plugin to be updated.
		
		var $plugin_token = 'woothemes-plugin-updater';
		var $plugin_path;
	 	var $plugin_url;
	 	var $plugin_prefix = 'woothemes_plugin_updater_';
	 	var $plugin_base;
	 	
	 	var $username = '';
	 	var $password = '';
	 			
		/**
		 * WooThemes_Plugin_Updater function.
		 * 
		 * @access public
		 * @return void
		 */
		function WooThemes_Plugin_Updater ( $file ) {
			$this->plugin_path = dirname( $file );
			$this->plugin_url = trailingslashit( WP_PLUGIN_URL ) . plugin_basename( dirname( $file ) );
			$this->plugin_base = plugin_basename( $file );
		} // End Constructor
		
		/**
		 * init function.
		 * 
		 * @access public
		 * @return void
		 */
		function init () {
			// Don't do anything without the API key.
			if ( $this->api_key == '' ) { return; }
			
			// Register Navigation Menu Link
			add_action( 'admin_menu', array( &$this, 'register_nav_menu_link' ), 10 );
			
			// Check For Updates
			add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'update_check' ) );
			
			// Check For Plugin Information
			add_filter( 'plugins_api', array( &$this, 'plugin_information' ), 10, 3 );
			
			if ( ! $this->instance_exists() ) {
				add_action( 'init', array( &$this, 'hide_admin_notice' ), 0 );
			}
		} // End init()
		
		/**
		 * authenticate function.
		 * 
		 * @access public
		 * @param string $username
		 * @param string $password
		 * @return boolean $is_valid
		 */
		function authenticate ( $username, $password ) {
			$is_valid = false;
			
			if ( $username != '' && $password != '' ) {
				$params = array( 'username' => $username, 'password' => md5( $password ), 'action' => 'authenticate' );
				
				$xmlobj = $this->get_api_data( $params );
				
				if ( $xmlobj[0] == 'OK' ) {
					$is_valid = true;
				}
			}
			
			return $is_valid;
		} // End authenticate()
		
		/**
		 * load_user_data function.
		 * 
		 * @access public
		 * @return void
		 */
		function load_user_data () {
			$user = get_option( $this->plugin_prefix . 'username' );
			$pass = get_option( $this->plugin_prefix . 'password' );
			
			if ( $user != '' && $pass != '' ) {
				$this->username = $user;
				$this->password = $pass;
			}
		} // End load_user_data()
		
		/**
		 * register_nav_menu_link function.
		 * 
		 * @access public
		 * @return void
		 * @uses admin_screen_logic()
		 */
		function register_nav_menu_link () {	
			// Don't register the menu if it's already there.
			if ( $this->instance_exists() ) { return; }
			
			// Setup Admin Notices
			add_action ( 'admin_notices', array( &$this, 'admin_notice' ) );
			
			if ( function_exists( 'add_submenu_page' ) ) {
				$this->admin_screen = add_submenu_page( 'index.php', __( 'WooThemes Updates', 'woothemes-sensei' ), __( 'WooThemes Updates', 'woothemes-sensei' ), 'switch_themes', $this->plugin_token, array( &$this, 'admin_screen' ) );	
			}
			
			// Load admin screen logic.
			if ( isset( $_POST['woo-action'] ) && ( $_POST['woo-action'] == 'woo-plugin-updater-login' ) ) {
				$this->admin_screen_logic();
			}
		} // End register_nav_menu_link()
		
		/**
		 * admin_screen function.
		 * 
		 * @access public
		 * @return void
		 */
		function admin_screen () {
?>
<div class="wrap">

	<?php screen_icon( 'plugins' ); ?>
	<h2><?php _e( 'WooThemes Plugin Updater', 'woothemes-sensei' ); ?></h2>
	
	<form name="woo-plugin-updater-login" id="woo-plugin-updater-login" action="<?php echo admin_url( 'index.php?page=' . $this->plugin_token ); ?>" method="post">
		<fieldset>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="username"><?php _e( 'WooThemes Username', 'woothemes-sensei' ); ?>:</label></th>
						<td><input type="text" class="input-text input-woo_user regular-text" name="username" id="woo_user" value="" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="password"><?php _e( 'WooThemes Password', 'woothemes-sensei' ); ?>:</label></th>
						<td><input type="password" class="input-text input-woo_pass regular-text" name="password" id="woo_pass" value="" /></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		
		<fieldset>
			<p class="submit">
				<button type="submit" name="woo_login" id="woo_login" class="button-primary"><?php _e( 'Login', 'woothemes-sensei' ); ?></button>
			</p>
			<input type="hidden" name="woo-action" value="woo-plugin-updater-login" />
			<input type="hidden" name="page" value="woo-plugin-updater" />
		</fieldset>
	</form>

</div><!--/.wrap-->
<?php
		} // End admin_screen()
		
		/**
		 * admin_screen_logic function.
		 * 
		 * @access public
		 * @return void
		 */
		function admin_screen_logic () {
			$is_valid = $this->authenticate( $_POST['username'], $_POST['password'] );
			
			if ( $is_valid ) {
				$username = trim( strip_tags( $_POST['username'] ) );
				$password = md5( trim( strip_tags( $_POST['password'] ) ) );
					
				update_option( $this->plugin_prefix . 'username', $username );
				update_option( $this->plugin_prefix . 'password', $password );
				
				// Refresh the login screen.
				wp_redirect( admin_url( 'index.php?page=' . $this->plugin_token ) ); exit;
			} else {
				// Refresh the login screen with an error message.
				wp_redirect( admin_url( 'index.php?page=' . $this->plugin_token . '&type=error' ) ); exit;
			}
		} // End admin_screen_logic()
		
		/**
		 * admin_notice function.
		 * 
		 * @access public
		 * @return void
		 */
		function admin_notice () {
			$notice = '';
			
			$this->load_user_data();
			
			// Admin notice for switching login details.
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->plugin_token ) ) {
				
				if ( $this->username != '' && $this->password != '' ) {	
					$notice = '<div id="woo-plugin-updater-notice" class="updated fade">' . "\n";
					$notice .= '<p><strong>' . __( 'Switch WooThemes Account', 'woothemes-sensei' ) . '</strong></p><p>' . "\n";
					
					$notice .= sprintf( __( 'You are currently logged in as %1$s. To switch to a different WooThemes account, please fill in the login details below.', 'woothemes-sensei' ), '<strong>' . $this->username . '</strong>' );
					$notice .= "\n" . '</p>' . "\n";
					
					$notice .= '<p><a href="' . admin_url( 'update-core.php' ) . '">' . __( 'Update your Plugins', 'woothemes-sensei' ) . ' &rarr;</a></p>' . "\n";
					
					$notice .= '</div>' . "\n";
				}
				
				if ( isset( $_GET['type'] ) && ( $_GET['type'] == 'error' ) ) {	
					$notice .= '<div id="woo-plugin-updater-error-notice" class="error fade">' . "\n";
					$notice .= '<p><strong>' . __( 'Login Error', 'woothemes-sensei' ) . '</strong></p><p>' . "\n";
					
					$notice .= __( 'The login details supplied are invalid. Please try again.', 'woothemes-sensei' );
					$notice .= "\n" . '</p>' . "\n";
					
					$notice .= '</div>' . "\n";
				}
			}
			
			// Admin notice for if no login details are set, to notify the user.
			if ( ( ! isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && ( $_GET['page'] != $this->plugin_token ) ) ) && ( $this->username == '' || $this->password == '' ) && ( get_option( $this->plugin_prefix . 'hide-admin-notice', false ) != true ) ) {
				$notice = '<div id="woo-plugin-updater-notice" class="updated fade">' . "\n";
				$notice .= '<p class="alignleft"><strong>' . __( 'Enable WooThemes Plugin Updates.', 'woothemes-sensei' ) . '</strong> ' . "\n";
				
				$notice .= sprintf( __( 'Please <a href="%1$s">login</a> to enable automatic plugin updates.', 'woothemes-sensei' ), 'index.php?page=' . $this->plugin_token );
				$notice .= "\n" . '</p>' . "\n";

				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				
				$delimiter = '?';
				
				if ( stristr( $url, '?' ) == true ) {
					$delimiter = '&';
				}
				
				$url .= $delimiter . 'woo-hide-updatenotice=true';
				
				$notice .= '<p class="alignright submitbox"><a href="' . $url . '" class="submitdelete">' . __( 'Hide This Message', 'woothemes-sensei' ) . '</a></p>' . "\n";
				$notice .= '<br class="clear" />';
				$notice .= '</div>' . "\n";
			}
			
			if ( $notice != '' ) {
				echo $notice;
			}
		} // End admin_notice()
		
		/**
		 * update_check function.
		 * 
		 * @access public
		 * @param object $transient
		 * @return object $transient
		 */
		function update_check ( $transient ) {

		    // Check if the transient contains the 'checked' information
		    // If no, just return its value without hacking it
		    if( empty( $transient->checked ) )
		        return $transient;
		    
		    // The transient contains the 'checked' information
		    // Now append to it information form your own API
		    
		    $plugin_slug = $this->plugin_base;
		    
		    // Get the user's data.
		    $this->load_user_data();
		    
		    // POST data to send to your API
		    $args = array(
		        'action' => 'update-check',
		        'plugin_name' => $plugin_slug,
		        'version' => $transient->checked[$plugin_slug], 
		        'api_key' => $this->api_key, 
		        'username' => $this->username, 
		        'password' => $this->password
		    );
		    
		    // Send request checking for an update
		    $response = $this->prepare_request( $args );
		    
		    // If response is false, don't alter the transient
		    if( false !== $response ) {
		        $transient->response[$plugin_slug] = $response;
		    }
		    
		    return $transient;
		} // End update_check()
		
		function plugin_information ( $false, $action, $args ) {	
			$plugin_slug = $this->plugin_base;

			$transient = get_site_transient( 'update_plugins' );

			// Check if this plugins API is about this plugin
			if( $args->slug != $plugin_slug ) {
				return $false;
			}
			
			// POST data to send to your API
			$args = array(
				'action' => 'plugin_information',
				'plugin_name' => $plugin_slug, 
				'version' => $transient->checked[$plugin_slug], 
			    'api_key' => $this->api_key, 
		        'username' => $this->username, 
		        'password' => $this->password
			);
			
			// Send request for detailed information
			$response = $this->prepare_request( $args );

			return $response;
		} // End plugin_information()
		
		/**
		 * prepare_request function.
		 * 
		 * @access public
		 * @param array $args
		 * @return object $response or boolean false
		 */
		function prepare_request( $args ) {
		
		    // Send request
		    $request = wp_remote_post( $this->api_url, array( 'body' => $args ) );
		    
		    // Make sure the request was successful
		    if( is_wp_error( $request )
		    or
		    wp_remote_retrieve_response_code( $request ) != 200
		    ) {
		        // Request failed
		        return false;
		    }
		    
		    // Read server response, which should be an object
		    $response = maybe_unserialize( wp_remote_retrieve_body( $request ) );
		    if( is_object( $response ) ) {
		        return $response;
		    } else {
		        // Unexpected response
		        return false;
		    }
		} // End prepare_request()
		
		/**
		 * get_api_data function.
		 *
		 * @description Return the contents of a URL using wp_remote_post().
		 * @access public
		 * @param array $params (default: array())
		 * @return string $data
		 */
		function get_api_data ( $params = array() ) {
			$response = wp_remote_post( $this->api_url, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $params,
				'cookies' => array(),
				'sslverify' => false
			    )
			);
			
			if( is_wp_error( $response ) ) {
			  $data = '<?xml version="1.0"?><status>FAILED</status>';
			} else {
				$data = $response['body'];
			}
			
			try {
				$xmlobj = new SimpleXmlElement( $data );
			} catch ( Exception $e ) {
				$data = '<?xml version="1.0"?><status>FAILED</status>';
				$xmlobj = new SimpleXmlElement( $data );
			}
			
			return $xmlobj;
		} // End get_api_data()
		
		/**
		 * instance_exists function.
		 * 
		 * @access public
		 * @return void
		 */
		function instance_exists () {
			global $submenu;
			
			$exists = false;
			
			// Check if the menu item already exists.
			if ( isset( $submenu['index.php'] ) && is_array( $submenu['index.php'] ) ) {
				foreach ( $submenu['index.php'] as $k => $v ) {
					if ( isset( $v[2] ) && ( $v[2] == $this->plugin_token ) ) {
						$exists = true;
						break;
					}
				}
			}
			
			return $exists;
		} // End instance_exists()
		
		/**
		 * hide_admin_notice function.
		 * 
		 * @access public
		 * @return void
		 */
		function hide_admin_notice () {
			if ( isset( $_GET['woo-hide-updatenotice'] ) && ( $_GET['woo-hide-updatenotice'] == 'true' ) ) {
				update_option( $this->plugin_prefix . 'hide-admin-notice', true );
				
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				
				$url = str_replace( 'woo-hide-updatenotice=true', '', $url );
				
				wp_redirect( $url ); exit;
			}
		} // End hide_admin_notice()
	} // End Class
?>