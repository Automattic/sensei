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

<div id="poststuff" class="sensei-learners-wrap">
	<div class="sensei-learners-main">
		<?php $sensei_list_table->display(); ?>
	</div>
	<div class="sensei-learners-extra">
		<?php do_action( 'sensei_learners_extra' ); ?>
	</div>
</div>

<?php
do_action( 'learners_wrapper_container', 'bottom' );
do_action( 'learners_after_container' );
