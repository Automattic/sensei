/**
 * WordPress dependencies
 */
import { BlockIcon } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
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
 * Placeholder for empty Course Outline block.
 *
 * @param {Function} addBlock Add block
 */
const OutlinePlaceholder = ( { addBlock } ) => {
	const instructions = window.sensei.aiCourseOutline
		? __( 'Build and display a course outline.', 'sensei-lms' )
		: __(
				'Build and display a course outline. A course is made up of modules (optional) and lessons. You can use modules to group related lessons together.',
				'sensei-lms'
		  );

	const content = window.sensei.aiCourseOutline ? (
		<div className="wp-block-sensei-lms-course-outline__placeholder-items">
			<figure className="wp-block-sensei-lms-course-outline__placeholder-item is-blank">
				<p className="wp-block-sensei-lms-course-outline__placeholder-item-intro">
					{ __(
						'Start with a blank canvas and create your own course outline.',
						'sensei-lms'
					) }
				</p>
				<ul className="wp-block-sensei-lms-course-outline__placeholder-item-details">
					<li>{ __( 'Add Lessons and Modules', 'sensei-lms' ) }</li>
					<li>{ __( 'Reorder and edit anytime', 'sensei-lms' ) }</li>
				</ul>
				<ul className="wp-block-sensei-lms-course-outline__placeholder-item-lessons">
					<li>{ __( 'Lesson 1', 'sensei-lms' ) }</li>
					<li>{ __( 'Lesson 2', 'sensei-lms' ) }</li>
				</ul>
				<figcaption className="wp-block-sensei-lms-course-outline__placeholder-item-caption">
					{ __( 'Start with blank', 'sensei-lms' ) }
				</figcaption>
			</figure>

			<figure className="wp-block-sensei-lms-course-outline__placeholder-item is-ai">
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
				<figcaption className="wp-block-sensei-lms-course-outline__placeholder-item-caption">
					{ __( 'Generate with AI', 'sensei-lms' ) }
					<SenseiProBadge />
				</figcaption>
			</figure>
		</div>
	) : (
		<>
			<Button
				isDefault
				onClick={ () => addBlock( 'module' ) }
				className="is-large"
			>
				{ __( 'Create a module', 'sensei-lms' ) }
			</Button>
			<Button
				isDefault
				onClick={ () => addBlock( 'lesson' ) }
				className="is-large"
			>
				{ __( 'Create a lesson', 'sensei-lms' ) }
			</Button>
		</>
	);

	return (
		<Placeholder
			className="wp-block-sensei-lms-course-outline__placeholder"
			label={ __( 'Course Outline', 'sensei-lms' ) }
			icon={ <BlockIcon icon={ settings.icon } showColors /> }
			instructions={ instructions }
		>
			{ content }
		</Placeholder>
	);
};

export default OutlinePlaceholder;
