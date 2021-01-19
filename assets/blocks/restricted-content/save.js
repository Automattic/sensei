import { InnerBlocks } from '@wordpress/block-editor';
import classnames from 'classnames';

export default function SaveRestrictedContent( { className } ) {
	return (
		<div className={ classnames( 'wp-block-group', className ) }>
			<div className="wp-block-group__inner-container">
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
