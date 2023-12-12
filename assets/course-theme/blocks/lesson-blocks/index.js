/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ChevronLeft from '../../../icons/chevron-left.svg';
import ChevronRight from '../../../icons/chevron-right.svg';
import DoubleChevronRight from '../../../icons/double-chevron-right.svg';
import MenuIcon from '../../../icons/menu.svg';
import LogoTreeIcon from '../../../icons/logo-tree.svg';
import lessonPropertiesBlock from '../../../blocks/lesson-properties';
import courseContentMeta from './course-content.block.json';
import courseThemeCourseProgressBarMeta from './course-theme-course-progress-bar.block.json';
import courseThemeCourseProgressCounterMeta from './course-theme-course-progress-counter.block.json';
import courseThemeLessonActionsMeta from './course-theme-lesson-actions.block.json';
import courseThemeNoticesMeta from './course-theme-notices.block.json';
import courseThemePostTitleMeta from './course-theme-post-title.block.json';
import courseThemePrevNextLessonMeta from './course-theme-prev-next-lesson.block.json';
import learningModeLessonPropertiesMeta from './learning-mode-lesson-properties.block.json';
import focusModeToggleMeta from './focus-mode-toggle.block.json';
import pageActionsMeta from './page-actions.block.json';
import sideBarToggleButtonMeta from './sidebar-toggle-button.block.json';
import courseThemeLessonVideoMeta from './course-theme-lesson-video.block.json';
import ExitCourseButton from './exit-course-button';

import CourseTitle from './course-title';
import moduleTitle from './module-title';

