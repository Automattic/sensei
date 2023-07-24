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
import AiLessonsImage from './elements/ai-lessons-image';
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
			<div className="wp-block-sensei-lms-course-outline__placeholder__options">
				<button
					className="wp-block-sensei-lms-course-outline__placeholder__option wp-block-sensei-lms-course-outline__placeholder__option-blank"
					onClick={ createBlankLessons }
					aria-labelledby="generate-blank"
				>
					<div className="wp-block-sensei-lms-course-outline__placeholder__option__content">
						<div className="wp-block-sensei-lms-course-outline__placeholder__option__content__intro">
							<p>
								{ __(
									'Start with a blank canvas and create your own course outline.',
									'sensei-lms'
								) }
							</p>
						</div>
						<ul className="wp-block-sensei-lms-course-outline__placeholder__option__content__details">
							<li>
								{ __(
									'Add Lessons and Modules',
									'sensei-lms'
								) }
							</li>
							<li>
								{ __(
									'Reorder and edit anytime',
									'sensei-lms'
								) }
							</li>
						</ul>
						<ul className="wp-block-sensei-lms-course-outline__placeholder__option__content__lessons">
							<li>{ __( 'Lesson 1', 'sensei-lms' ) }</li>
							<li>{ __( 'Lesson 2', 'sensei-lms' ) }</li>
						</ul>
					</div>
					<figcaption
						className="wp-block-sensei-lms-course-outline__placeholder__option__caption"
						id="generate-blank"
					>
						{ __( 'Start with blank', 'sensei-lms' ) }
					</figcaption>
				</button>

				<button
					className="wp-block-sensei-lms-course-outline__placeholder__option is-ai"
					aria-labelledby="generate-with-ai"
					onClick={ onGenerateWithAIClick }
				>
					<div className="wp-block-sensei-lms-course-outline__placeholder__option__content">
						<div className="wp-block-sensei-lms-course-outline__placeholder__option__content__intro">
							<p>
								{ __(
									"Get AI's help to start with a tailored course outline.",
									'sensei-lms'
								) }
							</p>
							<AiIcon className="wp-block-sensei-lms-course-outline__placeholder__option__content__intro__icon" />
						</div>
						<ul className="wp-block-sensei-lms-course-outline__placeholder__option__content__details">
							<li>
								<CheckIcon width={ 24 } height={ 24 } />
								{ __(
									'AI tailored outline based on your content',
									'sensei-lms'
								) }
							</li>
							<li>
								<CheckIcon width={ 24 } height={ 24 } />
								{ __(
									'Access to all Sensei Pro features',
									'sensei-lms'
								) }
							</li>
						</ul>
						<AiLessonsImage />
					</div>
					<figcaption
						className="wp-block-sensei-lms-course-outline__placeholder__option__caption"
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
