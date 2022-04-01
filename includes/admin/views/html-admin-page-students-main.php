<?php
/**
 * Students page main view.
 *
 * This view displays the students data and bulk actions dropdown.
 *
 * @package sensei-lms
 * @since x.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$sensei_list_table = new Sensei_Learners_Admin_Bulk_Actions_View( $this->bulk_actions_controller, $this );
$sensei_list_table->prepare_items();

do_action( 'sensei_learner_admin_before_container' );
?>

<div id="woothemes-sensei" class="wrap woothemes-sensei">
	<?php
	do_action( 'sensei_learner_admin_wrapper_container', 'top' );
	$sensei_list_table->output_headers();
	?>

	<div id="poststuff" class="sensei-learners-wrap">
		<div class="sensei-learners-main">
			<?php $sensei_list_table->display(); ?>
		</div>
		<div class="sensei-learners-extra">
			<?php do_action( 'sensei_learner_admin_extra' ); ?>
		</div>
	</div>

	<?php do_action( 'sensei_learner_admin_wrapper_container', 'bottom' ); ?>
</div>

<?php
do_action( 'sensei_learner_admin_after_container' );
