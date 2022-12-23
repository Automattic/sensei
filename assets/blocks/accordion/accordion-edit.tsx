/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { useState } from '@wordpress/element';

const TEMPLATE = [ [ 'sensei-lms/accordion-section' ] ];
const ALLOWED_BLOCKS = [ 'sensei-lms/accordion-section', { isOpen: true } ];

const AccordionEdit = () => {
	const blockProps = useBlockProps();
	const [ selected, setSelection ] = useState< string | null >( null );

	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		allowedBlocks: ALLOWED_BLOCKS,
	} );

	return (
		<BlockContextProvider value={ { selected, setSelection } }>
			<div { ...innerBlockProps } />
		</BlockContextProvider>
	);
};

export default AccordionEdit;
