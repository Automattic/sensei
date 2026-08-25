/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

const LessonActionsSave = () => (
	<div { ...useBlockProps.save() }>
		<div className="sensei-buttons-container">
			<InnerBlocks.Content />
		</div>
	</div>
);

export default LessonActionsSave;
