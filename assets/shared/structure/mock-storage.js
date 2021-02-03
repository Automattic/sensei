let nextId = 1;

/**
 * ID generator.
 *
 * @return {number} Next unique ID.
 */
function createId() {
	return nextId++;
}

/**
 * Load quiz from sessionStorage.
 *
 * @param {string} endpoint URL.
 * @return {Object} Quiz structure.
 */
export function mockLoadQuizStructure( endpoint ) {
	return JSON.parse( window.sessionStorage.getItem( endpoint ) || 'null' );
}

/**
 * Save quiz to sessionStorage. Add IDs.
 *
 * @param {string} endpoint  URL.
 * @param {Object} structure Quiz structure.
 * @return {Object} Updated structure.
 */
export function mockSaveQuizStructure( endpoint, structure ) {
	const structureWithIds = {
		...structure,
		questions: structure.questions?.map( ( q ) => ( {
			...q,
			id: q.id || createId(),
		} ) ),
	};
	window.sessionStorage.setItem(
		endpoint,
		JSON.stringify( structureWithIds )
	);
	// eslint-disable-next-line no-console
	console.table( structureWithIds.questions );

	return structureWithIds;
}
