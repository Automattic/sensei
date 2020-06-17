import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Section, H } from '@woocommerce/components';
import { UploadLevels } from '../upload-level';
import { Button } from '@wordpress/components';
import { Notice } from '../../notice';

/**
 * This component displays the upload page of the importer.
 */
export const UploadPage = () => {
	const { submitStartImport } = useDispatch( 'sensei/import' );

	const { state, isReady } = useSelect( ( select ) => {
		const store = select( 'sensei/import' );
		return {
			state: store.getStepData( 'upload' ),
			isReady: store.isReadyToStart(),
		};
	}, [] );

	const { isSubmitting, errorMsg } = state;

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
				<UploadLevels />
				<div className={ 'continue-container' }>
					{ errorMsg !== null && (
						<Notice message={ errorMsg } isError />
					) }
					<Button
						isPrimary
						disabled={ ! isReady || isSubmitting }
						onClick={ submitStartImport }
					>
						{ __( 'Continue', 'sensei-lms' ) }
					</Button>
				</div>
			</Section>
		</section>
	);
};
