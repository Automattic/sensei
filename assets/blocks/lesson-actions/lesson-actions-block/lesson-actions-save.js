/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

// Block API version 3 no longer auto-merges the default block classname
// and `attributes.className` into the saved markup; `useBlockProps.save()`
// is required to restore it.
const LessonActionsSave = () => {
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<div className="sensei-buttons-container">
				<InnerBlocks.Content />
			</div>
		</div>
	);
};

export default LessonActionsSave;
