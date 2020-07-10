import { H, Section } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import UploadLevels from '../upload-level';
import { Notice } from '../../notice';
import { Button } from '@wordpress/components';

/**
 * This component displays the upload page of the importer.
 */
export const UploadPage = ( { state, isReady, submitStartImport } ) => {
	const { isSubmitting, errorMsg } = state;

	return (
		<section className="sensei-data-port-step sensei-upload-page">
			<header className="sensei-data-port-step__header">
				<H>{ __( 'Import content from a CSV file', 'sensei-lms' ) }</H>
				<p>
					{ __(
						'This tool allows you to import courses, lessons, and questions from a CSV file.',
						'sensei-lms'
					) }
				</p>
			</header>
			<Section
				className="sensei-data-port-step__body"
				component="section"
			>
				<p>
					{ __(
						'Choose one or more CSV files to upload from your computer.',
						'sensei-lms'
					) }
				</p>
				<UploadLevels />
				<div className="sensei-data-port-step__footer">
					{ errorMsg !== null && (
						<Notice message={ errorMsg } isError />
					) }
					<Button
						isPrimary
						className="continue-button"
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
