/**
 * Internal dependencies
 */
import {
	CompleteLessonBlock,
	LessonActionsBlock,
	LessonCompletedBlock,
	NextLessonBlock,
	ViewQuizBlock,
	ResetLessonBlock,
} from './lesson-actions';
import registerSenseiBlocks from './register-sensei-blocks';

registerSenseiBlocks( [
	LessonActionsBlock,
	LessonCompletedBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ViewQuizBlock,
	ResetLessonBlock,
] );
