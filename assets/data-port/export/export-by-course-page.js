/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PostTokenField } from './post-token-field';
import { ExportPreviewPanel } from './export-preview-panel';

/**
 * Step 2 — "By course" mode. Pick courses; the export bundle includes
 * each picked course's lessons and the questions used by those
 * lessons' quizzes. Empty selection exports every course.
 *
 * @param {Object}   props
 * @param {Object}   props.job      Current job state.
 * @param {Function} props.onSubmit Called with ({ types, selections, mode }).
 * @param {Function} props.onBack   Called when the user clicks "Back".
 */
export const ExportByCoursePage = ( { job, onSubmit, onBack } ) => {
	const [ courseIds, setCourseIds ] = useState( [] );

	const isLoading = job && 'creating' === job.status;

	const submit = () =>
		onSubmit( {
			types: [ 'course', 'lesson', 'question' ],
			selections: { course: courseIds },
			mode: 'by_course',
		} );

	const previewLines = [
		{
			file: 'courses.csv',
			detail:
				courseIds.length > 0
					? __( 'selected courses', 'sensei-lms' )
					: __( 'all courses', 'sensei-lms' ),
		},
		{
			file: 'lessons.csv',
			detail:
				courseIds.length > 0
					? __(
							'lessons belonging to the selected courses',
							'sensei-lms'
					  )
					: __( 'all lessons', 'sensei-lms' ),
		},
		{
			file: 'questions.csv',
			detail:
				courseIds.length > 0
					? __(
							'questions used by those lessons’ quizzes',
							'sensei-lms'
					  )
					: __( 'all questions', 'sensei-lms' ),
		},
	];

	return (
		<div className="sensei-data-port-step__body">
			<p className="sensei-export__by-course__label">
				{ __(
					'Pick courses to export. Each course is exported with its lessons and their questions. Leave empty to export all courses.',
					'sensei-lms'
				) }
			</p>
			<PostTokenField
				type="course"
				label={ __( 'Courses', 'sensei-lms' ) }
				placeholder={ __( 'Search courses…', 'sensei-lms' ) }
				selectedIds={ courseIds }
				onChange={ setCourseIds }
			/>
			<ExportPreviewPanel lines={ previewLines } />
			<div className="sensei-data-port-step__footer">
				<Button isSecondary onClick={ onBack } disabled={ isLoading }>
					{ __( 'Back', 'sensei-lms' ) }
				</Button>
				<Button
					isPrimary
					onClick={ submit }
					disabled={ isLoading }
					isBusy={ isLoading }
				>
					{ __( 'Start Export', 'sensei-lms' ) }
				</Button>
			</div>
		</div>
	);
};
