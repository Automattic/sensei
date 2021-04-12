/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from './card';

const AllExtensions = () => (
	<>
		<section className="sensei-extensions__section sensei-extensions__grid__col --col-12">
			<h2 className="sensei-extensions__section__title">
				{ __( 'Featured', 'sensei-lms' ) }
			</h2>
			<ul className="sensei-extensions__section__content sensei-extensions__featured-list">
				<li className="sensei-extensions__featured-list__item">
					<Card hasUpdate />
				</li>
				<li className="sensei-extensions__featured-list__item">
					<Card />
				</li>
				<li className="sensei-extensions__featured-list__item">
					<Card />
				</li>
			</ul>
		</section>

		<section className="sensei-extensions__section sensei-extensions__grid__col --col-8">
			<h2 className="sensei-extensions__section__title">
				{ __( 'Course creation', 'sensei-lms' ) }
			</h2>
			<ul className="sensei-extensions__section__content sensei-extensions__large-list">
				<li className="sensei-extensions__large-list__item">
					<Card />
				</li>
				<li className="sensei-extensions__large-list__item">
					<Card />
				</li>
				<li className="sensei-extensions__large-list__item">
					<Card />
				</li>
				<li className="sensei-extensions__large-list__item">
					<Card />
				</li>
			</ul>
		</section>

		<section className="sensei-extensions__section sensei-extensions__grid__col --col-4">
			<h2 className="sensei-extensions__section__title">
				{ __( 'Learner engagement', 'sensei-lms' ) }
			</h2>
			<ul className="sensei-extensions__section__content sensei-extensions__small-list">
				<li className="sensei-extensions__small-list__item">
					<Card />
				</li>
				<li className="sensei-extensions__small-list__item">
					<Card />
				</li>
				<li className="sensei-extensions__small-list__item">
					<Card />
				</li>
			</ul>
		</section>
	</>
);

export default AllExtensions;
