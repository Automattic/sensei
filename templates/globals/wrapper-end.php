<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Content wrappers
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */

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