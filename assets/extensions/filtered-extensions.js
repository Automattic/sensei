/**
 * Internal dependencies
 */
import Card from './card';
import { Col } from './grid';

/**
 * Filtered extensions component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Filtered extensions.
 */
const FilteredExtensions = ( { extensions } ) => (
	<Col as="section" className="sensei-extensions__section" cols={ 12 }>
		<ul className="sensei-extensions__grid-list">
			{ extensions.map( ( extension ) => (
				<li
					key={ extension.product_slug }
					className="sensei-extensions__list-item"
				>
					<div className="sensei-extensions__card-wrapper">
						<Card { ...extension } />
					</div>
				</li>
			) ) }
		</ul>
	</Col>
);

export default FilteredExtensions;
