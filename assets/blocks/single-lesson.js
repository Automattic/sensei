/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import {
	LessonActionsBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
} from './lesson-actions';
import LessonPropertiesBlock from './lesson-properties';
import FeaturedVideoBlock from './featured-video';

registerSenseiBlocks( [
	LessonActionsBlock,
	LessonPropertiesBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
	FeaturedVideoBlock,
] );
