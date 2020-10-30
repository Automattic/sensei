import { select, dispatch } from '@wordpress/data';
import TokenList from '@wordpress/token-list';
import { find } from 'lodash';

/**
 * Checks if a block has a registered style which matches with the supplied class.
 *
 * @param {Array}  blockStyles The block's registered styles.
 * @param {string} className   The class to check.
 *
 * @return {boolean} True if there is a match.
 */
const blockHasStyle = function ( blockStyles, className ) {
	return (
		blockStyles &&
		blockStyles.some( ( style ) => 'is-style-' + style.name === className )
	);
};

/**
 * Returns the active style class from the given className.
 *
 * @param {Array}  styles    Block style variations.
 * @param {string} className Class name
 *
 * @return {string} The active style class.
 */
export function getActiveStyleClass( styles, className ) {
	let activeClass = null;

	new TokenList( className ).forEach( ( potentialClass ) => {
		if ( potentialClass.indexOf( 'is-style-' ) !== -1 ) {
			const potentialStyleName = potentialClass.substring( 9 );
			const activeStyle = find( styles, { name: potentialStyleName } );

			if ( activeStyle ) {
				activeClass = potentialClass;
			}
		}
	} );

	if ( ! activeClass ) {
		const defaultStyle = find( styles, 'isDefault' );

		activeClass = defaultStyle ? 'is-style-' + defaultStyle.name : null;
	}

	return activeClass;
}

/**
 * Applies the style class of a parent block when the style is updated.
 *
 * @param {string} parentBlockName The name of the parent block.
 * @param {string} childBlockName  The name of the child block.
 * @param {string} childBlockId    The clientId of the block to apply the style to.
 * @param {string} oldParentClass  The previous style class of the parent block.
 * @param {string} onUpdate        A callback to be called when the the style of the parent block is updated.
 */
export const applyParentStyle = function (
	parentBlockName,
	childBlockName,
	childBlockId,
	oldParentClass,
	onUpdate
) {
	const outlineBlockId = select(
		'core/block-editor'
	).getBlockParentsByBlockName( childBlockId, parentBlockName )[ 0 ];

	if ( ! outlineBlockId ) {
		return;
	}

	const { className: parentClassName } = select(
		'core/block-editor'
	).getBlockAttributes( outlineBlockId );

	const parentBlockStyles = select( 'core/blocks' ).getBlockStyles(
		parentBlockName
	);

	const newParentClass = getActiveStyleClass(
		parentBlockStyles,
		parentClassName
	);

	if ( newParentClass && oldParentClass !== newParentClass ) {
		onUpdate( newParentClass );

		// If oldParentClass is not initialized yet, we don't want to update the class of the child block.
		if ( ! oldParentClass ) {
			return;
		}

		const { className: childClassName } = select(
			'core/block-editor'
		).getBlockAttributes( childBlockId );

		const childBlockStyles = select( 'core/blocks' ).getBlockStyles(
			childBlockName
		);

		if ( blockHasStyle( childBlockStyles, newParentClass ) ) {
			const newClassName = new TokenList( childClassName );
			const childStyleClass = getActiveStyleClass(
				childBlockStyles,
				childClassName
			);

			if ( childStyleClass ) {
				newClassName.remove( childStyleClass );
			}

			newClassName.add( newParentClass );

			dispatch( 'core/block-editor' ).updateBlockAttributes(
				childBlockId,
				{
					className: newClassName.value,
				}
			);
		}
	}
};
