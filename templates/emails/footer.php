<?php
/**
 * Email Footer
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates/Emails
 * @version     1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woothemes_sensei, $sensei_email_data;
extract( $sensei_email_data );

// Load colours
$base = '#557da1';
if( isset( $woothemes_sensei->settings->settings['email_base_color'] ) && '' != $woothemes_sensei->settings->settings['email_base_color'] ) {
    $base = $woothemes_sensei->settings->settings['email_base_color'];
}

$base_lighter_40 = sensei_hex_lighter( $base, 40 );

$footer_text = sprintf( __( '%1$s - Powered by Sensei', 'woothemes-sensei' ), get_bloginfo( 'name' ) );
if( isset( $woothemes_sensei->settings->settings['email_footer_text'] ) ) {
    $footer_text = $woothemes_sensei->settings->settings['email_footer_text'];
}

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline.
$template_footer = "
	border-top:0;
	-webkit-border-radius:6px;
";

$credit = "
	border:0;
	color: $base_lighter_40;
	font-family: Arial;
	font-size:12px;
	line-height:125%;
	text-align:center;
";
?>
															</div>
														</td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Footer -->
                                	<table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer" style="<?php echo $template_footer; ?>">
                                    	<tr>
                                        	<td valign="top">
                                                <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td colspan="2" valign="middle" id="credit" style="<?php echo $credit; ?>">
                                                        	<?php echo wpautop( wp_kses_post( wptexturize( apply_filters( 'sensei_email_footer_text', $footer_text ) ) ) ); ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Footer -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>