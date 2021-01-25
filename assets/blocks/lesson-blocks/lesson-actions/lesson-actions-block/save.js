/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function saveLessonActionsBlock( { className } ) {
	return (
		<div className={ className }>
			<div className="sensei-buttons-container">
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
