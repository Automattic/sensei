import { __ } from '@wordpress/i18n';
import { Section, H } from '@woocommerce/components';
import { UploadLevels } from '../upload-level';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { moveToNextAction } from '../../stepper';
import apiFetch from '@wordpress/api-fetch';
import { Notice } from '../../notice';

/**
 * This component displays the upload page of the importer.
 *
 * @param {Function} importerDispatch  The dispatch method of the importer.
 */
export const UploadPage = ( { importerDispatch } ) => {
	const [ isReady, setStatus ] = useState( false );
	const [ continueClicked, setContinueClicked ] = useState( false );
	const [ errorMsg, setErrorMsg ] = useState( null );

	/**
	 * Helper method to begin importing.
	 */
	const startImport = () => {
		setContinueClicked( true );
		setErrorMsg( null );

		apiFetch( {
			path: '/sensei-internal/v1/import/start',
			method: 'POST',
		} )
			.then( () => {
				importerDispatch( moveToNextAction() );
			} )
			.catch( ( error ) => {
				setErrorMsg( error.message );
			} )
			.finally( () => setContinueClicked( false ) );
	};

	return (
		<section className={ 'sensei-import-form' }>
			<header className={ 'sensei-import-form__header' }>
				<H>{ __( 'Import content from a CSV file', 'sensei-lms' ) }</H>
				<p>
					{ __(
						'This tool allows you to import courses, lessons, and questions from a CSV file.',
						'sensei-lms'
					) }
				</p>
			</header>
			<Section
				className={ 'sensei-import-form__body' }
				component={ 'section' }
			>
				<p>
					{ __(
						'Choose one or more CSV files to upload from your computer.',
						'sensei-lms'
					) }
				</p>
				<UploadLevels setReadyStatus={ setStatus } />
				<div className={ 'continue-container' }>
					{ errorMsg !== null && (
						<Notice message={ errorMsg } isError />
					) }
					<Button
						isPrimary
						disabled={ ! isReady || continueClicked }
						onClick={ startImport }
					>
						{ __( 'Continue', 'sensei-lms' ) }
					</Button>
				</div>
			</Section>
		</section>
	);
};
