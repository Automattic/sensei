import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { render, useLayoutEffect } from '@wordpress/element';
import { DataPortStepper } from './stepper';
import registerImportStore from './import/data';
import { Spinner } from '@woocommerce/components';
import { Notice } from '@wordpress/components';

registerImportStore();

/**
 * Sensei import page.
 */
const SenseiImportPage = () => {
	const { isFetching, error, navigationSteps } = useSelect( ( select ) => {
		const store = select( 'sensei/import' );
		return {
			isFetching: store.isFetching(),
			error: store.getFetchError(),
			navigationSteps: store.getNavigationSteps(),
		};
	}, [] );

	const { getCurrentJobState } = useDispatch( 'sensei/import' );

	// We want to show the loading before any content.
	useLayoutEffect( () => {
		getCurrentJobState();
	}, [ getCurrentJobState ] );

	if ( isFetching ) {
		return <Spinner className="sensei-import__main-loader" />;
	}

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
		<div className="sensei-import-wrapper">
			<DataPortStepper steps={ navigationSteps } />
			{ currentStep.container }
		</div>
	);
};

render( <SenseiImportPage />, document.getElementById( 'sensei-import-page' ) );
