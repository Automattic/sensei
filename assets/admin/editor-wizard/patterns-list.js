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
 * Patterns list component.
 */
const PatternsList = () => {
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
								resetEditorBlocks( blocks );
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
