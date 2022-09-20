/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Col, Grid } from '../grid';
import Card from '../card';
import Section from '../section';

/**
 * Extensions section component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions List of extensions to show.
 */
const Extensions = ( { extensions } ) => (
	<Section title={ __( 'Extensions', 'sensei-lms' ) }>
		<Grid>
			{ extensions.map( ( extension ) => (
				<Col
					key={ extension.product_slug }
					as="section"
					className="sensei-extensions__card-wrapper"
					cols={ 4 }
				>
					<Card { ...extension } />
				</Col>
			) ) }
		</Grid>
	</Section>
);

export default Extensions;
