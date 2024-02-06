/**
 * External dependencies
 */
import { isEmpty, keyBy, omitBy, uniq } from 'lodash';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { CheckboxControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Select lessons by filters.
 *
 * @param {Function} select  Data select function.
 * @param {Object}   filters Filters object.
 * @return {Object[]} Lessons.
 */
const selectLessons = ( select, filters ) => {
	const courseId = select( 'core/editor' ).getCurrentPostId();

	let foundLessons = select( 'core' ).getEntityRecords(
		'postType',
		'lesson',
		{
			status: [ 'publish', 'draft' ],
			per_page: 100,
			...omitBy( filters, ( v ) => v === '' ),
		}
	);

	foundLessons = foundLessons?.filter(
		( lesson ) =>
			! lesson.meta._lesson_course ||
			lesson.meta._lesson_course !== courseId
	);

	const courseIds = foundLessons
		? foundLessons
				.filter( ( lesson ) => lesson.meta._lesson_course )
				.map( ( lesson ) => lesson.meta._lesson_course )
		: [];

	const courses = select( 'core' ).getEntityRecords( 'postType', 'course', {
		per_page: 100,
		include: courseIds,
	} );
	const mappedCourses = keyBy( courses, 'id' );

	// Add course field to lessons.
	if ( ! isEmpty( foundLessons ) && ! isEmpty( mappedCourses ) ) {
		foundLessons = foundLessons.map( ( lesson ) => {
			const lessonCourseId = lesson.meta._lesson_course;
			if ( ! courseId ) {
				return lesson;
			}

			let course = mappedCourses[ lessonCourseId ] || undefined;
			if ( ! course ) {
				course = {
					id: undefined,
					title: {
						raw: __( 'Course not assigned', 'sensei-lms' ),
					},
				};
			}

			return {
				...lesson,
				course,
			};
		} );
	}

	return foundLessons;
};

/**
 * Lessons for selection.
 *
 * @param {Object}   props
 * @param {string}   props.clientId           Outline block ID.
 * @param {Object}   props.filters            Filters object.
 * @param {Object[]} props.selectedLessons    Seleted lessons.
 * @param {Function} props.setSelectedLessons Seleted lessons state setter.
 */
const Lessons = ( {
	clientId,
	filters,
	selectedLessons,
	setSelectedLessons,
} ) => {
	// Ids of the already added lessons.
	const addedLessonIds = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( clientId )
	).map( ( block ) => block.attributes?.id );

	// Lessons by current filter.
	let lessons = useSelect(
		( select ) => {
			return selectLessons( select, filters );
		},
		[ filters ]
	);

	if ( ! lessons ) {
		return (
			<div className="wp-block-sensei-lms-course-outline__existing-lessons-modal__existing-lessons wp-block-sensei-lms-course-outline__existing-lessons-modal__existing-lessons--loading">
				<Spinner />
			</div>
		);
	}

	// Filter out already added lessons.
	lessons = lessons.filter(
		( lesson ) => ! addedLessonIds.includes( lesson.id )
	);

	const allChecked =
		lessons.length > 0 &&
		lessons.every( ( lesson ) => selectedLessons.includes( lesson.id ) );

	const toggleAllHandler = ( checked ) => {
		//const lessonIds = lessons.map( ( lesson ) => lesson.id );

		setSelectedLessons( ( prev ) =>
			checked
				? uniq( [ ...prev, ...lessons ] )
				: prev.filter( ( lesson ) => ! lessons.includes( lesson ) )
		);
	};

	const toggleLesson = ( lesson ) => ( checked ) => {
		if ( checked ) {
			setSelectedLessons( ( prev ) => [ ...prev, lesson ] );
		} else {
			setSelectedLessons( ( prev ) =>
				prev.filter(
					( existingLesson ) => existingLesson.id !== lesson.id
				)
			);
		}
	};

	const lessonsMap = ( lesson ) => {
		const course =
			lesson.course?.title.raw || __( 'Loading…', 'sensei-lms' );
		const courseNotFoundClass =
			lesson.course?.id === undefined
				? 'wp-block-sensei-lms-course-outline__existing-lessons-modal__course-title--not-found'
				: '';
		const lessonId = lesson.id;
		const title = lesson.title.raw;

		return (
			<tr key={ lessonId }>
				<td>
					<CheckboxControl
						id={ `existing-lesson-${ lessonId }` }
						title={ title }
						checked={ selectedLessons.includes( lesson ) }
						onChange={ toggleLesson( lesson ) }
					/>
				</td>
				<td className="wp-block-sensei-lms-course-outline__existing-lessons-modal__lesson-title">
					<label
						htmlFor={ `existing-lesson-${ lessonId }` }
						title={ title }
					>
						{ title }
					</label>
				</td>
				<td className={ courseNotFoundClass }>{ course }</td>
			</tr>
		);
	};

	return (
		<div className="wp-block-sensei-lms-course-outline__existing-lessons-modal__lessons">
			<table className="wp-block-sensei-lms-course-outline__existing-lessons-modal__table">
				<thead>
					<tr>
						<th className="wp-block-sensei-lms-course-outline__existing-lessons-modal__lesson-checkbox">
							<CheckboxControl
								title={ __(
									'Toggle all visible lessons selection.',
									'sensei-lms'
								) }
								checked={ allChecked }
								onChange={ toggleAllHandler }
							/>
						</th>
						<th>{ __( 'Lesson', 'sensei-lms' ) }</th>
						<th>{ __( 'Course', 'sensei-lms' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ lessons.length === 0 ? (
						<tr>
							<td colSpan="3">
								<p>
									{ __( 'No lessons found.', 'sensei-lms' ) }
								</p>
							</td>
						</tr>
					) : (
						lessons.map( lessonsMap )
					) }
				</tbody>
			</table>
		</div>
	);
};

export default Lessons;
