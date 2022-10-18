/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

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
			className="sensei-home__guides"
		>
			<ul>
				{ data.items.map( ( item, key ) => (
					<li key={ key }>
						<Link
							label={ decodeEntities( item.title ) }
							url={ item.url }
						/>
					</li>
				) ) }
			</ul>
			{ data.more_url && (
				<div className="sensei-home__guides__more-link">
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
