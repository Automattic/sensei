/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

// Block API version 3 no longer auto-merges the default block classname
// and `attributes.className` into the saved markup; `useBlockProps.save()`
// is required to restore it.
const ConditionalContentSave = () => {
	const blockProps = useBlockProps.save( { className: 'wp-block-group' } );

	return (
		<div { ...blockProps }>
			<div className="wp-block-group__inner-container">
				<InnerBlocks.Content />
			</div>
		</div>
	);
};

export default ConditionalContentSave;
