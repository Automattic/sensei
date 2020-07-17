/**
 * Return keys of a key-value map where their value is true.
 *
 * @param {Object} map Data.
 * @return {string[]} Selected keys.
 */
export function getSelectedKeys( map ) {
	return Object.entries( map ).reduce( ( m, [ key, value ] ) => {
		if ( value ) m.push( key );
		return m;
	}, [] );
}
