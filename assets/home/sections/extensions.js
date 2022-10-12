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
 * @param {Object}   props
 * @param {Object[]} props.extensions
 */
const Extensions = ( { extensions } ) => {
	if ( extensions === undefined || extensions.length === 0 ) {
		return null;
	}

	return (
		<Section title={ __( 'Extensions', 'sensei-lms' ) }>
			<Grid>
				{ extensions.map( ( extension ) => {
					return (
						<Col
							key={ extension.product_slug }
							className="sensei-extensions__card-wrapper"
							cols={ 4 }
						>
							<Card { ...extension } />
						</Col>
					);
				} ) }
			</Grid>
		</Section>
	);
};

export default Extensions;
