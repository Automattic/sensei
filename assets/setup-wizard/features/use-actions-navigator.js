/**
 * WordPress dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// Minimum timer for the actions, to make sure the user will have time to read the texts.
export const actionMinimumTimer = 1500;

/**
 * Function that simulates an action, returning a promise with the minimum time.
 *
 * @return {Promise} Promise that resolves after a minimum timer.
 */
const minimumTimerPromise = () =>
	new Promise( ( resolve ) => {
		setTimeout( () => {
			resolve();
		}, actionMinimumTimer );
	} );

/**
 * Actions navigator hook.
 *
 * @param {Array} actions
 *
 * @return {{percentage: number, label: string, error: object, errorActions: object}} Current action data.
 */
const useActionsNavigator = ( actions ) => {
	const [ currentAction, setCurrentAction ] = useState();
	const [ error, setError ] = useState( false );
	const action = actions[ currentAction ]?.action;

	const goToNextAction = useCallback( () => {
		if ( currentAction + 1 < actions.length ) {
			setCurrentAction( ( prev ) => prev + 1 );
		}
	}, [ currentAction, actions.length ] );

	const runAction = useCallback( () => {
		setError( false );

		const actionPromise = action?.();

		Promise.all( [ minimumTimerPromise(), actionPromise ] )
			.then( () => {
				goToNextAction();
			} )
			.catch( ( err ) => {
				setError( err );
			} );

		return actionPromise?.clearAction;
	}, [ action, goToNextAction ] );

	// Navigate through the actions.
	useEffect( () => {
		// This is to make sure that the bar will run the CSS transition for the first step.
		if ( undefined === currentAction ) {
			setTimeout( () => {
				setCurrentAction( 0 );
			} );
			return;
		}

		const clearAction = runAction();

		return () => {
			clearAction?.();
		};
	}, [ currentAction, runAction ] );

	const errorActions = error && [
		{
			variant: 'primary',
			label: __( 'Retry', 'sensei-lms' ),
			onClick: runAction,
		},
		{
			label: __( 'Skip', 'sensei-lms' ),
			onClick: goToNextAction,
		},
	];

	const stepNumber = currentAction + ( error ? 0 : 1 );

	return {
		percentage: ( stepNumber / actions.length ) * 100 || 0,
		label: actions[ currentAction ]?.label,
		error,
		errorActions,
	};
};

export default useActionsNavigator;
