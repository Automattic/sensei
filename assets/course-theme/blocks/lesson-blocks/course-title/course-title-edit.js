/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const CourseTitleEdit = () => {
	const blockProps = useBlockProps();

	// Todo: Add the heading level selection, similar to this https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/site-title/edit/index.js#L98-L103.
	return <h2 { ...blockProps }>{ __( 'Course Title', 'sensei-lms' ) }</h2>;
};
