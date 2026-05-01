/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Step 1: pick how the user wants to scope the export.
 *
 * @param {Object}   props
 * @param {Function} props.onPickMode Called with 'by_course' or 'by_file_type'.
 */
export const ExportModePickerPage = ( { onPickMode } ) => {
	return (
		<div className="sensei-data-port-step__body">
			<p className="sensei-export__mode-picker__label">
				{ __(
					'How would you like to export your content?',
					'sensei-lms'
				) }
			</p>
			<div className="sensei-export__mode-picker__options">
				<button
					type="button"
					className="sensei-export__mode-picker__option"
					onClick={ () => onPickMode( 'by_course' ) }
				>
					<span className="sensei-export__mode-picker__option-title">
						{ __( 'By course', 'sensei-lms' ) }
					</span>
					<span className="sensei-export__mode-picker__option-description">
						{ __(
							'Pick courses and get all of their lessons and questions in the bundle.',
							'sensei-lms'
						) }
					</span>
				</button>
				<button
					type="button"
					className="sensei-export__mode-picker__option"
					onClick={ () => onPickMode( 'by_file_type' ) }
				>
					<span className="sensei-export__mode-picker__option-title">
						{ __( 'By file type', 'sensei-lms' ) }
					</span>
					<span className="sensei-export__mode-picker__option-description">
						{ __(
							'Choose which CSV files to generate. Each file contains exactly the items you select.',
							'sensei-lms'
						) }
					</span>
				</button>
			</div>
			<div className="sensei-data-port-step__footer">
				<Button
					isLink
					href="https://senseilms.com/documentation/import-export/"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Learn more about exporting', 'sensei-lms' ) }
				</Button>
			</div>
		</div>
	);
};
