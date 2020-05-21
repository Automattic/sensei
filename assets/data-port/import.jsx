import { __ } from '@wordpress/i18n';
import { render, useReducer } from '@wordpress/element';
import { DataPortStepper, stepsReducer, getCurrentStep } from './stepper';

const initialSteps = [
	{
		key: 'upload',
		description: __( 'Upload CSV Files', 'sensei-lms' ),
		isActive: true,
		isComplete: false,
	},
	{
		key: 'import',
		description: __( 'Import', 'sensei-lms' ),
		isActive: false,
		isComplete: false,
	},
	{
		key: 'completed',
		description: __( 'Done', 'sensei-lms' ),
		isActive: false,
		isComplete: false,
	},
];

/**
 * Sensei onboarding page.
 */
const SenseiImportPage = () => {
	const [ steps, dispatch ] = useReducer( stepsReducer, initialSteps );

	return (
		<div className="sensei-import-wrapper">
			<DataPortStepper steps={ steps } />
			<button onClick={ () => dispatch( { type: 'MOVE_TO_NEXT'} ) }>Move to next step!</button>
			<button onClick={ () => dispatch( { type: 'COMPLETE_CURRENT'} ) }>Complete current!</button>
			{ /* eslint-disable-next-line no-console */ }
			<button onClick={ () => console.log( getCurrentStep( steps ) ) }>Check current step!</button>
		</div>
	);
};

render( <SenseiImportPage />, document.getElementById( 'sensei-import-page' ) );
