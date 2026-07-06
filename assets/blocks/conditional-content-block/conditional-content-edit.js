/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConditionalContentSettings from './conditional-content-settings';

export const Conditions = {
	ENROLLED: 'enrolled',
	UNENROLLED: 'unenrolled',
	COURSE_COMPLETED: 'course-completed',
};

export const ConditionLabels = {
	[ Conditions.ENROLLED ]: __( 'Enrolled', 'sensei-lms' ),
	[ Conditions.UNENROLLED ]: __( 'Not Enrolled', 'sensei-lms' ),
	[ Conditions.COURSE_COMPLETED ]: __( 'Course Completed', 'sensei-lms' ),
};

const ConditionalContentEdit = ( {
	hasInnerBlocks,
	clientId,
	attributes: { condition },
	setAttributes,
} ) => {
	// Block API version 3 requires `useBlockProps()` on the outermost
	// element for the editor to recognize/select this block and no longer
	// auto-merges `className`/`attributes.className` into `props.className`.
	const blockProps = useBlockProps();

	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks
					renderAppender={
						! hasInnerBlocks && InnerBlocks.ButtonBlockAppender
					}
				/>
			</div>
			<ConditionalContentSettings
				selectedCondition={ condition }
				onConditionChange={ ( option ) =>
					setAttributes( {
						condition: option,
					} )
				}
				clientId={ clientId }
				hasInnerBlocks={ hasInnerBlocks }
			/>
		</>
	);
};

export default compose( [
	withSelect( ( select, { clientId } ) => {
		const { getBlock } = select( 'core/block-editor' );

		const block = getBlock( clientId );

		return {
			hasInnerBlocks: !! ( block && block.innerBlocks.length ),
		};
	} ),
] )( ConditionalContentEdit );
