/**
 * WordPress dependencies
 */
import { BlockIcon } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import settings from './index';
import AiIcon from '../../../shared/components/ai-icon';
import AiLessonsImage from './ai-lessons-image';
import CheckIcon from '../../../icons/checked.svg';
import SenseiProBadge from '../../../shared/components/sensei-pro-badge';
/**
 * External dependencies
 */
import { noop } from 'lodash';
import { DeprecatedPlaceholder } from './elements/deprecated-placeholder';

/**
 * Placeholder for empty Course Outline block.
 *
 * @param {Function} addBlock Add block
 */

const EditPlaceholder = ( { addBlocks, openTailoredModal = noop } ) => {
	const createBlankLessons = () => {
		addBlocks( [
			{
				type: 'lesson',
				title: __( 'Lesson 1', 'sensei-lms' ),
			},
			{
				type: 'lesson',
				title: __( 'Lesson 2', 'sensei-lms' ),
			},
			{
				type: 'lesson',
				title: __( 'Lesson 3', 'sensei-lms' ),
			},
		] );
	};

	const onGenerateWithAIClick = () => {
		openTailoredModal();
	};

	return (
		<Placeholder
			className="wp-block-sensei-lms-course-outline__placeholder"
			label={ __( 'Course Outline', 'sensei-lms' ) }
			icon={ <BlockIcon icon={ settings.icon } showColors /> }
			instructions={ __(
				'Build and display a course outline.',
				'sensei-lms'
			) }
		>
			<div className="wp-block-sensei-lms-course-outline__placeholder-items">
				<button
					className="wp-block-sensei-lms-course-outline__placeholder-item is-blank"
					onClick={ createBlankLessons }
					aria-labelledby="generate-blank"
				>
					<p className="wp-block-sensei-lms-course-outline__placeholder-item-intro">
						{ __(
							'Start with a blank canvas and create your own course outline.',
							'sensei-lms'
						) }
					</p>
					<ul className="wp-block-sensei-lms-course-outline__placeholder-item-details">
						<li>
							{ __( 'Add Lessons and Modules', 'sensei-lms' ) }
						</li>
						<li>
							{ __( 'Reorder and edit anytime', 'sensei-lms' ) }
						</li>
					</ul>
					<ul className="wp-block-sensei-lms-course-outline__placeholder-item-lessons">
						<li>{ __( 'Lesson 1', 'sensei-lms' ) }</li>
						<li>{ __( 'Lesson 2', 'sensei-lms' ) }</li>
					</ul>
					<figcaption
						className="wp-block-sensei-lms-course-outline__placeholder-item-caption"
						id="generate-blank"
					>
						{ __( 'Start with blank', 'sensei-lms' ) }
					</figcaption>
				</button>

				<button
					className="wp-block-sensei-lms-course-outline__placeholder-item is-ai"
					aria-labelledby="generate-with-ai"
					onClick={ onGenerateWithAIClick }
				>
					<div>
						<p className="wp-block-sensei-lms-course-outline__placeholder-item-intro">
							{ __(
								"Get AI's help to start with a tailored course outline.",
								'sensei-lms'
							) }
						</p>
						<AiIcon className="wp-block-sensei-lms-course-outline__placeholder-item-icon" />
					</div>
					<ul className="wp-block-sensei-lms-course-outline__placeholder-item-details">
						<li>
							<CheckIcon className="wp-block-sensei-lms-course-outline__placeholder-item-icon" />
							{ __(
								'AI tailored outline based on your content',
								'sensei-lms'
							) }
						</li>
						<li>
							<CheckIcon className="wp-block-sensei-lms-course-outline__placeholder-item-icon" />
							{ __(
								'Access to all Sensei Pro features',
								'sensei-lms'
							) }
						</li>
					</ul>
					<AiLessonsImage />
					<figcaption
						className="wp-block-sensei-lms-course-outline__placeholder-item-caption"
						id="generate-with-ai"
					>
						{ __( 'Generate with AI', 'sensei-lms' ) }
						<SenseiProBadge />
					</figcaption>
				</button>
			</div>
		</Placeholder>
	);
};

const OutlinePlaceholder = ( { addBlock, addBlocks, openTailoredModal } ) => {
	return window.sensei.featureFlags.course_outline_ai ? (
		<EditPlaceholder
			addBlocks={ addBlocks }
			openTailoredModal={ openTailoredModal }
		/>
	) : (
		<DeprecatedPlaceholder addBlock={ addBlock } />
	);
};

export default OutlinePlaceholder;
