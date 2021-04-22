/**
 * External dependencies
 */
import { keyBy } from 'lodash';

/**
 * Internal dependencies
 */
import Card from './card';

// TODO: Get from API.
const extensionsSkeleton = [
	{
		id: 'featured',
		columns: 12,
		type: 'featured-list',
		title: 'Featured',
		itemSlugs: [
			'sensei-wc-paid-courses',
			'sensei-content-drip',
			'sensei-certificates',
		],
	},
	{
		id: 'course-creation',
		columns: 8,
		type: 'large-list',
		title: 'Course creation',
		itemSlugs: [
			'sensei-course-participants',
			'sensei-course-progress',
			'sensei-media-attachments',
		],
	},
	{
		id: 'learner-engagement',
		columns: 4,
		type: 'small-list',
		title: 'Learner engagement',
		itemSlugs: [ 'sensei-share-your-grade', 'sensei-post-to-course' ],
	},
	{
		id: 'grid-example',
		columns: 12,
		type: 'grid-list',
		title: 'Grid example',
		itemSlugs: [
			'sensei-share-your-grade',
			'sensei-post-to-course',
			'sensei-media-attachments',
			'sensei-course-participants',
			'sensei-course-progress',
		],
	},
];

const AllExtensions = ( { extensions } ) => {
	const extensionsBySlug = keyBy( extensions, 'product_slug' );

	return extensionsSkeleton.map( ( section ) => (
		<section
			key={ section.id }
			className={ `sensei-extensions__section sensei-extensions__grid__col --col-${ section.columns }` }
		>
			<h2 className="sensei-extensions__section__title">
				{ section.title }
			</h2>

			<ul
				className={ `sensei-extensions__section__content sensei-extensions__${ section.type }` }
			>
				{ section.itemSlugs.map( ( slug ) => (
					<li
						key={ extensionsBySlug[ slug ].product_slug }
						className="sensei-extensions__list-item"
					>
						<div className="sensei-extensions__card-wrapper">
							<Card extension={ extensionsBySlug[ slug ] } />
						</div>
					</li>
				) ) }
			</ul>
		</section>
	) );
};

export default AllExtensions;
