/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Card from './card';
import { Grid, Col } from './grid';
import { EXTENSIONS_STORE } from './store';

/**
 * Renders the sections based on the skeleton structure. It can also render subsections recursively.
 *
 * @param {Array}  layout     Layout skeleton.
 * @param {Object} extensions Extensions by slug to be rendered.
 */
const renderSections = ( layout, extensions ) =>
	layout.map( ( section ) => (
		<Col
			key={ section.key }
			as="section"
			className={ classnames( 'sensei-extensions__section', {
				'sensei-extensions__section--with-inner-sections':
					section.innerSections,
			} ) }
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
					{ renderSections( section.innerSections, extensions ) }
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
						} ) =>
							( ! extensionSlug ||
								extensions[ extensionSlug ] ) && (
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
												? extensions[ extensionSlug ]
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
 * @param {Object} props        Component props.
 * @param {Array}  props.layout Layout to render the extensions page.
 */
const AllExtensions = ( { layout } ) => {
	const { extensions } = useSelect( ( select ) => ( {
		extensions: select( EXTENSIONS_STORE ).getEntities( 'extensions' ),
	} ) );

	return renderSections( layout, extensions );
};

export default AllExtensions;
