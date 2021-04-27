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
import extensionsLayout from './_TEMP.json';

/**
 * Renders the sections based on the skeleton structure. It can also render subsections recursively.
 *
 * @param {Array}  layout           Layout skeleton.
 * @param {Object} extensionsBySlug Extensions by slug to be rendered.
 */
const renderSections = ( layout, extensionsBySlug ) =>
	layout.map( ( section ) => (
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

			{ section.description && (
				<p className="sensei-extensions__section__description">
					{ section.description }
				</p>
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
										{ ...( extensionSlug
											? extensionsBySlug[ extensionSlug ]
											: {} ) }
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

	return renderSections( extensionsLayout, extensionsBySlug );
};

export default AllExtensions;
