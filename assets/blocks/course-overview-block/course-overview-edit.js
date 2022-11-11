/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import InvalidUsageError from '../../shared/components/invalid-usage';

/**
 * Course Overview block edit component.
 *
 * @param {Object} props
 * @param {Object} props.context          Block context.
 * @param {Object} props.context.postType Post type.
 */
const CourseOverviewEdit = ( { context: { postType } } ) => {
	const blockProps = useBlockProps();

	if ( ! [ 'course', 'lesson' ].includes( postType ) ) {
		return (
			<InvalidUsageError
				message={ __(
					'The Course Overview block can only be used inside the Course List block.',
					'sensei-lms'
				) }
			/>
		);
	}

	return (
		<div { ...blockProps }>
			{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
			<a href="#">{ __( 'Course Overview', 'sensei-lms' ) }</a>
		</div>
	);
};

export default CourseOverviewEdit;
