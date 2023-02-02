/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import LogoTreeIcon from '../../../icons/logo-tree.svg';
import quizActionsMeta from './quiz-actions.block.json';
import quizBackToLessonMeta from './quiz-back-to-lesson.block.json';
import quizProgressMeta from './quiz-progress.block.json';

const meta = {
	attributes: {},
	icon: {
		src: <LogoTreeIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
};

export default [
	{
		...quizProgressMeta,
		...meta,
		title: __( 'Quiz Progress Bar', 'sensei-lms' ),
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
							className="sensei-progress-bar__progress"
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
		...quizBackToLessonMeta,
		...meta,
		title: __( 'Back to lesson', 'sensei-lms' ),
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
		...quizActionsMeta,
		...meta,
		title: __( 'Quiz Actions and Pagination', 'sensei-lms' ),
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
];
