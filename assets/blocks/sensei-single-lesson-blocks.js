import registerSenseiBlocks from './register-sensei-blocks';
import {
	LessonActionsBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
} from './lesson-actions';

import quizBlocks from './quiz';

registerSenseiBlocks( [
	LessonActionsBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
	...quizBlocks,
] );
