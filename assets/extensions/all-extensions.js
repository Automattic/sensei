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
		key: 'featured',
		columns: 12,
		type: 'featured-list',
		title: 'Featured',
		items: [
			{
				key: 'sensei-wc-paid-courses',
				extensionSlug: 'sensei-wc-paid-courses',
				itemProps: {
					className: 'special-class',
					style: { background: 'red' },
				},
				wrapperProps: {
					className: 'special',
					style: { color: 'white' },
				},
				cardProps: {
					htmlProps: { style: { background: 'blue' } },
				},
			},
			{
				key: 'sensei-content-drip',
				extensionSlug: 'sensei-content-drip',
			},
			{
				key: 'sensei-advanced-quizzes',
				cardProps: {
					title: 'Advanced Quizzes',
					excerpt:
						'Take your lesson quizzes to the next level with additional question types, quiz timer, answer feedback, and more.',
					badgeLabel: 'Coming soon',
					customLinks: [
						{
							key: 'learn-more',
							className: 'button button-primary',
							target: '_blank',
							rel: 'noreferrer external',
							href: '#',
							children: 'Learn more',
						},
						{
							key: 'other-link',
							className:
								'sensei-extensions__extension-actions__details-link',
							target: '_blank',
							rel: 'noreferrer external',
							href: '#',
							children: 'Other link',
						},
					],
				},
			},
		],
	},
	{
		key: 'course-creation',
		columns: 8,
		type: 'large-list',
		title: 'Course Creation',
		items: [
			{
				key: 'sensei-course-participants',
				extensionSlug: 'sensei-course-participants',
			},
			{
				key: 'sensei-course-progress',
				extensionSlug: 'sensei-course-progress',
			},
			{
				key: 'sensei-media-attachments',
				extensionSlug: 'sensei-media-attachments',
			},
		],
	},
	{
		key: 'learner-engagement',
		columns: 4,
		type: 'small-list',
		title: 'Learner Engagement',
		items: [
			{
				key: 'sensei-certificates',
				extensionSlug: 'sensei-certificates',
			},
			{
				key: 'sensei-share-your-grade',
				extensionSlug: 'sensei-share-your-grade',
			},
			{
				key: 'sensei-post-to-course',
				extensionSlug: 'sensei-post-to-course',
			},
		],
	},
	{
		key: 'grid-example',
		columns: 12,
		type: 'grid-list',
		items: [
			{
				key: 'sensei-share-your-grade',
				extensionSlug: 'sensei-share-your-grade',
			},
			{
				key: 'sensei-post-to-course',
				extensionSlug: 'sensei-post-to-course',
			},
			{
				key: 'sensei-media-attachments',
				extensionSlug: 'sensei-media-attachments',
			},
			{
				key: 'sensei-course-participants',
				extensionSlug: 'sensei-course-participants',
			},
			{
				key: 'sensei-course-progress',
				extensionSlug: 'sensei-course-progress',
			},
		],
	},
	{
		key: 'inner-sections-example',
		columns: 6,
		title: 'Inner Sections Example',
		innerSections: [
			{
				key: 'sub-section',
				columns: 12,
				type: 'small-list',
				title: 'Sub Section',
				items: [
					{
						key: 'sensei-share-your-grade',
						extensionSlug: 'sensei-share-your-grade',
					},
					{
						key: 'sensei-post-to-course',
						extensionSlug: 'sensei-post-to-course',
					},
				],
			},
			{
				key: 'sub-section-2',
				columns: 12,
				type: 'small-list',
				title: 'Sub Section 2',
				items: [
					{
						key: 'sensei-post-to-course',
						extensionSlug: 'sensei-post-to-course',
					},
				],
			},
		],
	},
	{
		key: 'inner-sections-example2',
		columns: 6,
		innerSections: [
			{
				key: 'sub-section-3',
				columns: 12,
				type: 'small-list',
				items: [
					{
						key: 'sensei-post-to-course',
						extensionSlug: 'sensei-post-to-course',
					},
				],
			},
			{
				key: 'sub-section-4',
				columns: 12,
				type: 'small-list',
				title: 'Sub Section 4',
				items: [
					{
						key: 'sensei-share-your-grade',
						extensionSlug: 'sensei-share-your-grade',
					},
					{
						key: 'sensei-post-to-course',
						extensionSlug: 'sensei-post-to-course',
					},
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
			key={ section.key }
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
							key,
							extensionSlug,
							itemProps = {},
							wrapperProps = {},
							cardProps = {},
						} ) => (
							<li
								{ ...itemProps }
								key={ key }
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
										extension={
											extensionSlug &&
											extensionsBySlug[ extensionSlug ]
										}
										{ ...cardProps }
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
