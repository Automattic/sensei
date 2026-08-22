/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

const ConditionalContentSave = () => (
	<div { ...useBlockProps.save( { className: 'wp-block-group' } ) }>
		<div className="wp-block-group__inner-container">
			<InnerBlocks.Content />
		</div>
	</div>
);

export default ConditionalContentSave;
