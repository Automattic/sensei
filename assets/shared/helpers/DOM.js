/**
 * Finds the first ancestor matching the CSS selector.
 *
 * @param {HTMLElement} element  The element whose parent you want to retrieve.
 * @param {string}      selector The CSS selector.
 * @return {HTMLElement|null} The parent element if found or null otherwise.
 */
export const querySelectorAncestor = ( element, selector = '' ) => {
	if ( ! element.parentElement ) {
		return null;
	}
	if ( element.parentElement.matches( selector ) ) {
		return element.parentElement;
	}
	return querySelectorAncestor( element.parentElement, selector );
};
