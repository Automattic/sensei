/**
 * External dependencies
 */
import { uniq, omitBy } from 'lodash';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { CheckboxControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Lessons for selection.
 *
 * @param {Object}   props
 * @param {string}   props.clientId             Outline block ID.
 * @param {Object}   props.filters              Filters object.
 * @param {number[]} props.selectedLessonIds    Seleted lesson IDs.
 * @param {Object}   props.setSelectedLessonIds Seleted lesson IDs state setter.
 */
const Lessons = ( {
	clientId,
	filters,
	selectedLessonIds,
	setSelectedLessonIds,
} ) => {
	// Ids of the already added lessons.
	const addedLessonIds = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( clientId )
	).map( ( block ) => block.attributes?.id );

	// Lessons by current filter.
	let lessons = useSelect(
		( select ) => {
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

			const courses = select( 'core' ).getEntityRecords(
				'postType',
				'course',
				{
					per_page: 100,
					include: courseIds,
				}
			);

			// Add course field to lessons.
			if ( foundLessons && courses ) {
				foundLessons = foundLessons.map( ( lesson ) => {
					const lessonCourseId = lesson.meta._lesson_course;
					if ( ! courseId ) {
						return lesson;
					}

					let course = courses.find(
						( c ) => c.id === lessonCourseId
					);

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
		},
		[ filters ]
	);

	if ( ! lessons ) {
		return (
			<div className="sensei-lms-quiz-block__existing-lessons-modal__existing-lessons sensei-lms-quiz-block__existing-lessons-modal__existing-lessons--loading">
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
		lessons.every( ( lesson ) => selectedLessonIds.includes( lesson.id ) );

	const toggleAllHandler = ( checked ) => {
		const lessonIds = lessons.map( ( lesson ) => lesson.id );

		setSelectedLessonIds( ( prev ) =>
			checked
				? uniq( [ ...prev, ...lessonIds ] )
				: prev.filter( ( lesson ) => ! lessonIds.includes( lesson ) )
		);
	};

	const toggleLesson = ( lessonId ) => ( checked ) => {
		if ( checked ) {
			setSelectedLessonIds( ( prev ) => [ ...prev, lessonId ] );
		} else {
			setSelectedLessonIds( ( prev ) =>
				prev.filter( ( id ) => id !== lessonId )
			);
		}
	};

	const lessonsMap = ( lesson ) => {
		const course =
			lesson.course?.title.raw || __( 'Loading…', 'sensei-lms' );
		const courseNotFoundClass =
			lesson.course?.id === undefined
				? 'sensei-lms-quiz-block__existing-lessons-modal__course-title--not-found'
				: '';
		const lessonId = lesson.id;
		const title = lesson.title.raw;

		return (
			<tr key={ lessonId }>
				<td>
					<CheckboxControl
						id={ `existing-lesson-${ lessonId }` }
						title={ title }
						checked={ selectedLessonIds.includes( lessonId ) }
						onChange={ toggleLesson( lessonId ) }
					/>
				</td>
				<td className="sensei-lms-quiz-block__existing-lessons-modal__lesson-title">
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
		<div className="sensei-lms-quiz-block__existing-lessons-modal__lessons">
			<table className="sensei-lms-quiz-block__existing-lessons-modal__table">
				<thead>
					<tr>
						<th className="sensei-lms-quiz-block__existing-lessons-modal__lesson-checkbox">
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
							<td colSpan="4">
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
