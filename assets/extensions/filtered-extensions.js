/**
 * Internal dependencies
 */
import Card from './card';

const FilteredExtensions = ( { extensions } ) => (
	<section className="sensei-extensions__section sensei-extensions__grid__col --col-12">
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
	</section>
);

export default FilteredExtensions;
