/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const ExtensionActions = () => (
	<ul className="sensei-extensions__extension-actions">
		<li>
			<button className="button button-primary">
				{ __( 'Install', 'sensei-lms' ) }
			</button>
		</li>
		<li>
			<a
				href="#more-details"
				className="sensei-extensions__extension-actions__details-link"
			>
				{ __( 'More details', 'sensei-lms' ) }
			</a>
		</li>
	</ul>
);

export default ExtensionActions;
