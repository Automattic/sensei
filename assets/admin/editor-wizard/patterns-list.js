/**
 * WordPress dependencies
 */
import {
	store as blockEditorStore,
	BlockPreview,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { getBlockType, getBlockFromExample } from '@wordpress/blocks';

/**
 * It returns events to fire the click event on click, pressing enter, and pressing space.
 *
 * @param {Function} fn Click callback.
 *
 * @return {Object} Object with props to add in the React element.
 */
const accessibleClick = ( fn ) => ( {
	onClick: fn,
	onKeyUp: ( e ) => {
		if ( [ ENTER, SPACE ].includes( e.keyCode ) ) {
			fn( e );
		}
	},
} );

/**
 * Use the block's example option for the block's content in the pattern preview.
 *
 * @param {Object} block Block instance.
 * @return {Object} Block instance.
 */
const withBlockExample = ( block ) => {
	const example = getBlockType( block.name )?.example;

	const innerBlocks =
		example && block.name !== 'core/group'
			? example.innerBlocks
			: block.innerBlocks;
	return example
		? getBlockFromExample( block.name, {
				attributes: {
					...example.attributes,
					...block.attributes,
				},
				innerBlocks,
		  } )
		: block;
};

/**
 * Filter out lesson actions block.
 *
 * @param {Object} block Block instance.
 */
const withoutLessonActions = ( block ) =>
	'sensei-lms/lesson-actions' !== block.name;

/**
 * Patterns list component.
 *
 * @param {Object}   props          Component props.
 * @param {Function} props.onChoose Callback on choosing a pattern.
 */
const PatternsList = ( { onChoose } ) => {
	const { patterns } = useSelect( ( select ) => ( {
		patterns: select(
			blockEditorStore
		).__experimentalGetPatternsByBlockTypes( 'sensei-lms/post-content' ),
	} ) );

	return (
		<div
			className="sensei-patterns-list"
			role="listbox"
			aria-label={ __( 'Sensei block patterns', 'sensei-lms' ) }
		>
			{ patterns
				.filter(
					( { categories } ) =>
						categories && categories.includes( 'sensei-lms' )
				)
				.map(
					( {
						name,
						title,
						description,
						blocks,
						viewportWidth,
						template,
					} ) => (
						// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
						<div
							key={ name }
							className="sensei-patterns-list__item"
							title={ description }
							role="option"
							tabIndex={ 0 }
							{ ...accessibleClick( () => {
								onChoose( blocks, name, template );
							} ) }
						>
							<div className="sensei-patterns-list__item-preview">
								<BlockPreview
									__experimentalPadding={ 10 }
									blocks={ blocks
										.filter( withoutLessonActions )
										.map( withBlockExample ) }
									viewportWidth={ viewportWidth ?? 800 }
								/>
							</div>
							<div className="sensei-patterns-list__item-title">
								{ title }
							</div>
						</div>
					)
				) }
		</div>
	);
};

export default PatternsList;
