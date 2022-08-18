/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, Warning } from '@wordpress/block-editor';

function InvalidUsageError( { message } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Warning>
				{ message }
			</Warning>
		</div>
	);
}

export default InvalidUsageError;
