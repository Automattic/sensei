/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../section';

/**
 * Sensei Guides section component.
 */
const SenseiGuides = () => (
	<Section title={ __( 'Sensei Guides', 'sensei-lms' ) }>
		<ul>
			<li>Test 3</li>
		</ul>
	</Section>
);

export default SenseiGuides;
