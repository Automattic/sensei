/**
 * Return keys of a key-value map where their value is true.
 *
 * @param {Object} map Data.
 * @return {string[]} Selected keys.
 */
export const getSelectedKeys = ( map ) =>
	Object.keys( map ).filter( ( key ) => map[ key ] );
