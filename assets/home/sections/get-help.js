/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../section';

/**
 * Get Help section component.
 */
const GetHelp = () => (
	<Section title={ __( 'Get help', 'sensei-lms' ) }>
		<ul>
			<li>Test 2</li>
		</ul>
	</Section>
);

export default GetHelp;
