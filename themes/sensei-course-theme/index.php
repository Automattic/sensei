<?php
/**
 * The template for displaying Course Theme content when WordPress core block template canvas is not available.
 * This is a wrapper template and the others will be loaded inside the content.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.15.0
 */

namespace Sensei\Themes\Sensei_Course_Theme\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
	\Sensei_Course_Theme_Compat::instance()->the_course_theme_layout();
	wp_footer();
?>
</body>
</html>
