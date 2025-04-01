/**
 * WordPress dependencies
 */
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import { useCallback, useState, RawHTML } from '@wordpress/element';
import { search } from '@wordpress/icons';
import { __, _n, sprintf } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import CourseList from './course-list';
import InputControl from '../../../blocks/editor-components/input-control';
import useAbortController from '../hooks/use-abort-controller';

const getAction = ( action, studentCount, studentDisplayName ) => {
	const safeStudentDisplayName = escapeHTML( studentDisplayName );

	const possibleActions = {
		add: {
			description:
				studentCount > 1
					? sprintf(
							// Translators: placeholder is the number of selected students.
							__(
								'Select the course(s) you would like to add <strong>%d students</strong> to:',
								'sensei-lms'
							),
							studentCount
					  )
					: sprintf(
							// Translators: placeholder is the student's name.
							__(
								'Select the course(s) you would like to add <strong>%s</strong> to:',
								'sensei-lms'
							),
							safeStudentDisplayName
					  ),
			buttonLabel: __( 'Add to Course', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to add student. Please try again.',
					'Unable to add students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, courses, { signal } ) =>
				apiFetch( {
					path: '/sensei-internal/v1/course-students/batch',
					method: 'POST',
					data: { student_ids: students, course_ids: courses },
					signal,
				} ),
			isDestructive: false,
		},
		remove: {
			description:
				studentCount > 1
					? sprintf(
							// Translators: placeholder is the number of selected students.
							__(
								'Select the course(s) you would like to remove <strong>%d students</strong> from:',
								'sensei-lms'
							),
							studentCount
					  )
					: sprintf(
							// Translators: placeholder is the student's name.
							__(
								'Select the course(s) you would like to remove <strong>%s</strong> from:',
								'sensei-lms'
							),
							safeStudentDisplayName
					  ),
			buttonLabel: __( 'Remove from Course', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to remove student. Please try again.',
					'Unable to remove students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, courses, { signal } ) =>
				apiFetch( {
					path: '/sensei-internal/v1/course-students/batch',
					method: 'DELETE',
					data: { student_ids: students, course_ids: courses },
					signal,
				} ),
			isDestructive: true,
		},
		'reset-progress': {
			// Translators: placeholder is the number of selected students for plural, student's name for singular.
			description:
				studentCount > 1
					? sprintf(
							// Translators: placeholder is the number of selected students.
							__(
								'Select the course(s) you would like to reset progress from for <strong>%d students</strong>:',
								'sensei-lms'
							),
							studentCount
					  )
					: sprintf(
							// Translators: placeholder is the student's name.
							__(
								'Select the course(s) you would like to reset progress from for <strong>%s</strong>:',
								'sensei-lms'
							),
							safeStudentDisplayName
					  ),
			buttonLabel: __( 'Reset Progress', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to reset progress for this student. Please try again.',
					'Unable to reset progress for these students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, courses, { signal } ) =>
				apiFetch( {
					path: '/sensei-internal/v1/course-progress/batch',
					method: 'DELETE',
					data: { student_ids: students, course_ids: courses },
					signal,
				} ),

			isDestructive: true,
		},
	};
	return possibleActions[ action ];
};

/**
 * Student Actions Modal.
 *
 * @param {Object}   props
 * @param {Object}   props.action             Action that is being performed.
 * @param {Function} props.onClose            Close callback.
 * @param {Array}    props.students           A list of Student ids related to the action should be applied.
 * @param {string}   props.studentDisplayName Name of the student, shown when there's only one student.
 */
export const StudentModal = ( {
	action,
	onClose,
	students,
	studentDisplayName,
} ) => {
	const {
		description,
		buttonLabel,
		errorMessage,
		isDestructive,
		sendAction,
	} = getAction( action, students.length, studentDisplayName );
	const [ selectedCourses, setCourses ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ isSending, setIsSending ] = useState( false );
	const [ error, setError ] = useState( false );
	const { getSignal } = useAbortController();

	const send = useCallback( async () => {
		setIsSending( true );

		try {
			await sendAction(
				students,
				selectedCourses.map( ( course ) => course.id ),
				{ signal: getSignal() }
			);
			onClose( true );
		} catch ( e ) {
			if ( ! getSignal().aborted ) {
				setError( true );
				setIsSending( false );
			}
		}
	}, [ sendAction, students, selectedCourses, onClose, getSignal ] );

	const searchCourses = ( value ) => setSearchQuery( value );

	return (
		<Modal
			className="sensei-student-modal"
			title={ __( 'Choose Course', 'sensei-lms' ) }
			onRequestClose={ () => onClose() }
		>
			<RawHTML>{ description }</RawHTML>

			<InputControl
				iconRight={ search }
				onChange={ searchCourses }
				placeholder={ __( 'Search courses', 'sensei-lms' ) }
				value={ searchQuery }
			/>

			<CourseList
				searchQuery={ searchQuery }
				onChange={ ( courses ) => {
					setCourses( courses );
				} }
			/>

			{ error && (
				<Notice
					status="error"
					isDismissible={ false }
					className="sensei-student-modal__notice"
				>
					{ errorMessage( students ) }
				</Notice>
			) }

			<div className="sensei-student-modal__action">
				<Button
					className={ `sensei-student-modal__action` }
					variant={ isDestructive ? '' : 'primary' }
					onClick={ () => send() }
					disabled={ isSending || selectedCourses.length === 0 }
					isDestructive={ isDestructive }
				>
					{ isSending && <Spinner /> }
					{ buttonLabel }
				</Button>
			</div>
		</Modal>
	);
};

export default StudentModal;
