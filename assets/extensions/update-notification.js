/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, update } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ExtensionActions from './extension-actions';

const UpdateNotification = () => (
	<div role="alert" className="sensei-extensions__update-notification">
		<small className="sensei-extensions__update-badge">
			<Icon icon={ update } />
			{ __( 'Update available', 'sensei-lms' ) }
		</small>
		<h3 className="sensei-extensions__update-notification__title">
			WooCommerce Paid Courses v2.1.4
		</h3>
		<p className="sensei-extensions__update-notification__description">
			Lorem ipsum dolor sit amet, consectertur adipiscing elit. Enim cras
			odio netus mi. Maecenas
		</p>
		<ExtensionActions />
	</div>
);

export default UpdateNotification;
