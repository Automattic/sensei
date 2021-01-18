import { InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { RestrictedContentSettings } from './settings';

export const RestrictOptions = {
	ENROLLED: 'enrolled',
	UNENROLLED: 'unenrolled',
	COURSE_COMPLETED: 'course-completed',
};

export const RestrictOptionLabels = {
	[ RestrictOptions.ENROLLED ]: __( 'Enrolled Users', 'sensei-lms' ),
	[ RestrictOptions.UNENROLLED ]: __( 'Unenrolled Users', 'sensei-lms' ),
	[ RestrictOptions.COURSE_COMPLETED ]: __(
		'Course Completed',
		'sensei-lms'
	),
};

const EditRestrictedContent = ( {
	className,
	hasInnerBlocks,
	clientId,
	attributes: { restrictionType },
	setAttributes,
} ) => {
	return (
		<>
			<section className={ className }>
				<InnerBlocks
					renderAppender={
						! hasInnerBlocks && InnerBlocks.ButtonBlockAppender
					}
				/>
			</section>
			<RestrictedContentSettings
				selectedRestriction={ restrictionType }
				onRestrictionChange={ ( option ) =>
					setAttributes( {
						restrictionType: option,
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
] )( EditRestrictedContent );
