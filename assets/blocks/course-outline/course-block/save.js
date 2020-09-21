import { InnerBlocks } from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';
import { extractStructure } from '../data';
import { COURSE_STORE } from '../store';

export default function saveCourseOutlineBlock( { innerBlocks } ) {
	dispatch( COURSE_STORE ).setEditorStructure(
		extractStructure( innerBlocks )
	);
	return <InnerBlocks.Content />;
}
