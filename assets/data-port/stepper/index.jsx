import classnames from 'classnames';

/**
 * Helper method to update the steps when moving to the next one.
 *
 * @param  {Array} steps  The current steps.
 * @return {Array}        The steps after moving.
 */
const moveToNext = ( steps ) => {
	const newSteps = [ ...steps ];

	for ( let i = 0; i < newSteps.length; i++ ) {
		if ( newSteps[ i ].isActive ) {
			newSteps[ i ].isComplete = true;

			if ( i + 1 < newSteps.length ) {
				newSteps[ i + 1 ].isActive = true;
				newSteps[ i ].isActive = false;
			}

			return newSteps;
		}
	}

	throw new Error( 'No active step.' );
};

/**
 * Helper method to update the steps when the active step is completed.
 *
 * @param  {Array} steps  The current steps.
 * @return {Array}        The steps after completing the current one.
 */
const completeCurrentStep = ( steps ) => {
	const newSteps = steps.map( ( step ) => {
		if ( step.isActive ) {
			step.isComplete = true;
		}

		return step;
	} );

	return newSteps;
};

/**
 * Get the key of the current active step.
 *
 * @param  {Array} steps  The current steps.
 * @return {string}       The key of the active step.
 */
const getCurrentStep = ( steps ) => {
	for ( const step of steps ) {
		if ( step.isActive ) {
			return step.key;
		}
	}

	throw new Error( 'No active step.' );
};

const stepsReducer = ( state, action ) => {
	switch ( action.type ) {
		case 'MOVE_TO_NEXT':
			return moveToNext( state );
		case 'COMPLETE_CURRENT':
			return completeCurrentStep( state );
		default:
			throw new Error( `Unknown action ${ action.type }.` );
	}
};

/**
 * @typedef  {Object} Step
 * @property {string} key          Unique key for the step.
 * @property {string} description  A description of the step that is going to be displayed.
 * @property {string} isActive     True if the step is the currently active one.
 * @property {string} isComplete   True if the step is completed.
 */
/**
 * A simple component to display a stepper on data port pages.
 *
 * @param {Array} steps The array of the steps.
 */
const DataPortStepper = ( { steps } ) => (
	<ol className="sensei-progress-steps">
		{ steps.map( ( step ) => {
			const stepClass = classnames( {
				active: step.isActive,
				done: step.isComplete,
			} );

			return (
				<li key={ step.key } className={ stepClass }>
					{ step.description }
				</li>
			);
		} ) }
	</ol>
);

export { DataPortStepper, getCurrentStep, stepsReducer };
