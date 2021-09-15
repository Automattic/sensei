/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * External dependencies
 */
import cn from 'classnames';

/**
 * Internal dependencies
 */
import { withBlockMeta } from '../../../shared/blocks/block-metadata';



/**
 * Question Description control.
 */
const QuestionDescription = ( ) => {
	return (
		<div className={ cn( 'sensei-lms-question-description-block' ) }>
			<h4>{ __( 'Question Description', 'sensei-lms' ) }</h4>
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

export default compose(
	withBlockMeta,
)( QuestionDescription );