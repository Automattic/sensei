/**
 * External dependencies
 */
import { keyBy } from 'lodash';

/**
 * Internal dependencies
 */
import Card from './card';
import { Grid, Col } from './grid';

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
		itemSlugs: [
			'sensei-share-your-grade',
			'sensei-post-to-course',
			'sensei-media-attachments',
			'sensei-course-participants',
			'sensei-course-progress',
		],
	},
	{
		id: 'inner-sections-example',
		columns: 6,
		title: 'Inner sections example',
		innerSections: [
			{
				id: 'sub-section',
				columns: 12,
				type: 'small-list',
				title: 'Sub section',
				itemSlugs: [
					'sensei-share-your-grade',
					'sensei-post-to-course',
				],
			},
			{
				id: 'sub-section-2',
				columns: 12,
				type: 'small-list',
				title: 'Sub section 2',
				itemSlugs: [ 'sensei-post-to-course' ],
			},
		],
	},
	{
		id: 'inner-sections-example2',
		columns: 6,
		innerSections: [
			{
				id: 'sub-section-3',
				columns: 12,
				type: 'small-list',
				itemSlugs: [ 'sensei-post-to-course' ],
			},
			{
				id: 'sub-section-4',
				columns: 12,
				type: 'small-list',
				title: 'Sub section 4',
				itemSlugs: [
					'sensei-share-your-grade',
					'sensei-post-to-course',
				],
			},
		],
	},
];

/**
 * Renders the sections based on the skeleton structure. It can also render subsections recursively.
 *
 * @param {Array}  skeleton         Skeleton to be rendered.
 * @param {Object} extensionsBySlug Extensions by slug to be rendered.
 */
const renderSections = ( skeleton, extensionsBySlug ) =>
	skeleton.map( ( section ) => (
		<Col
			key={ section.id }
			as="section"
			className="sensei-extensions__section"
			cols={ section.columns }
		>
			{ section.title && (
				<h2 className="sensei-extensions__section__title">
					{ section.title }
				</h2>
			) }

			{ section.innerSections ? (
				<Grid>
					{ renderSections(
						section.innerSections,
						extensionsBySlug
					) }
				</Grid>
			) : (
				<ul
					className={ `sensei-extensions__section__content sensei-extensions__${ section.type }` }
				>
					{ section.itemSlugs.map( ( slug ) => (
						<li
							key={ slug }
							className="sensei-extensions__list-item"
						>
							<div className="sensei-extensions__card-wrapper">
								<Card extension={ extensionsBySlug[ slug ] } />
							</div>
						</li>
					) ) }
				</ul>
			) }
		</Col>
	) );

/**
 * All extensions component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions All extensions.
 */
const AllExtensions = ( { extensions } ) => {
	const extensionsBySlug = keyBy( extensions, 'product_slug' );

	return renderSections( extensionsSkeleton, extensionsBySlug );
};

export default AllExtensions;
