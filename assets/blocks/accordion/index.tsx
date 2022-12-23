/**
 * Internal dependencies
 */
export { default as SummaryBlock } from './summary';
export { default as DetailsBlock } from './section';
export { default as ContentBlock } from './content';

import metadata from './block.json';
import edit from './accordion-edit';
import save from './accordion-save';

export default {
	...metadata,
	edit,
	save,
};
