<?php
/**
 * AutomateWoo action for removing a user to a course.
 *
 * @package 3rd-Party
 */

namespace Sensei\AutomateWoo\Actions;

use AutomateWoo\Action;
use AutomateWoo\Clean;
use AutomateWoo\Sensei_Workflow_Helper;
use Sensei_Course_Enrolment;

/**
 * AutomateWoo action class for removing a user to a course.
 */
class Remove_From_Course_Action extends Action {
	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'customer' ];

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	protected function load_admin_details() {
		$this->title = __( 'Remove from Course', 'sensei-lms' );
		$this->group = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$courses = Sensei_Workflow_Helper::get_courses_field();
		$courses->set_required();
		$this->add_field( $courses );
	}

	/**
	 * Run the action.
	 */
	public function run() {
		$customer = $this->workflow->data_layer()->get_customer();

		if ( ! $customer ) {
			return;
		}

		$courses = Clean::ids( $this->get_option( 'sensei_courses' ) );

		foreach ( $courses as $course_id ) {
			$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
			$course_enrolment->withdraw( $customer->get_user_id() );
		}
	}
}
