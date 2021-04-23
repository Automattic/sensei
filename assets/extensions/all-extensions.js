/**
 * External dependencies
 */
import classnames from 'classnames';
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
		items: [
			{
				slug: 'sensei-wc-paid-courses',
				itemProps: {
					className: 'special-class',
					style: { background: 'red' },
				},
				wrapperProps: {
					className: 'special',
					style: { color: 'white' },
				},
				cardProps: {
					style: { background: 'blue' },
				},
			},
			{ slug: 'sensei-content-drip' },
			{ slug: 'sensei-certificates' },
		],
	},
	{
		id: 'course-creation',
		columns: 8,
		type: 'large-list',
		title: 'Course creation',
		items: [
			{ slug: 'sensei-course-participants' },
			{ slug: 'sensei-course-progress' },
			{ slug: 'sensei-media-attachments' },
		],
	},
	{
		id: 'learner-engagement',
		columns: 4,
		type: 'small-list',
		title: 'Learner engagement',
		items: [
			{ slug: 'sensei-share-your-grade' },
			{ slug: 'sensei-post-to-course' },
		],
	},
	{
		id: 'grid-example',
		columns: 12,
		type: 'grid-list',
		items: [
			{ slug: 'sensei-share-your-grade' },
			{ slug: 'sensei-post-to-course' },
			{ slug: 'sensei-media-attachments' },
			{ slug: 'sensei-course-participants' },
			{ slug: 'sensei-course-progress' },
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
				items: [
					{ slug: 'sensei-share-your-grade' },
					{ slug: 'sensei-post-to-course' },
				],
			},
			{
				id: 'sub-section-2',
				columns: 12,
				type: 'small-list',
				title: 'Sub section 2',
				items: [ { slug: 'sensei-post-to-course' } ],
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
				items: [ { slug: 'sensei-post-to-course' } ],
			},
			{
				id: 'sub-section-4',
				columns: 12,
				type: 'small-list',
				title: 'Sub section 4',
				items: [
					{ slug: 'sensei-share-your-grade' },
					{ slug: 'sensei-post-to-course' },
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
					className={ classnames(
						'sensei-extensions__section__content',
						`sensei-extensions__${ section.type }`
					) }
				>
					{ section.items.map(
						( {
							slug,
							itemProps = {},
							wrapperProps = {},
							cardProps = {},
						} ) => (
							<li
								{ ...itemProps }
								key={ slug }
								className={ classnames(
									'sensei-extensions__list-item',
									itemProps?.className
								) }
							>
								<div
									{ ...wrapperProps }
									className={ classnames(
										'sensei-extensions__card-wrapper',
										wrapperProps?.className
									) }
								>
									<Card
										extension={ extensionsBySlug[ slug ] }
										extraProps={ cardProps }
									/>
								</div>
							</li>
						)
					) }
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
