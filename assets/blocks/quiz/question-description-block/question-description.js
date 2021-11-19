/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { withBlockMeta } from '../../../shared/blocks/block-metadata';

/**
 * Question Description control.
 */
const QuestionDescription = () => {
	return (
		<div
			className={ classnames( 'sensei-lms-question-description-block' ) }
		>
			<InnerBlocks
				template={ [
					[
						'core/paragraph',
						{
							placeholder: __(
								'Question Description',
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
