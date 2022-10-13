/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import Section from '../section';
import Progress from './progress';
import Tasks from './tasks';
import FirstCourse from './first-course';
import Ready from './ready';

/**
 * Hook to update the state to ready when all tasks are completed.
 *
 * @param {boolean} isServerCompleted   Whether tasks were already completed in the server.
 * @param {number}  totalTasks          Number of tasks.
 * @param {number}  totalCompletedTasks Number of completed tasks.
 */
const useReadyState = (
	isServerCompleted,
	totalTasks,
	totalCompletedTasks
) => {
	const [ ready, setReady ] = useState( isServerCompleted );
	const [ readyError, setReadyError ] = useState( false );

	useEffect( () => {
		if ( ! ready && totalCompletedTasks === totalTasks ) {
			apiFetch( {
				path: '/sensei-internal/v1/home/tasks/complete',
				method: 'POST',
			} )
				.then( () => {
					setReady( true );
				} )
				.catch( ( err ) => {
					setReadyError( err );
				} );
		}
	}, [ totalTasks, totalCompletedTasks, ready ] );

	return {
		ready,
		readyError,
	};
};

/**
 * Tasks section component.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.data Tasks data.
 */
const TasksSection = ( { data } ) => {
	const items = Object.values( data.items );

	const sortedItems = items.sort( ( a, b ) => a.priority - b.priority );
	const completedItems = sortedItems.filter( ( i ) => i.done );

	const { ready, readyError } = useReadyState(
		data.is_completed,
		items.length,
		completedItems.length
	);

	let content;

	if ( readyError ) {
		content = (
			<Notice status="error" isDismissible={ false }>
				{ readyError.message }
			</Notice>
		);
	} else if ( ready ) {
		content = <Ready />;
	} else {
		content = (
			<>
				<div className="sensei-home-tasks-section__content">
					<Progress
						totalTasks={ items.length }
						completedTasks={ completedItems.length }
					/>
					<Tasks items={ sortedItems } />
				</div>
				<div className="sensei-home-tasks-section__first-course">
					<FirstCourse
						siteTitle="Learn Photography"
						courseTitle="Architectural Photography"
						siteLogo="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
						featuredImage="https://techcrunch.com/wp-content/uploads/2022/06/Leica-on-black.jpeg?w=1390&crop=1"
					/>
				</div>
			</>
		);
	}

	return (
		<Section
			className={ classnames( 'sensei-home-tasks-section', {
				'sensei-home-tasks-section--ready': ready,
			} ) }
			insideClassName="sensei-home-tasks-section__inside"
		>
			{ content }
		</Section>
	);
};

export default TasksSection;
