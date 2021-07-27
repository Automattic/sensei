/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import LearnerCoursesBlock from './learner-courses-block';
import LearnerMessagesButtonBlock from './learner-messages-button-block';
import CourseCompletedActionsBlock from './course-completed-actions';
import CourseResultsBlock from './course-results-block';

const blocks = [ LearnerCoursesBlock, LearnerMessagesButtonBlock ];

if ( window.sensei_single_page_blocks.course_completed_page_enabled ) {
	blocks.push( CourseCompletedActionsBlock );
	blocks.push( CourseResultsBlock );
}
registerSenseiBlocks( blocks );
