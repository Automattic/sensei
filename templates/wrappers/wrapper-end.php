<?php
/**
 * Content wrappers
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$template = get_option('template');

switch( $template ) {

	// IF Twenty Eleven
	case 'twentyeleven' :
	?>
			</div>
		</div>
	<?php
		break;

	// IF Twenty Twelve
	case 'twentytwelve' :
	?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div>
	<?php
		break;

	// IF Twenty Fourteen
	case 'twentyfourteen' :
	?>
					</div>
				</div>
			</div>
		</div>
		<?php get_sidebar(); ?>
	<?php
		break;

	// Default
	default :
	?>
		</div>
		<?php get_sidebar(); ?>
	</div>
	<?php
		break;
}

?>