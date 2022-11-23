<?php
/**
 * Students page course view.
 *
 * This view displays the students data in a course context.
 *
 * @package sensei-lms
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$sensei_list_table = new Sensei_Learners_Main();
$sensei_list_table->prepare_items();

do_action( 'learners_before_container' );
do_action( 'learners_wrapper_container', 'top' );

Sensei()->learners->learners_headers();
?>
<?php $sensei_list_table->views(); ?>
<form id="learners-filter" method="get">
	<?php Sensei_Utils::output_query_params_as_inputs( [ 's' ] ); ?>
	<?php $sensei_list_table->table_search_form(); ?>
</form>
<?php $sensei_list_table->display(); ?>
<?php
do_action( 'learners_wrapper_container', 'bottom' );
do_action( 'learners_after_container' );
