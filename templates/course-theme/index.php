<?php
/**
 * The Template for displaying Course Theme content.
 * This is a wrapper template and the others will be loaded inside the content,
 * so we can run the `do_blocks` function, adding blocks to the PHP template.
 *
 * Override this template by copying it to yourtheme/sensei/course-theme/index.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.13.4
 */

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
	the_content();
	wp_footer();
?>
</body>
</html>
