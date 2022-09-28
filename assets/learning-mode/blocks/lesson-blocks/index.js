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
import SenseiIcon from '../../../icons/sensei.svg';
import courseContentMeta from './course-content.block.json';
import learningModeCourseProgressBarMeta from './learning-mode-course-progress-bar.block.json';
import learningModeCourseProgressCounterMeta from './learning-mode-course-progress-counter.block.json';
import learningModeLessonActionsMeta from './learning-mode-lesson-actions.block.json';
import learningModeLessonModuleMeta from './learning-mode-lesson-module.block.json';
import learningModeNoticesMeta from './learning-mode-notices.block.json';
import learningModePostTitleMeta from './learning-mode-post-title.block.json';
import learningModePrevNextLessonMeta from './learning-mode-prev-next-lesson.block.json';
import courseTitleMeta from './course-title.block.json';
import exitCourseMeta from './exit-course.block.json';
import focusModeToggleMeta from './focus-mode-toggle.block.json';
import pageActionsMeta from './page-actions.block.json';
import sideBarToggleButtonMeta from './sidebar-toggle-button.block.json';
import learningModeLessonVideoMeta from './learning-mode-lesson-video.block.json';

const meta = {
	category: 'theme',
	attributes: {},
	icon: {
		src: <SenseiIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
};

export default [
	{
		...courseTitleMeta,
		...meta,
		title: __( 'Course Title', 'sensei-lms' ),
		description: __(
			'Display title of the course the current lesson or quiz belongs to.',
			'sensei-lms'
		),
		edit() {
			return <>{ __( 'Course Title', 'sensei-lms' ) }</>;
		},
	},
	{
		...learningModeCourseProgressCounterMeta,
		...meta,
		title: __( 'Course Progress', 'sensei-lms' ),
		description: __(
			'Display number of completed and total lessons in the course.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-learning-mode-course-progress">
					{ __( '2 of 10 lessons complete (20%)', 'sensei-lms' ) }
				</div>
			);
		},
	},
	{
		...learningModePrevNextLessonMeta,
		...meta,
		title: __( 'Previous & Next Lesson', 'sensei-lms' ),
		description: __(
			'Link to the previous and next lessons.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-learning-mode-prev-next-lesson-container">
					<div className="sensei-learning-mode-prev-next-lesson-a sensei-learning-mode-prev-next-lesson-a__prev">
						<ChevronLeft />
					</div>
					<div className="sensei-learning-mode-prev-next-lesson-a sensei-learning-mode-prev-next-lesson-a__next">
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
				<div className="sensei-learning-mode__sidebar-toggle">
					<MenuIcon />
				</div>
			);
		},
	},
	{
		...learningModeLessonActionsMeta,
		...meta,
		title: __( 'Lesson Actions (Learning Mode)', 'sensei-lms' ),
		description: __(
			'Display buttons for actions the learner can take for the current lesson.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-learning-mode-lesson-actions">
					<div className="sensei-learning-mode__button is-primary">
						{ __( 'Take Quiz', 'sensei-lms' ) }
					</div>
					<div className="sensei-learning-mode__button is-secondary">
						{ __( 'Complete Course', 'sensei-lms' ) }
					</div>
				</div>
			);
		},
	},
	{
		...learningModeCourseProgressBarMeta,
		...meta,
		title: __( 'Course Progress Bar', 'sensei-lms' ),
		description: __( 'Display course progress.', 'sensei-lms' ),
		edit() {
			return (
				<div className="sensei-learning-mode-course-progress-bar">
					<div
						className="sensei-learning-mode-course-progress-bar-inner"
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
				<div className="sensei-learning-mode__focus-mode-toggle">
					<DoubleChevronRight className="sensei-learning-mode__focus-mode-toggle-icon" />
				</div>
			);
		},
	},
	{
		...exitCourseMeta,
		...meta,
		title: __( 'Exit Course', 'sensei-lms' ),
		description: __(
			'Exit Learning Mode and return to the course page.',
			'sensei-lms'
		),
		edit() {
			return (
				<span className="sensei-lms-href">
					{ __( 'Exit Course', 'sensei-lms' ) }
				</span>
			);
		},
	},
	{
		...learningModeLessonModuleMeta,
		...meta,
		title: __( 'Module Title', 'sensei-lms' ),
		description: __(
			'Display title of the module the current lesson belongs to.',
			'sensei-lms'
		),
		edit() {
			return <em>{ __( 'MODULE', 'sensei-lms' ) }</em>;
		},
	},
	{
		...learningModePostTitleMeta,
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
		...learningModeNoticesMeta,
		...meta,
		title: __( 'Notices', 'sensei-lms' ),
		description: __(
			'Display Sensei notices about the current lesson or quiz.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-learning-mode__frame sensei-lms-notice sensei-learning-mode-lesson-quiz-notice">
					<div className="sensei-learning-mode-lesson-quiz-notice__content">
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
				className: 'sensei-quiz-pagination',
			} );
			return (
				<div { ...blockProps }>
					<div className="sensei-quiz-pagination__list">
						<ul className="page-numbers">
							<li>
								<span className="page-numbers current">1</span>
							</li>
							<li>
								<span className="page-numbers">2</span>
							</li>
							<li>
								<span className="page-numbers dots">…</span>
							</li>
							<li>
								<span className="page-numbers">10</span>
							</li>
						</ul>
					</div>
				</div>
			);
		},
	},
	{
		...learningModeLessonVideoMeta,
		...meta,
		title: __( 'Lesson Video', 'sensei-lms' ),
		description: __(
			'Displays the featured video if there is one for the lesson.',
			'sensei-lms'
		),
		apiVersion: 2,
		edit: function EditLessonVideo() {
			const blockProps = useBlockProps( {
				className: 'sensei-learning-mode-lesson-video',
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
];
