/**
 * External dependencies
 */
import { omitBy, uniq } from 'lodash';

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
	const foundLessons = select( 'core' ).getEntityRecords(
		'postType',
		'lesson',
		{
			requestSource: 'add_existing_lesson_modal',
			status: [ 'publish', 'draft' ],
			metaKey: '_lesson_course',
			metaValue: 0,
			per_page: 100,
			...omitBy( filters, ( v ) => v === '' ),
		}
	);

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
			<div className="sensei-lms-existing-lessons-modal__lessons sensei-lms-existing-lessons-modal__lessons--loading">
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
		lessons.every( ( lesson ) => selectedLessons.includes( lesson ) );

	const toggleAllHandler = ( checked ) => {
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
				<td className="sensei-lms-existing-lessons-modal__lesson-title">
					<label
						htmlFor={ `existing-lesson-${ lessonId }` }
						title={ title }
					>
						{ title }
					</label>
				</td>
			</tr>
		);
	};

	return (
		<div className="sensei-lms-existing-lessons-modal__lessons">
			<table className="sensei-lms-existing-lessons-modal__lessons-table">
				<thead>
					<tr>
						<th className="sensei-lms-existing-lessons-modal__lesson-checkbox">
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
					</tr>
				</thead>
				<tbody>
					{ lessons.length === 0 ? (
						<tr>
							<td colSpan="2">
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
