/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { render, useLayoutEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';
import { DataPortStepper } from './stepper';
import registerImportStore from './import/data';
import { Notice } from '@wordpress/components';
import '../shared/data/api-fetch-preloaded-once';

registerImportStore();

/**
 * Sensei import page.
 */
const SenseiImportPage = () => {
	const { error, navigationSteps } = useSelect( ( select ) => {
		const store = select( 'sensei/import' );
		return {
			error: store.getFetchError(),
			navigationSteps: store.getNavigationSteps(),
		};
	}, [] );

	const { loadCurrentJobState } = useDispatch( 'sensei/import' );

	useLayoutEffect( () => {
		loadCurrentJobState();
	}, [ loadCurrentJobState ] );

	useSenseiColorTheme();

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __(
					'An error has occurred while fetching the data. Please try again later!',
					'sensei-lms'
				) }
				<br />
				{ __( 'Error details:', 'sensei-lms' ) } { error.message }
			</Notice>
		);
	}

	const currentStep = navigationSteps.find( ( step ) => step.isNext );

	return (
		<div className="sensei-page-import">
			<DataPortStepper steps={ navigationSteps } />
			{ currentStep.container }
		</div>
	);
};

render( <SenseiImportPage />, document.getElementById( 'sensei-import-page' ) );
