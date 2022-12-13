import { CourseContent } from '@e2e/fixtures/courses';
import { APIRequestContext } from '@playwright/test';
import { lessonSimple } from '../lesson-templates';
import { createApiContext } from './index';

export type Course = {
	title: string;
	meta?: Record< string, string >;
	lessons: Lesson[];
	slug?: string;
	excerpt?: string;
	categoryIds?: Array< string | number >;
	content?: string;
	status?: string;
};

type CourseCategory = {
	id?: number;
	name: string;
	description: string;
	slug: string;
};

type ValueWithRaw = {
	raw: string;
	rendered: string;
};

export type LessonResponse = {
	title: ValueWithRaw;
	content: ValueWithRaw;
	id: number;
	link: string;
};

export type CourseResponse = {
	title: ValueWithRaw;
	content: ValueWithRaw;
	lessons: LessonResponse[];
	id: number;
	link: string;
};

export type Lesson = {
	title: string;
	content: string;
};

const toStructure = ( lesson: Lesson ) => ( {
	...lesson,
	type: 'lesson',
	draft: false,
} );

export const createCourse = async (
	context: APIRequestContext,
	course: Course
): Promise< CourseResponse > => {
	const api = await createApiContext( context );

	const created = await api.post( `/wp-json/wp/v2/courses`, {
		status: 'publish',
		content: CourseContent.SIMPLE,
		excerpt: '',
		'course-category': course.categoryIds || [],
		...course,
	} );

	const structure = ( course.lessons || [] ).map( toStructure );

	const newLessons = await api.post(
		`/wp-json/sensei-internal/v1/course-structure/${ created.id }`,
		{
			structure,
		}
	);

	const createdLessons = await Promise.all(
		newLessons.map( ( lesson ) => updateLesson( api, lesson ) )
	);

	return {
		...created,
		lessons: createdLessons,
	};
};
const updateLesson = async (
	api,
	{ id, content = lessonSimple(), ...lesson }
) => {
	return api.post( `/wp-json/wp/v2/lessons/${ id }`, {
		...lesson,
		status: 'publish',
		id,
		content,
	} );
};

export const createCourseCategory = async (
	context: APIRequestContext,
	category: CourseCategory
): Promise< CourseCategory > => {
	const { name, description, slug } = category;
	const api = await createApiContext( context );

	return api.post( `/wp-json/wp/v2/course-category`, {
		name,
		description: description || `Some description for ${ name }`,
		slug,
	} );
};
