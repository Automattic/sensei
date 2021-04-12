/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from '../extension-actions';

/**
 * Single update notification.
 *
 * @param {Object} props           Component props.
 * @param {Object} props.extension Extension with update.
 */
const Single = ( { extension } ) => (
	<>
		<h3 className="sensei-extensions__update-notification__title">
			{ extension.title }
		</h3>
		<p className="sensei-extensions__update-notification__description">
			{ extension.excerpt }
		</p>
		<ExtensionActions
			detailsLink={ extension.link }
			buttonLabel={ __( 'Update', 'sensei-lms' ) }
		/>
	</>
);

export default Single;
