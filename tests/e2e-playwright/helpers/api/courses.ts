import { CourseContent } from '@e2e/factories/courses';
import { lessonSimple } from '../../factories/lesson';
import { WpApiRequestContext } from './contexts';

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
	id?: number;
	title?: string;
	content?: string;
	status?: string;
};

const toStructure = ( lesson: Lesson ) => ( {
	...lesson,
	type: 'lesson',
	draft: false,
} );

export const createCourse = async (
	api: WpApiRequestContext,
	course: Course
): Promise< CourseResponse > => {
	const created = await api.post< CourseResponse >(
		`/wp-json/wp/v2/courses`,
		{
			status: 'publish',
			content: CourseContent.SIMPLE,
			excerpt: '',
			'course-category': course.categoryIds || [],
			...course,
		}
	);

	const structure = ( course.lessons || [] ).map( toStructure );

	const newLessons = await api.post< LessonResponse[] >(
		`/wp-json/sensei-internal/v1/course-structure/${ created.id }`,
		{
			structure,
		}
	);

	const createdLessons = await Promise.all(
		newLessons.map( ( lesson ) => {
			return updateLesson( api, {
				id: lesson.id,
				status: 'publish',
				content: lessonSimple(),
			} );
		} )
	);

	return {
		...created,
		lessons: createdLessons,
	};
};
const updateLesson = async (
	api: WpApiRequestContext,
	lesson: Lesson
): Promise< LessonResponse > => {
	return api.post< LessonResponse >(
		`/wp-json/wp/v2/lessons/${ lesson.id }`,
		{
			...lesson,
			status: 'publish',
			content: lesson.content,
		}
	);
};

export const createCourseCategory = async (
	wp: WpApiRequestContext,
	category: CourseCategory
): Promise< CourseCategory > => {
	const { name, description, slug } = category;

	return wp.post( `/wp-json/wp/v2/course-category`, {
		name,
		description: description || `Some description for ${ name }`,
		slug,
	} );
};
