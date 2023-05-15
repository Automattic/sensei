/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const ExitCourseEdit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<a
				href="#exit-course-button-pseudo-link"
				onClick={ ( event ) => event.preventDefault() }
			>
				{ __( 'Exit Course', 'sensei-lms' ) }
			</a>
		</div>
	);
};
