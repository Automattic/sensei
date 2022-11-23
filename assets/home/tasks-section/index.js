/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useEffect, useState, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Col } from '../grid';
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
			setTimeout( () => {
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
			}, 1500 );
		}
	}, [ totalTasks, totalCompletedTasks, ready ] );

	return {
		ready,
		readyError,
	};
};

/**
 * Dismiss hook.
 *
 * @return {{dismissed:boolean, onDismiss:Function}} Object with dismissed and onDismiss callback.
 */
const useDismiss = () => {
	const [ dismissed, setDismissed ] = useState( false );

	const onDismiss = () => {
		setDismissed( true );
	};

	return { dismissed, onDismiss };
};

/**
 * Col preparation to height animation. Basically, it sets a max-height in the component based on
 * its real height.
 *
 * @return {{colRef:Object, colStyle:Object}} Object with colRef and colStyle.
 */
const useColPreparationToHeightAnimation = () => {
	const colRef = useRef();
	const [ colStyle, setColStyle ] = useState( {} );

	useEffect( () => {
		if ( ! colRef.current ) {
			return;
		}

		setColStyle( {
			overflow: 'hidden',
			maxHeight: colRef.current.offsetHeight,
		} );
	}, [] );

	return { colRef, colStyle };
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

	const { dismissed, onDismiss } = useDismiss();

	const { colRef, colStyle } = useColPreparationToHeightAnimation();

	if ( window.sensei_home.tasks_dismissed ) {
		return null;
	}

	let content;

	if ( readyError ) {
		content = (
			<Notice status="error" isDismissible={ false }>
				{ readyError.message }
			</Notice>
		);
	} else if ( ready ) {
		content = (
			<Ready
				coursePermalink={ data.course?.permalink }
				onDismiss={ onDismiss }
			/>
		);
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
						siteTitle={ data.site?.title }
						siteImage={ data.site?.image }
						courseTitle={ data.course?.title }
						courseImage={ data.course?.image }
					/>
				</div>
			</>
		);
	}

	return (
		<Col
			as="section"
			className={ classnames( 'sensei-home__section', {
				'sensei-home__section--dismissed': dismissed,
			} ) }
			cols={ 12 }
			ref={ colRef }
			style={ colStyle }
		>
			<Section
				className={ classnames( 'sensei-home-tasks-section', {
					'sensei-home-tasks-section--ready': ready,
				} ) }
				insideClassName="sensei-home-tasks-section__inside"
			>
				{ content }
			</Section>
		</Col>
	);
};

export default TasksSection;
