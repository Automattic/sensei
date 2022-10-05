/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

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
 * @return {{percentage: number, label: string, error: object}} Current action data.
 */
const useActionsNavigator = ( actions ) => {
	const [ currentAction, setCurrentAction ] = useState();
	const [ error, setError ] = useState( false );

	// Navigate through the actions.
	useEffect( () => {
		// This is to make sure that the bar will run the CSS transition for the first step.
		if ( undefined === currentAction ) {
			setTimeout( () => {
				setCurrentAction( 0 );
			} );
			return;
		}

		// Run action.
		setError( false );
		Promise.all( [
			minimumTimerPromise(),
			actions[ currentAction ]?.action?.(),
		] )
			.then( () => {
				if ( currentAction + 1 < actions.length ) {
					setCurrentAction( ( prev ) => prev + 1 );
				}
			} )
			.catch( ( err ) => {
				setError( err );
			} );
	}, [ currentAction, actions ] );

	const stepNumber = currentAction + ( error ? 0 : 1 );

	return {
		percentage: ( stepNumber / actions.length ) * 100 || 0,
		label: actions[ currentAction ]?.label,
		error,
	};
};

export default useActionsNavigator;
