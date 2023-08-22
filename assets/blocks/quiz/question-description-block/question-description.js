/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { withBlockMeta } from '../../../shared/blocks/block-metadata';

/**
 * Question Description control.
 */
const QuestionDescription = () => {
	const blockProps = useBlockProps( {
		className: 'sensei-lms-question-description-block',
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks
				template={ [
					[
						'core/paragraph',
						{
							placeholder: __(
								'Add question description or type / to choose a block.',
								'sensei-lms'
							),
						},
					],
				] }
				templateInsertUpdatesSelection={ false }
				templateLock={ false }
			/>
		</div>
	);
};

export default compose( withBlockMeta )( QuestionDescription );
