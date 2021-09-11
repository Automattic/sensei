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
 * Question Answer Feedback control.
 *
 * @param {string}   questionType             Question type.
 * @param {Object}   props                    Block props.
 * @param {Object}   props.attributes         Block attributes.
 * @param {string}   props.attributes.options Block options attribute.
 * @param {Function} props.setAttributes      Update block attributes.
 */
const QuestionDescription = ( ) => {
	return (
		<div className={ cn( 'sensei-lms-question-description-block' ) }>
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