const meta = {
	category: 'theme',
	attributes: {},
	icon: {
		src: <LogoTreeIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
};

export default [
	CourseTitle,
	ExitCourseButton,
	moduleTitle,
	{
		...courseThemeCourseProgressCounterMeta,
		...meta,
		title: __( 'Course Progress', 'sensei-lms' ),
		description: __(
			'Display number of completed and total lessons in the course.',
			'sensei-lms'
		),
		edit: function ProgressCounterEdit() {
			const blockProps = useBlockProps( {
				className: 'sensei-course-theme-course-progress',
			} );
			return (
				<div { ...blockProps }>
					{ __( '2 of 10 lessons complete (20%)', 'sensei-lms' ) }
				</div>
			);
		},
	},
	{
		...courseThemePrevNextLessonMeta,
		...meta,
		title: __( 'Previous & Next Lesson', 'sensei-lms' ),
		description: __(
			'Link to the previous and next lessons.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-course-theme-prev-next-lesson-container">
					<div className="sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__prev">
						<ChevronLeft />
					</div>
					<div className="sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__next">
						<ChevronRight />
					</div>
				</div>
			);
		},
	},
	{
		...sideBarToggleButtonMeta,
		...meta,
		title: __( 'Sidebar Menu Toggle', 'sensei-lms' ),
		description: __(
			'Turn the Sidebar block into an overlay menu on mobile screens.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-course-theme__sidebar-toggle">
					<MenuIcon />
				</div>
			);
		},
	},
	{
		// Deprecated since Sensei 4.19.2.
		...courseThemeLessonActionsMeta,
		...meta,
		title: __( 'Lesson Actions (Learning Mode)', 'sensei-lms' ),
		description: __(
			'(Deprecated) Display buttons for actions the learner can take for the current lesson.',
			'sensei-lms'
		),
		attributes: {
			options: {
				type: 'object',
				default: {},
			},
		},
		edit() {
			return (
				<div className="sensei-course-theme-lesson-actions">
					<div className="wp-block-button is-style-outline">
						<div className="wp-block-button__link wp-element-button sensei-course-theme__button is-secondary">
							{ __( 'Complete Lesson', 'sensei-lms' ) }
						</div>
					</div>
					<div className="wp-block-button">
						<div className="wp-block-button__link wp-element-button sensei-course-theme__button is-primary">
							{ __( 'Take Quiz', 'sensei-lms' ) }
						</div>
					</div>
				</div>
			);
		},
	},
	{
		...courseThemeCourseProgressBarMeta,
		...meta,
		title: __( 'Course Progress Bar', 'sensei-lms' ),
		description: __( 'Display course progress.', 'sensei-lms' ),
		edit: function ProgressBardEdit() {
			const blockProps = useBlockProps( {
				className: 'sensei-course-theme-course-progress-bar',
			} );
			return (
				<div { ...blockProps }>
					<div
						className="sensei-course-theme-course-progress-bar-inner"
						style={ { width: '20%' } }
					/>
				</div>
			);
		},
	},
	{
		...focusModeToggleMeta,
		...meta,
		title: __( 'Focus Mode Toggle', 'sensei-lms' ),
		description: __(
			'Toggle a minimalized view of Learning Mode.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-course-theme__focus-mode-toggle">
					<DoubleChevronRight className="sensei-course-theme__focus-mode-toggle-icon" />
				</div>
			);
		},
	},
	{
		...courseThemePostTitleMeta,
		...meta,
		title: __( 'Post Title', 'sensei-lms' ),
		description: __(
			'Display title of the current lesson or quiz.',
			'sensei-lms'
		),
		edit() {
			return <h1>{ __( 'Lesson Title', 'sensei-lms' ) }</h1>;
		},
	},
	{
		...courseContentMeta,
		...meta,
		title: __( 'Course Content', 'sensei-lms' ),
		description: __(
			'Display lesson or quiz content, if the learner has access to it.',
			'sensei-lms'
		),
		edit() {
			return <p>{ __( 'Course Content.', 'sensei-lms' ) }</p>;
		},
	},
	{
		...courseThemeNoticesMeta,
		...meta,
		title: __( 'Notices', 'sensei-lms' ),
		description: __(
			'Display Sensei notices about the current lesson or quiz.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-course-theme__frame sensei-lms-notice sensei-course-theme-lesson-quiz-notice">
					<div className="sensei-course-theme-lesson-quiz-notice__content">
						{ __( 'Notice', 'sensei-lms' ) }
					</div>
				</div>
			);
		},
	},
	{
		...pageActionsMeta,
		...meta,
		title: __( 'Page Actions', 'sensei-lms' ),
		description: __(
			'Display pagination and related actions for the current page.',
			'sensei-lms'
		),
		apiVersion: 2,
		edit: function EditPageActions() {
			const blockProps = useBlockProps( {
				className: 'sensei-course-theme__post-pagination',
			} );
			return (
				<div { ...blockProps }>
					<span className="post-page-numbers current">1</span>
					<a href="#pseudo-link" className="post-page-numbers">
						2
					</a>
				</div>
			);
		},
	},
	{
		...courseThemeLessonVideoMeta,
		...meta,
		title: __( 'Lesson Video', 'sensei-lms' ),
		description: __(
			'Displays the featured video if there is one for the lesson.',
			'sensei-lms'
		),
		apiVersion: 2,
		edit: function EditLessonVideo() {
			const blockProps = useBlockProps( {
				className: 'sensei-course-theme-lesson-video',
			} );
			return (
				<div
					{ ...blockProps }
					style={ {
						backgroundColor: '#000',
						color: '#fff',
						display: 'flex',
						justifyContent: 'center',
						alignItems: 'center',
						height: '500px',
					} }
				>
					<p
						className="has-text-align-center"
						style={ { fontSize: '100px' } }
					>
						▶
					</p>
				</div>
			);
		},
	},
	{
		...lessonPropertiesBlock,
		...learningModeLessonPropertiesMeta,
		...meta,
		description: __(
			'Displays the lesson properties such as length and difficulty.',
			'sensei-lms'
		),
		edit: function EditLearningModeLessonProperties() {
			const blockProps = useBlockProps( {
				className: 'wp-block-sensei-lms-lesson-properties',
			} );
			return (
				<div { ...blockProps }>
					<span className="wp-block-sensei-lms-lesson-properties__difficulty">
						{ __(
							'The Learning Mode Lesson Properties block will display the lesson complexity and length. To set these properties, you need to add a Lesson Properties block to each individual lesson.',
							'sensei-lms'
						) }
					</span>
				</div>
			);
		},
	},
];
