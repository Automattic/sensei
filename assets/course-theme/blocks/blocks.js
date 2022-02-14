/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import ChevronLeft from '../../icons/chevron-left.svg';
import ChevronRight from '../../icons/chevron-right.svg';
import DoubleChevronRight from '../../icons/double-chevron-right.svg';
import MenuIcon from '../../icons/menu.svg';
import SenseiIcon from '../../icons/sensei.svg';
import CourseNavigationBlock from './course-navigation';

const meta = {
	category: 'theme',
	supports: {},
	attributes: {},
	icon: <SenseiIcon width="20" height="20" />,
};

const blocks = [
	{ ...meta, ...CourseNavigationBlock },
	{
		...meta,
		title: __( 'Course Title', 'sensei-lms' ),
		name: 'sensei-lms/course-title',
		description: __(
			'Display title of the course the current lesson or quiz belongs to.',
			'sensei-lms'
		),
		edit() {
			return (
				<h2 className="wp-block-sensei-lms-course-title">
					{ __( 'Course Title', 'sensei-lms' ) }
				</h2>
			);
		},
	},
	{
		...meta,
		title: __( 'Course Progress', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-course-progress-counter',
		description: __(
			'Display number of completed and total lessons in the course.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-course-theme-course-progress">
					{ __( '2 of 10 lessons complete (20%)', 'sensei-lms' ) }
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Previous & Next Lesson', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-prev-next-lesson',
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
		...meta,
		title: __( 'Sidebar Toggle', 'sensei-lms' ),
		name: 'sensei-lms/sidebar-toggle-button',
		description: __( 'Toggle the Learning Mode sidebar.', 'sensei-lms' ),
		edit() {
			return (
				<div className="sensei-course-theme__sidebar-toggle">
					<MenuIcon />
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Lesson Actions', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-lesson-actions',
		description: __(
			'Display buttons for actions the learner can take for the current lesson.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-course-theme-lesson-actions">
					<div className="sensei-course-theme__button is-primary">
						{ __( 'Take Quiz', 'sensei-lms' ) }
					</div>
					<div className="sensei-course-theme__button is-secondary">
						{ __( 'Complete Course', 'sensei-lms' ) }
					</div>
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Course Progress Bar', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-course-progress-bar',
		description: __( 'Display course progress.', 'sensei-lms' ),
		edit() {
			return (
				<div className="sensei-course-theme-course-progress-bar">
					<div
						className="sensei-course-theme-course-progress-bar-inner"
						style={ { width: '20%' } }
					/>
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Focus Mode Toggle', 'sensei-lms' ),
		name: 'sensei-lms/focus-mode-toggle',
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
		...meta,
		title: __( 'Exit Course', 'sensei-lms' ),
		name: 'sensei-lms/exit-course',
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
		...meta,
		title: __( 'Module Title', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-lesson-module',
		description: __(
			'Display title of the module the current lesson belongs to.',
			'sensei-lms'
		),
		edit() {
			return <em>{ __( 'MODULE', 'sensei-lms' ) }</em>;
		},
	},
	{
		...meta,
		title: __( 'Post Title', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-post-title',
		description: __(
			'Display title of the current lesson or quiz.',
			'sensei-lms'
		),
		edit() {
			return <h1>{ __( 'Lesson Title', 'sensei-lms' ) }</h1>;
		},
	},
	{
		...meta,
		title: __( 'Course Content', 'sensei-lms' ),
		name: 'sensei-lms/course-content',
		description: __(
			'Display lesson or quiz content, if the learner has access to it.',
			'sensei-lms'
		),
		edit() {
			return <p>{ __( 'Course Content.', 'sensei-lms' ) }</p>;
		},
	},
	{
		...meta,
		title: __( 'Notices', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-notices',
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
		...meta,
		title: __( 'Quiz Progress Bar', 'sensei-lms' ),
		name: 'sensei-lms/quiz-progress',
		description: __(
			'Display progress of questions answered in a quiz.',
			'sensei-lms'
		),
		edit() {
			return (
				<div className="sensei-progress-bar">
					<div className="sensei-progress-bar__label">
						{ __(
							'2 of 10 questions complete (20%)',
							'sensei-lms'
						) }
					</div>
					<div
						role="progressbar"
						className="sensei-progress-bar__bar"
					>
						<div
							style={ {
								width: '20%',
								backgroundColor:
									'var(--wp--preset--color--primary)',
							} }
						></div>
					</div>
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Back to lesson', 'sensei-lms' ),
		name: 'sensei-lms/quiz-back-to-lesson',
		description: __(
			'Return to the lesson the quiz belongs to.',
			'sensei-lms'
		),
		edit() {
			return (
				<span className="sensei-lms-href sensei-lms-quiz-back-to-lesson">
					&lt; { __( 'Back to lesson', 'sensei-lms' ) }
				</span>
			);
		},
	},
	{
		...meta,
		title: __( 'Quiz Actions and Pagination', 'sensei-lms' ),
		name: 'sensei-lms/quiz-actions',
		description: __(
			'Display pagination and actions the learner can take for the current quiz page.',
			'sensei-lms'
		),
		apiVersion: 2,
		edit: function EditQuizActions() {
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
					<div className="sensei-quiz-actions">
						<div className="sensei-quiz-actions-secondary">
							<div className="sensei-quiz-action">
								<div className="button">
									{ __( 'Next', 'sensei-lms' ) }
								</div>
							</div>
							<div className="sensei-quiz-action">
								<div className="button quiz-submit save">
									{ __( 'Save', 'sensei-lms' ) }
								</div>
							</div>
						</div>
						<div className="sensei-quiz-actions-primary wp-block-buttons">
							<div className="sensei-quiz-action wp-block-button sensei-course-button">
								<div className="wp-block-button__link button quiz-submit complete">
									{ __( 'Complete', 'sensei-lms' ) }
								</div>
							</div>
						</div>
					</div>
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Page Actions', 'sensei-lms' ),
		name: 'sensei-lms/page-actions',
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
];

blocks.forEach( ( block ) => {
	const { name, ...settings } = block;
	registerBlockType( name, settings );
} );
