/**
 * Internal dependencies
 */
import {
	CompleteLessonBlock,
	LessonActionsBlock,
	LessonCompletedBlock,
	PreviousLessonBlock,
	NextLessonBlock,
	TakeQuizBlock,
	ResetLessonBlock,
} from './lesson-actions';
import registerSenseiBlocks from './register-sensei-blocks';

registerSenseiBlocks( [
	LessonActionsBlock,
	LessonCompletedBlock,
	CompleteLessonBlock,
	PreviousLessonBlock,
	NextLessonBlock,
	TakeQuizBlock,
	ResetLessonBlock,
] );
