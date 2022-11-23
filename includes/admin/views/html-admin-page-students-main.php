<?php
/**
 * Students page main view.
 *
 * This view displays the students data and bulk actions dropdown.
 *
 * @package sensei-lms
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$sensei_list_table = new Sensei_Learners_Admin_Bulk_Actions_View(
	Sensei()->learners->bulk_actions_controller,
	Sensei()->learners,
	Sensei_Learner::instance()
);
$sensei_list_table->prepare_items();

do_action( 'sensei_learner_admin_before_container' );
?>

<div id="woothemes-sensei" class="wrap woothemes-sensei">
	<?php
	do_action( 'sensei_learner_admin_wrapper_container', 'top' );
	?>

			<?php $sensei_list_table->views(); ?>
			<form id="students-filter" method="get">
				<?php Sensei_Utils::output_query_params_as_inputs( [ 's', 'filter_by_course_id', '_wpnonce', '_wp_http_referer' ] ); ?>
				<?php $sensei_list_table->table_search_form(); ?>
				<?php $sensei_list_table->display(); ?>
			</form>
			<?php do_action( 'sensei_learner_admin_extra' ); ?>

	<?php do_action( 'sensei_learner_admin_wrapper_container', 'bottom' ); ?>
</div>

<?php
do_action( 'sensei_learner_admin_after_container' );
