/**
 * WordPress dependencies
 */
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import { useCallback, useState, RawHTML } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { search } from '@wordpress/icons';
import { __, _n, sprintf } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import ItemList from './item-list';
import InputControl from '../../../blocks/editor-components/input-control';
import useAbortController from '../hooks/use-abort-controller';

const getPossibleActions = ( studentCount, studentDisplayName ) => {
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
			sendAction: ( students, items, { signal } ) =>
				apiFetch( {
					path: '/sensei-internal/v1/course-students/batch',
					method: 'POST',
					data: { student_ids: students, course_ids: items },
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
			sendAction: ( students, items, { signal } ) =>
				apiFetch( {
					path: '/sensei-internal/v1/course-students/batch',
					method: 'DELETE',
					data: { student_ids: students, course_ids: items },
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
								'Select the course(s) you would like to reset or remove progress from for <strong>%d students</strong>:',
								'sensei-lms'
							),
							studentCount
					  )
					: sprintf(
							// Translators: placeholder is the student's name.
							__(
								'Select the course(s) you would like to reset or remove progress from for <strong>%s</strong>:',
								'sensei-lms'
							),
							safeStudentDisplayName
					  ),
			buttonLabel: __( 'Reset or Remove Progress', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to reset or remove progress for this student. Please try again.',
					'Unable to reset or remove progress for these students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, items, { signal } ) =>
				apiFetch( {
					path: '/sensei-internal/v1/course-progress/batch',
					method: 'DELETE',
					data: { student_ids: students, course_ids: items },
					signal,
				} ),

			isDestructive: true,
		},
	};

	/**
	 * Filters possible actions in the Student Modal.
	 *
	 * @since 4.8.0
	 *
	 * @param {Object} possibleActions        Dictionary with possible actions.
	 * @param {number} studentCount           Number of selected students.
	 * @param {string} safeStudentDisplayName Student name.
	 *
	 * @return {Object} Filtered possible actions.
	 */
	return applyFilters(
		'senseiStudentModalPossibleActions',
		possibleActions,
		studentCount,
		safeStudentDisplayName
	);
};

const getAction = ( action, studentCount, studentDisplayName ) => {
	const possibleActions = getPossibleActions(
		studentCount,
		studentDisplayName
	);

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
	const [ selectedItems, setItems ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ isSending, setIsSending ] = useState( false );
	const [ error, setError ] = useState( false );
	const { getSignal } = useAbortController();

	const send = useCallback( async () => {
		setIsSending( true );

		try {
			await sendAction(
				students,
				selectedItems.map( ( item ) => item.id ),
				{ signal: getSignal() }
			);
			onClose( true );
		} catch ( e ) {
			if ( ! getSignal().aborted ) {
				setError( true );
				setIsSending( false );
			}
		}
	}, [ sendAction, students, selectedItems, onClose, getSignal ] );

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

			<ItemList
				searchQuery={ searchQuery }
				onChange={ ( items ) => {
					setItems( items );
				} }
				action={ action }
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
					disabled={ isSending || selectedItems.length === 0 }
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
