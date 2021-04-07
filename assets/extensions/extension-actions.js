/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const ExtensionActions = () => (
	<ul>
		<li>
			<a href="#learn-more">{ __( 'Learn more', 'sensei-lms' ) }</a>
		</li>
		<li>
			<button>{ __( 'Update', 'sensei-lms' ) }</button>
		</li>
	</ul>
);

export default ExtensionActions;
