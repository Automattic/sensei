import { Course } from '@e2e/helpers/api';
import { lessonSimple } from './lesson';

export enum CourseContent {
	SIMPLE = '<!-- wp:sensei-lms/button-take-course -->\n<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Take Course</button></div>\n<!-- /wp:sensei-lms/button-take-course -->\n\n<!-- wp:sensei-lms/button-contact-teacher -->\n<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">Contact Teacher</a></div>\n<!-- /wp:sensei-lms/button-contact-teacher -->\n\n<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /-->\n\n<!-- wp:sensei-lms/course-outline /-->',
}

export enum CourseMode {
	LEARNING_MODE = 'Learning Mode',
	DEFAULT_MODE = 'Default Mode',
}

const LEARNING_MODE_META = {
	_course_theme: 'sensei-theme',
};

export const buildCourse = (
	mode: CourseMode = CourseMode.DEFAULT_MODE,
	course?: Partial< Course >
): Course => {
	const useLearningMode = mode === CourseMode.LEARNING_MODE;

	return {
		title: `E2E Course ${ mode }`,
		...course,
		meta: {
			...course?.meta,
			...( useLearningMode ? LEARNING_MODE_META : {} ),
		},
		lessons: course?.lessons || [
			{
				title: `E2E Lesson ${ mode } 101`,
				content: lessonSimple(),
			},
			{
				title: `E2E Lesson ${ mode } 102`,
				content: lessonSimple(),
			},
		],
	};
};
