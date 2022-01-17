/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ChevronLeft from '../icons/chevron-left.svg';
import ChevronRight from '../icons/chevron-right.svg';
import ChevronUp from '../icons/chevron-up.svg';
import MenuIcon from '../icons/menu.svg';
import CircleIcon from '../icons/circle.svg';
import HalfCircleIcon from '../icons/half-filled-circle.svg';
import CheckCircleIcon from '../icons/check-filled-circle.svg';
import LockIcon from '../icons/lock.svg';
import EyeIcon from '../icons/eye.svg';

const ICONS = {
	'not-started': CircleIcon,
	'in-progress': HalfCircleIcon,
	ungraded: HalfCircleIcon,
	completed: CheckCircleIcon,
	failed: HalfCircleIcon,
	locked: LockIcon,
	preview: EyeIcon,
};

/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../blocks/register-sensei-blocks';

const meta = {
	category: 'sensei-lms',
	supports: {},
	attributes: {},
};

const blocks = [
	{
		...meta,
		title: __( 'Course Title', 'sensei-lms' ),
		name: 'sensei-lms/course-title',
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
		title: __( 'Sidebar Toggle Button', 'sensei-lms' ),
		name: 'sensei-lms/sidebar-toggle-button',
		edit() {
			return (
				<div className="sensei-course-theme__sidebar-toggle">
					{ MenuIcon }
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Lesson Actions', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-lesson-actions',
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
		edit() {
			return <div>{ __( 'Collapse', 'sensei-lms' ) }</div>;
		},
	},
	{
		...meta,
		title: __( 'Course Navigation', 'sensei-lms' ),
		name: 'sensei-lms/course-navigation',
		edit() {
			const structure = [
				{
					title: __( 'Module A', 'sensei-lms' ),
					lessons: [
						{
							title: 'First Lesson',
							status: 'preview',
						},
						{
							title: 'Second Lesson',
							status: 'completed',
							quiz: true,
						},
						{
							title: 'Third Lesson',
							status: 'in-progress',
							quiz: true,
						},
						{
							title: 'Fourth Lesson',
							status: 'not-started',
							quiz: true,
						},
					],
				},
				{
					title: __( 'Module B', 'sensei-lms' ),
					lessons: [
						{
							title: 'Fifth Lesson',
							status: 'not-started',
							quiz: true,
						},
						{
							title: 'Sixth Lesson',
							status: 'locked',
						},
						{
							title: 'Seventh Lesson',
							status: 'locked',
						},
					],
				},
			];

			const Module = ( { title, lessons } ) => (
				<section className="sensei-lms-course-navigation-module sensei-collapsible">
					<header className="sensei-lms-course-navigation-module__header">
						<div className="sensei-collapsible__toggle">
							<h2 className="sensei-lms-course-navigation-module__title">
								{ title }
							</h2>
							<ChevronUp className="sensei-lms-course-navigation-module__collapsible-icon" />
						</div>
					</header>
					<div className="sensei-lms-course-navigation-module__lessons sensei-collapsible__content">
						{ lessons.map( Lesson ) }
					</div>
					<div className="sensei-lms-course-navigation-module__summary">
						{ __( '2 lessons, 0 quizzes', 'sensei-lms' ) }
					</div>
				</section>
			);

			const Lesson = ( { title, quiz, status } ) => {
				const StatusIcon = ICONS[ status ];
				return (
					<div
						className={ `sensei-lms-course-navigation-lesson status-${ status }` }
					>
						<span className="sensei-lms-course-navigation-lesson__link">
							<StatusIcon className="sensei-lms-course-navigation-lesson__status" />
							<span className="sensei-lms-href sensei-lms-course-navigation-lesson__title">
								{ title }
							</span>
						</span>
						{ quiz && (
							<span className="sensei-lms-href sensei-lms-course-navigation-lesson__extra">
								{ __( 'Quiz', 'sensei-lms' ) }
							</span>
						) }
					</div>
				);
			};

			return (
				<div className="sensei-lms-course-navigation">
					<div className="sensei-lms-course-navigation__modules">
						{ structure.map( Module ) }
					</div>
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Contact Teacher', 'sensei-lms' ),
		name: 'sensei-lms/button-contact-teacher',
		edit() {
			return (
				<div className="sensei-course-theme-contact-teacher__button is-primary">
					{ __( 'Contact Teacher', 'sensei-lms' ) }
				</div>
			);
		},
	},
	{
		...meta,
		title: __( 'Exit Course', 'sensei-lms' ),
		name: 'sensei-lms/exit-course',
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
		edit() {
			return <em>{ __( 'MODULE', 'sensei-lms' ) }</em>;
		},
	},
	{
		...meta,
		title: __( 'Post Title', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-post-title',
		edit() {
			return <h1>{ __( 'Lesson Title', 'sensei-lms' ) }</h1>;
		},
	},
	{
		...meta,
		title: __( 'Course Content', 'sensei-lms' ),
		name: 'sensei-lms/course-content',
		edit() {
			return <p>{ __( 'Lesson Content', 'sensei-lms' ) }</p>;
		},
	},
	{
		...meta,
		title: __( 'Notices', 'sensei-lms' ),
		name: 'sensei-lms/course-theme-notices',
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
		name: 'sensei-lms/quiz-progress-bar',
		edit() {
			return (
				<div>
					<div className="sensei-progress-bar__heading">
						<div className="sensei-progress-bar__label">
							{ __(
								'2 of 10 questions complete (20%)',
								'sensei-lms'
							) }
						</div>
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
		title: __( 'Quiz actions and pagination', 'sensei-lms' ),
		name: 'sensei-lms/quiz-actions',
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
];

registerSenseiBlocks( blocks );
