import { useState } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';

import { LessonActionsBlockSettings } from './settings';

const innerBlocksTemplate = [
	[
		'sensei-lms/button-complete-lesson',
		{ inContainer: true, align: 'full' },
	],
	[ 'sensei-lms/button-next-lesson', { inContainer: true } ],
	[ 'sensei-lms/button-reset-lesson', { inContainer: true } ],
];

/**
 * Edit lesson actions block component.
 *
 * @param {Object}   props
 * @param {string}   props.className              Custom class name.
 * @param {string}   props.clientId               Block ID.
 * @param {Function} props.setAttributes          Block set attributes function.
 * @param {Object}   props.attributes             Block attributes.
 * @param {boolean}  props.attributes.resetLesson Whether reset lesson is enabled.
 */
const EditLessonActionsBlock = ( {
	className,
	clientId,
	setAttributes,
	attributes: { resetLesson },
} ) => {
	const block = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( clientId ),
		[]
	);
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
	const [ resetLessonAttributes, setResetLessonAttributes ] = useState( {} );

	const setResetLesson = ( on ) => {
		const resetLessonBlock = block.innerBlocks.find(
			( i ) => i.name === 'sensei-lms/button-reset-lesson'
		);
		let newBlocks = null;

		if ( on && ! resetLessonBlock ) {
			// Add block.
			newBlocks = [
				...block.innerBlocks,
				createBlock(
					'sensei-lms/button-reset-lesson',
					resetLessonAttributes
				),
			];
		} else if ( ! on && resetLessonBlock ) {
			// Remove block.
			newBlocks = block.innerBlocks.filter(
				( i ) => i.name !== 'sensei-lms/button-reset-lesson'
			);

			// Save block attributes to restore, if needed.
			setResetLessonAttributes( resetLessonBlock.attributes );
		}

		if ( newBlocks ) {
			replaceInnerBlocks( clientId, newBlocks, false );
		}

		setAttributes( { resetLesson: on } );
	};

	return (
		<div className={ className }>
			<div className="sensei-buttons-container">
				<LessonActionsBlockSettings
					resetLesson={ resetLesson }
					setResetLesson={ setResetLesson }
				/>
				<InnerBlocks
					allowedBlocks={ [
						'sensei-lms/button-complete-lesson',
						'sensei-lms/button-next-lesson',
						'sensei-lms/button-reset-lesson',
					] }
					template={ innerBlocksTemplate }
					templateLock="all"
				/>
			</div>
		</div>
	);
};

export default EditLessonActionsBlock;
