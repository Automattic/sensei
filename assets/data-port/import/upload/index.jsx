import { __ } from '@wordpress/i18n';
import { Section, H } from '@woocommerce/components';
import { UploadLevels } from '../upload-level';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { moveToNextAction } from '../../stepper';

/**
 * This component displays the upload page of the importer.
 *
 * @param {Function} importerDispatch  The dispatch method of the importer.
 */
export const UploadPage = ( { importerDispatch } ) => {
	const [ isReady, setStatus ] = useState( false );

	/**
	 * Helper method to begin importing.
	 */
	const startImport = () => {
		importerDispatch( moveToNextAction() );
	};

	return (
		<section className={ 'sensei-import-form' }>
			<header className={ 'sensei-import-form__header' }>
				<H>{ __( 'Import products from a CSV file', 'sensei-lms' ) }</H>
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
					<Button
						isPrimary
						disabled={ ! isReady }
						onClick={ startImport }
					>
						{ __( 'Continue', 'sensei-lms' ) }
					</Button>
				</div>
			</Section>
		</section>
	);
};
