/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Preview panel that lists the CSV files an export will produce.
 *
 * @param {Object} props
 * @param {Array}  props.lines Array of { file, detail } describing each CSV file.
 */
export const ExportPreviewPanel = ( { lines } ) => {
	if ( ! lines.length ) {
		return null;
	}

	return (
		<div className="sensei-export__preview">
			<h3 className="sensei-export__preview__heading">
				{ __( 'Your export will include:', 'sensei-lms' ) }
			</h3>
			<ul className="sensei-export__preview__list">
				{ lines.map( ( { file, detail } ) => (
					<li key={ file }>
						<code>{ file }</code>
						{ detail && (
							<span className="sensei-export__preview__detail">
								{ ' — ' }
								{ detail }
							</span>
						) }
					</li>
				) ) }
			</ul>
		</div>
	);
};
