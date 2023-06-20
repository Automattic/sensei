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

const DeprecatedOutlinePlaceholder = ( { addBlock } ) => {
	return (
		<Placeholder
			className="wp-block-sensei-lms-course-outline__placeholder"
			label={ __( 'Course Outline', 'sensei-lms' ) }
			icon={ <BlockIcon icon={ settings.icon } showColors /> }
			instructions={ __(
				'Build and display a course outline. A course is made up of modules (optional) and lessons. You can use modules to group related lessons together.',
				'sensei-lms'
			) }
		>
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
		</Placeholder>
	);
};

/**
 * Placeholder for empty Course Outline block.
 *
 */
const OutlinePlaceholderWithAi = () => (
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
			<div className="wp-block-sensei-lms-course-outline__placeholder-item is-blank">
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
				<Button className="wp-block-sensei-lms-course-outline__generation-option">
					{ __( 'Start with blank', 'sensei-lms' ) }
				</Button>
			</div>

			<div className="wp-block-sensei-lms-course-outline__placeholder-item is-ai">
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
				<Button className="wp-block-sensei-lms-course-outline__generation-option-button">
					{ __( 'Generate with AI', 'sensei-lms' ) }
					<SenseiProBadge />
				</Button>
			</div>
		</div>
	</Placeholder>
);

const OutlinePlaceholder = ( props ) =>
	window?.sensei?.aiCourseOutline ? (
		<OutlinePlaceholderWithAi { ...props } />
	) : (
		<DeprecatedOutlinePlaceholder { ...props } />
	);

export default OutlinePlaceholder;
