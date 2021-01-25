/**
 * External dependencies
 */
import { find } from 'lodash';

/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';
import TokenList from '@wordpress/token-list';

/**
 * Checks if a block has a registered style which matches with the supplied class.
 *
 * @param {Array}  blockStyles The block's registered styles.
 * @param {string} className   The class to check.
 *
 * @return {boolean} True if there is a match.
 */
const blockHasStyle = ( blockStyles, className ) => {
	return (
		blockStyles &&
		blockStyles.some( ( style ) => 'is-style-' + style.name === className )
	);
};

/**
 * Returns the active style class from the given className.
 *
 * @param {Array}  styles    Block style variations.
 * @param {string} className Class name.
 *
 * @return {string} The active style class.
 */
export const getActiveStyleClass = ( styles, className ) => {
	if ( className ) {
		const classMatches = className.match( /is-style-\w+/ );

		if ( classMatches ) {
			return classMatches[ 0 ];
		}
	}

	const defaultStyle = find( styles, 'isDefault' );

	return defaultStyle ? 'is-style-' + defaultStyle.name : null;
};

/**
 * Applies a style class to a block.
 *
 * @param {string} clientId   The clientId of the block to apply the style to.
 * @param {string} styleClass The style class to apply.
 */
export const applyStyleClass = ( clientId, styleClass ) => {
	const {
		attributes: { className },
		name,
	} = select( 'core/block-editor' ).getBlock( clientId );

	const blockStyles = select( 'core/blocks' ).getBlockStyles( name );

	if ( blockHasStyle( blockStyles, styleClass ) ) {
		const newClassName = new TokenList( className );
		const activeStyleClass = getActiveStyleClass( blockStyles, className );

		if ( activeStyleClass ) {
			newClassName.remove( activeStyleClass );
		}

		newClassName.add( styleClass );

		dispatch( 'core/block-editor' ).updateBlockAttributes( clientId, {
			className: newClassName.value,
		} );
	}
};
