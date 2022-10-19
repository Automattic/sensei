/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../section';
import Link from '../link';

/**
 * Sensei Guides section component.
 *
 * @param {Object} props
 * @param {Object} props.data
 */
const SenseiGuides = ( { data } ) => {
	if ( ! data ) {
		return null;
	}

	return (
		<Section
			title={ __( 'Sensei Guides', 'sensei-lms' ) }
			className="sensei-home-guides"
		>
			<ul>
				{ data.items.map( ( item, key ) => (
					<li key={ key }>
						<Link label={ item.title } url={ item.url } />
					</li>
				) ) }
			</ul>
			{ data.more_url && (
				<div className="sensei-home-guides__more-link">
					<Link
						label={ __( 'See more', 'sensei-lms' ) }
						url={ data.more_url }
					/>
				</div>
			) }
		</Section>
	);
};

export default SenseiGuides;
