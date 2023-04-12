/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const ModuleTitleEdit = () => {
	const blockProps = useBlockProps();

	return <h3 { ...blockProps }>{ __( 'MODULE', 'sensei-lms' ) }</h3>;
};
