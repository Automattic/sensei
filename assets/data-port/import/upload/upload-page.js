/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import UploadLevels from '../upload-level';
import { Notice } from '../../notice';
import { formatString } from '../../../shared/helpers/format-string.js';
import { H, Section } from '../../../shared/components/section';

/**
 * This component displays the upload page of the importer.
 *
 * @param {Object}   input                   UploadPage input.
 * @param {Object}   input.state             The import state.
 * @param {boolean}  input.isReady           Whether the upload is finished.
 * @param {Function} input.submitStartImport Callback which is called when start button is clicked.
 */
export const UploadPage = ( { state, isReady, submitStartImport } ) => {
	const { isSubmitting, errorMsg } = state;

	return (
		<section className="sensei-data-port-step sensei-upload-page">
			<header className="sensei-data-port-step__header">
				<H>{ __( 'Import content from a CSV file', 'sensei-lms' ) }</H>
				<p>
					{ formatString(
						__(
							'This tool enables you to import courses, lessons, and questions from a CSV file. Please review the {{link}}documentation{{/link}} to learn more about the expected file structure.',
							'sensei-lms'
						),
						{
							link: (
								// eslint-disable-next-line jsx-a11y/anchor-has-content
								<a
									className="link__color-secondary"
									href="https://senseilms.com/lesson/import/"
									target="_blank"
									type="external"
									rel="noopener noreferrer"
								/>
							),
						}
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
