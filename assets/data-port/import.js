import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	render,
	useState,
	useEffect,
	useLayoutEffect,
} from '@wordpress/element';
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

	const { fetchCurrentJobState } = useDispatch( 'sensei/import' );

	const [ currentStep, setCurrentStep ] = useState();

	useEffect( () => {
		const newStep = navigationSteps.find( ( step ) => step.isNext );

		if ( 'complete' === newStep.key ) {
			setTimeout( () => {
				setCurrentStep( newStep );
			}, 1000 ); // CSS animation time to complete the progress bar.
		} else {
			setCurrentStep( newStep );
		}
	}, [ navigationSteps ] );

	// We want to show the loading before any content.
	useLayoutEffect( () => {
		fetchCurrentJobState();
	}, [ fetchCurrentJobState ] );

	// Add `sensei-color` to body tag.
	useLayoutEffect( () => {
		document.body.classList.add( [ 'sensei-color' ] );

		return () => document.body.classList.remove( [ 'sensei-color' ] );
	} );

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

	return (
		<div className="sensei-import-wrapper">
			<DataPortStepper steps={ navigationSteps } />
			{ currentStep.container }
		</div>
	);
};

render( <SenseiImportPage />, document.getElementById( 'sensei-import-page' ) );
