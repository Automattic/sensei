/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Extension actions component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.buttonLabel Button label.
 * @param {string} props.detailsLink Details link.
 */
const ExtensionActions = ( { buttonLabel, detailsLink } ) => (
	<ul className="sensei-extensions__extension-actions">
		<li className="sensei-extensions__extension-actions__item">
			<button className="button button-primary">
				{ buttonLabel || __( 'Install', 'sensei-lms' ) }
			</button>
		</li>
		{ detailsLink && (
			<li className="sensei-extensions__extension-actions__item">
				<a
					href={ detailsLink }
					className="sensei-extensions__extension-actions__details-link"
					target="_blank"
					rel="noreferrer external"
				>
					{ __( 'More details', 'sensei-lms' ) }
				</a>
			</li>
		) }
	</ul>
);

export default ExtensionActions;
