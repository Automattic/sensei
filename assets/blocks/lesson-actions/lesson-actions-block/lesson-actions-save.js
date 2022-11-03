/**
 * WordPress dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';

const LessonActionsSave = ({ className }) => {
	const blockProps = useBlockProps.save({ className });
	const innerBlocksProps = useInnerBlocksProps.save(blockProps);
	console.log({ innerBlocksProps });

	<div {...innerBlocksProps}>

	</div>
};

export default LessonActionsSave;
