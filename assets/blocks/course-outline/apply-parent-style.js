import { select, dispatch } from '@wordpress/data';
import TokenList from '@wordpress/token-list';
import { find } from 'lodash';

const blockHasStyle = function ( blockStyles, className ) {
	return (
		blockStyles &&
		blockStyles.some( ( style ) => 'is-style-' + style.name === className )
	);
};

/**
 * Returns the active style from the given className.
 *
 * @param {Array}  styles    Block style variations.
 * @param {string} className Class name
 *
 * @return {Object?} The active style.
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
