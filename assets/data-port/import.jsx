import { __ } from '@wordpress/i18n';
import { render, useState } from '@wordpress/element';
import DataPortStepper from './stepper';

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
	const [ steps, setSteps ] = useState( initialSteps );

	function moveToNext() {
		const newSteps = [ ...steps ];

		for ( let i = 0; i < newSteps.length; i++ ) {
			if ( ! newSteps[ i ].isComplete ) {
				newSteps[ i ].isComplete = true;
				newSteps[ i ].isActive = false;

				if ( i + 1 < newSteps.length ) {
					newSteps[ i + 1 ].isActive = true;
				}

				setSteps( newSteps );
				return;
			}
		}
	}

	return (
		<div className="sensei-import-wrapper">
			<DataPortStepper steps={ steps } />
			<button onClick={ moveToNext }>Try me!</button>
		</div>
	);
};

render( <SenseiImportPage />, document.getElementById( 'sensei-import-page' ) );
