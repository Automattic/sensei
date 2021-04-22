/**
 * Internal dependencies
 */
import Card from './card';
import { Col } from './grid';

const FilteredExtensions = ( { extensions } ) => (
	<Col as="section" className="sensei-extensions__section" cols={ 12 }>
		<ul className="sensei-extensions__grid-list">
			{ extensions.map( ( extension ) => (
				<li
					key={ extension.product_slug }
					className="sensei-extensions__list-item"
				>
					<div className="sensei-extensions__card-wrapper">
						<Card extension={ extension } />
					</div>
				</li>
			) ) }
		</ul>
	</Col>
);

export default FilteredExtensions;
