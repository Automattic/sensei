/**
 * Internal dependencies
 */
import {
	CompleteLessonBlock,
	LessonActionsBlock,
	LessonCompletedBlock,
	NextLessonBlock,
	TakeQuizBlock,
	ResetLessonBlock,
} from './lesson-actions';
import registerSenseiBlocks from './register-sensei-blocks';

registerSenseiBlocks( [
	LessonActionsBlock,
	LessonCompletedBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	TakeQuizBlock,
	ResetLessonBlock,
] );
