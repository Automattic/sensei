import { InnerBlocks } from '@wordpress/block-editor';

export default function SaveRestrictedContent( { className } ) {
	return (
		<section className={ className }>
			<InnerBlocks.Content />
		</section>
	);
}
