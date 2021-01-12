import { InnerBlocks } from '@wordpress/block-editor';

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
 * @param {Function} props.setAttributes          Block set attributes function.
 * @param {Object}   props.attributes             Block attributes.
 * @param {boolean}  props.attributes.resetLesson Whether reset lesson is enabled.
 */
const EditLessonActionsBlock = ( {
	className,
	setAttributes,
	attributes: { resetLesson },
} ) => (
	<div className={ className }>
		<div className="sensei-buttons-container">
			<LessonActionsBlockSettings
				resetLesson={ resetLesson }
				setResetLesson={ ( newValue ) =>
					setAttributes( { resetLesson: newValue } )
				}
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

export default EditLessonActionsBlock;
