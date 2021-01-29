/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

const ConditionalContentSave = ( { className } ) => (
	<div className={ classnames( 'wp-block-group', className ) }>
		<div className="wp-block-group__inner-container">
			<InnerBlocks.Content />
		</div>
	</div>
);

export default ConditionalContentSave;
