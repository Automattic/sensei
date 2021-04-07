/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from './extension-actions';

const UpdateNotification = () => (
	<div role="alert">
		<h2>{ __( 'Update available', 'sensei-lms' ) }</h2>
		<h3>WooCommerce Paid Courses v2.1.4</h3>
		<p>
			Lorem ipsum dolor sit amet, consectertur adipiscing elit. Enim cras
			odio netus mi. Maecenas
		</p>
		<ExtensionActions />
	</div>
);

export default UpdateNotification;
