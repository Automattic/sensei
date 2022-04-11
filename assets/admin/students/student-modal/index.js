/**
 * WordPress dependencies
 */
import { Button, Modal, Spinner } from '@wordpress/components';
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CourseList from './course-list';
import InputControl from '../../../blocks/editor-components/input-control';
import httpClient from './lib/http-client';

/**
 * External dependencies
 */
import { useCallback, useRef, useState, useEffect } from 'react';

const POSSIBLE_ACTIONS = {
	add: {
		description: __(
			'Select the course(s) you would like to add students to:',
			'sensei-lms'
		),
		buttonLabel: __( 'Add to Course', 'sensei-lms' ),
		sendAction: ( students, courses ) =>
			httpClient( {
				restRoute: '/sensei-internal/v1/course-students/batch',
				method: 'POST',
				data: { student_ids: students, course_ids: courses },
			} ),
	},
	remove: {
		description: __(
			'Select the course(s) you would like to remove students from:',
			'sensei-lms'
		),
		buttonLabel: __( 'Remove from Course', 'sensei-lms' ),
		sendAction: ( students, courses ) =>
			httpClient( {
				restRoute: '/sensei-internal/v1/course-students/batch',
				method: 'DELETE',
				data: { student_ids: students, course_ids: courses },
			} ),
	},
	'reset-progress': {
		description: __(
			'Select the course(s) you would like to reset or remove progress for:',
			'sensei-lms'
		),
		buttonLabel: __( 'Reset or Remove Progress', 'sensei-lms' ),
		sendAction: ( students, courses ) =>
			httpClient( {
				restRoute: '/sensei-internal/v1/course-progress/batch',
				method: 'DELETE',
				data: { student_ids: students, course_ids: courses },
			} ),
	},
};

/**
 * Questions modal content.
 *
 * @param {Object}   props
 * @param {Object}   props.action   Action that is being performed.
 * @param {Function} props.onClose  Close callback.
 * @param {Array}    props.students
 */
export const StudentModal = ( { action, onClose, students } ) => {
	const { description, buttonLabel, sendAction } = POSSIBLE_ACTIONS[ action ];
	const selectedCourses = useRef( [] );
	const [ isSending, setIsSending ] = useState( false );
	const [ hasError, setError ] = useState( false );
	const mounted = useRef( true );

	useEffect( () => () => ( mounted.current = false ) );

	const send = useCallback( async () => {
		setIsSending( true );
		try {
			await sendAction(
				students,
				selectedCourses.current.map( ( course ) => course.id )
			);
			setIsSending( false );
			onClose( true );
		} catch ( e ) {
			if ( mounted.current ) {
				setError( true );
				setIsSending( false );
			}
		}
	}, [ sendAction, students, onClose ] );

	return (
		<Modal
			className="sensei-student-modal"
			title={ __( 'Choose Course', 'sensei-lms' ) }
			onRequestClose={ () => onClose() }
		>
			<p>{ description }</p>
			{ isSending && <Spinner /> }
			{ hasError && <h1>Sorry, something went wrong</h1> }
			<InputControl
				placeholder={ __( 'Search courses', 'sensei-lms' ) }
				iconRight={ search }
			/>
			<CourseList
				onChange={ ( courses ) => {
					selectedCourses.current = courses;
				} }
			/>
			<div className="sensei-student-modal__action">
				<Button
					className={ `sensei-student-modal__action--${ action }` }
					variant="primary"
					onClick={ () => send() }
				>
					{ buttonLabel }
				</Button>
			</div>
		</Modal>
	);
};

export default StudentModal;
