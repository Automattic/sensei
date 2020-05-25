import { __ } from '@wordpress/i18n';
import { render, useReducer } from '@wordpress/element';
import { DataPortStepper, stepsReducer, getCurrentStep } from './stepper';
import { UploadPage } from './import/upload';

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
			{ ( () => {
				switch ( getCurrentStep( steps ) ) {
					case 'upload':
						return <UploadPage />;
					default:
						return null;
				}
			} )() }
		</div>
	);
};

render( <SenseiImportPage />, document.getElementById( 'sensei-import-page' ) );
