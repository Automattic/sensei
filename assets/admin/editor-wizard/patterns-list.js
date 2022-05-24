/**
 * WordPress dependencies
 */
import {
	store as blockEditorStore,
	BlockPreview,
} from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ENTER, SPACE } from '@wordpress/keycodes';

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
 * Update blocks content, filling the placeholders.
 *
 * @param {Object[]} blocks      Blocks to fill with the new content.
 * @param {string}   title       Title to fill the placeholders
 * @param {string}   description Description to fill the placeholders.
 * @return {Object[]} Blocks with the placeholders filled.
 */
const fillPlaceholders = ( blocks, title, description ) => {
	if ( ! title && ! description ) {
		return blocks;
	}

	return blocks.map( ( block ) => {
		const { className = '' } = block.attributes;

		if ( title && className.includes( 'sensei-pattern-description' ) ) {
			block.attributes.content = description;
		}

		if ( title && className.includes( 'sensei-pattern-title' ) ) {
			block.attributes.content = title;
		}

		if ( block.innerBlocks ) {
			block.innerBlocks = fillPlaceholders( block.innerBlocks );
		}

		return block;
	} );
};

/**
 * Patterns list component.
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.title       Title to fill the chosen pattern.
 * @param {string}   props.description Description to fill the chosen pattern.
 * @param {Function} props.onChoose    Callback on choosing a pattern.
 */
const PatternsList = ( {
	title: newTitle,
	description: newDescription,
	onChoose,
} ) => {
	const { patterns } = useSelect( ( select ) => ( {
		patterns: select( blockEditorStore ).__experimentalGetAllowedPatterns(),
	} ) );
	const { resetEditorBlocks } = useDispatch( editorStore );

	return (
		<div
			className="sensei-patterns-list"
			role="listbox"
			aria-label={ __( 'Sensei block patterns', 'sensei-lms' ) }
		>
			{ patterns
				.filter( ( { categories } ) =>
					categories.includes( 'sensei-lms' )
				)
				.map(
					( { name, title, description, blocks, viewportWidth } ) => (
						// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
						<div
							key={ name }
							className="sensei-patterns-list__item"
							title={ description }
							role="option"
							tabIndex={ 0 }
							{ ...accessibleClick( () => {
								const blocksWithFilledContent = fillPlaceholders(
									blocks,
									newTitle,
									newDescription
								);
								resetEditorBlocks( blocksWithFilledContent );
								onChoose();
							} ) }
						>
							<div className="sensei-patterns-list__item-preview">
								<BlockPreview
									blocks={ blocks }
									viewportWidth={ viewportWidth }
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
