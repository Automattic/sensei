/**
 * WordPress dependencies
 */
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import {
	useCallback,
	useRef,
	useState,
	useEffect,
	RawHTML,
} from '@wordpress/element';
import { search } from '@wordpress/icons';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { escape } from 'lodash';

/**
 * Internal dependencies
 */
import CourseList from './course-list';
import InputControl from '../../../blocks/editor-components/input-control';
import httpClient from '../../lib/http-client';

const getAction = ( action, studentCount ) => {
	const possibleActions = {
		add: {
			// Translators: placeholder is the number of selected students for plural, student's name for singular.
			description: _n(
				'Select the course(s) you would like to add <strong>%1$s</strong> to:',
				'Select the course(s) you would like to add <strong>%1$d students</strong> to:',
				studentCount,
				'sensei-lms'
			),
			buttonLabel: __( 'Add to Course', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to add student. Please try again.',
					'Unable to add students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, courses ) =>
				httpClient( {
					restRoute: '/sensei-internal/v1/course-students/batch',
					method: 'POST',
					data: { student_ids: students, course_ids: courses },
				} ),
			isDestructive: false,
		},
		remove: {
			// Translators: placeholder is the number of selected students for plural, student's name for singular.
			description: _n(
				'Select the course(s) you would like to remove <strong>%1$s</strong> from:',
				'Select the course(s) you would like to remove <strong>%1$d students</strong> from:',
				studentCount,
				'sensei-lms'
			),
			buttonLabel: __( 'Remove from Course', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to remove student. Please try again.',
					'Unable to remove students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, courses ) =>
				httpClient( {
					restRoute: '/sensei-internal/v1/course-students/batch',
					method: 'DELETE',
					data: { student_ids: students, course_ids: courses },
				} ),
			isDestructive: true,
		},
		'reset-progress': {
			// Translators: placeholder is the number of selected students for plural, student's name for singular.
			description: _n(
				'Select the course(s) you would like to reset or remove progress from for <strong>%1$s</strong>:',
				'Select the course(s) you would like to reset or remove progress from for <strong>%1$d students</strong>:',
				studentCount,
				'sensei-lms'
			),
			buttonLabel: __( 'Reset or Remove Progress', 'sensei-lms' ),
			errorMessage: ( students ) =>
				_n(
					'Unable to reset or remove progress for this student. Please try again.',
					'Unable to reset or remove progress for these students. Please try again.',
					students.length,
					'sensei-lms'
				),
			sendAction: ( students, courses ) =>
				httpClient( {
					restRoute: '/sensei-internal/v1/course-progress/batch',
					method: 'DELETE',
					data: { student_ids: students, course_ids: courses },
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
	} = getAction( action, students.length );
	const [ selectedCourses, setCourses ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ isSending, setIsSending ] = useState( false );
	const [ error, setError ] = useState( false );
	const isMounted = useRef( true );
	const replacementString =
		students.length === 1 ? escape( studentDisplayName ) : students.length;
	const replacedDescription = sprintf( description, replacementString );

	useEffect( () => {
		return () => ( isMounted.current = false );
	}, [ isMounted ] );

	const send = useCallback( async () => {
		setIsSending( true );

		try {
			await sendAction(
				students,
				selectedCourses.map( ( course ) => course.id )
			);
			onClose( true );
		} catch ( e ) {
			if ( isMounted.current ) {
				setError( true );
				setIsSending( false );
			}
		}
	}, [ sendAction, students, selectedCourses, onClose ] );

	const searchCourses = ( value ) => setSearchQuery( value );

	return (
		<Modal
			className="sensei-student-modal"
			title={ __( 'Choose Course', 'sensei-lms' ) }
			onRequestClose={ () => onClose() }
		>
			<RawHTML>{ replacedDescription }</RawHTML>

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
