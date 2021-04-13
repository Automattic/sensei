/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from './card';

const AllExtensions = ( { extensions } ) => {
	const featuredExtensions = extensions.filter(
		( extension ) => extension.is_featured
	);

	return (
		<>
			{ featuredExtensions.length > 0 && (
				<section className="sensei-extensions__section sensei-extensions__grid__col --col-12">
					<h2 className="sensei-extensions__section__title">
						{ __( 'Featured', 'sensei-lms' ) }
					</h2>
					<ul className="sensei-extensions__section__content sensei-extensions__featured-list">
						{ featuredExtensions.map( ( extension ) => (
							<li
								key={ extension.product_slug }
								className="sensei-extensions__featured-list__item"
							>
								<Card extension={ extension } />
							</li>
						) ) }
					</ul>
				</section>
			) }

			<section className="sensei-extensions__section sensei-extensions__grid__col --col-8">
				<h2 className="sensei-extensions__section__title">
					{ __( 'Course creation', 'sensei-lms' ) }
				</h2>
				<ul className="sensei-extensions__section__content sensei-extensions__large-list">
					{ extensions.map( ( extension ) => (
						<li
							key={ extension.product_slug }
							className="sensei-extensions__large-list__item"
						>
							<Card extension={ extension } />
						</li>
					) ) }
				</ul>
			</section>

			<section className="sensei-extensions__section sensei-extensions__grid__col --col-4">
				<h2 className="sensei-extensions__section__title">
					{ __( 'Learner engagement', 'sensei-lms' ) }
				</h2>
				<ul className="sensei-extensions__section__content sensei-extensions__small-list">
					{ extensions.map( ( extension ) => (
						<li
							key={ extension.product_slug }
							className="sensei-extensions__small-list__item"
						>
							<Card extension={ extension } />
						</li>
					) ) }
				</ul>
			</section>
		</>
	);
};

export default AllExtensions;
