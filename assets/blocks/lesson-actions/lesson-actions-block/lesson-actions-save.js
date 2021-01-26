/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

const LessonActionsSave = ( { className } ) => (
	<div className={ className }>
		<div className="sensei-buttons-container">
			<InnerBlocks.Content />
		</div>
	</div>
);

export default LessonActionsSave;
