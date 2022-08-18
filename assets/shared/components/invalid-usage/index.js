/**
 * WordPress dependencies
 */
import { useBlockProps, Warning } from '@wordpress/block-editor';

function InvalidUsageError( { message } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Warning>{ message }</Warning>
		</div>
	);
}

export default InvalidUsageError;
