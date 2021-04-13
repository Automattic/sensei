/**
 * Internal dependencies
 */
import Card from './card';

const FilteredExtensions = ( { extensions } ) => (
	<section className="sensei-extensions__section">
		<ul className="sensei-extensions__grid sensei-extensions__grid-list">
			{ extensions.map( ( extension ) => (
				<li
					key={ extension.product_slug }
					className="sensei-extensions__grid-list__item sensei-extensions__grid__col --col-4"
				>
					<div className="sensei-extensions__grid-list__item-wrapper">
						<Card extension={ extension } />
					</div>
				</li>
			) ) }
		</ul>
	</section>
);

export default FilteredExtensions;
