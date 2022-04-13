/**
 * WordPress dependencies
 */
import { Button, Modal, Spinner } from '@wordpress/components';
import { useCallback, useRef, useState, useEffect } from '@wordpress/element';
import { search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CourseList from './course-list';
import InputControl from '../../../blocks/editor-components/input-control';
import httpClient from '../../lib/http-client';

/**
 * External dependencies
 */

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
		isDestructive: false,
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
		isDestructive: true,
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

		isDestructive: true,
	},
};

/**
 * Questions modal content.
 *
 * @param {Object}   props
 * @param {Object}   props.action   Action that is being performed.
 * @param {Function} props.onClose  Close callback.
 * @param {Array}    props.students A list of Student ids related to the action should be applied.
 */
export const StudentModal = ( { action, onClose, students } ) => {
	const {
		description,
		buttonLabel,
		sendAction,
		isDestructive,
	} = POSSIBLE_ACTIONS[ action ];
	const [ selectedCourses, setCourses ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ isSending, setIsSending ] = useState( false );
	const [ hasError, setError ] = useState( false );
	const isMounted = useRef( true );

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
			<p>{ description }</p>

			<InputControl
				iconRight={ search }
				onChange={ searchCourses }
				placeholder={ __( 'Search courses', 'sensei-lms' ) }
				value={ searchQuery }
			/>

			{ hasError && <h1>Sorry, something went wrong</h1> }

			<CourseList
				searchQuery={ searchQuery }
				onChange={ ( courses ) => {
					setCourses( courses );
				} }
			/>
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
