/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatternsStep from './patterns-step';
import LogoTreeIcon from '../../../icons/logo-tree.svg';
import { getSenseiProUpsellUrl } from '../../helpers';
import { useHideEditorWizardUpsell } from '../helpers';

/**
 * Lesson patterns step.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.wizardData Wizard data.
 */
const LessonPatternsStep = ( { wizardData, ...props } ) => {
	const replaces = {};
	const excludeLessonPatterns = [ 'sensei-lms/default' ];

	if ( wizardData.title ) {
		replaces[ 'sensei-content-title' ] = wizardData.title;
	}

	if ( wizardData.description ) {
		replaces[ 'sensei-content-description' ] = wizardData.description;
	}

	const shouldHideEditorWizardUpsell = useHideEditorWizardUpsell();

	return (
		<Fragment>
			<PatternsStep
				title={ __( 'Lesson Layout', 'sensei-lms' ) }
				replaces={ replaces }
				patternsToExclude={ excludeLessonPatterns }
				{ ...props }
			/>
			<PatternsStep.UpsellFill>
				{ shouldHideEditorWizardUpsell ? null : <UpsellBlock /> }
			</PatternsStep.UpsellFill>
		</Fragment>
	);
};

/**
 * The Pattern Upsell block, which is shown only for Sensei Free users.
 */
const UpsellBlock = () => (
	<div className="sensei-editor-wizard-patterns-upsell">
		<LogoTreeIcon className="sensei-editor-wizard-patterns-upsell__logo" />
		<div className="sensei-editor-wizard-patterns-upsell__text">
			<b className="sensei-editor-wizard-patterns-upsell__title">
				{ __( 'Want More Lesson Layouts?', 'sensei-lms' ) }
			</b>
			<br />
			{ __(
				'Get flashcards, timed quizzes, image hotspots, and more with Sensei Pro.',
				'sensei-lms'
			) }{ ' ' }
			<a
				className="sensei-editor-wizard-patterns-upsell__link"
				href={ getSenseiProUpsellUrl(
					'lesson_patterns_editor_wizard'
				) }
				rel="noreferrer external"
				target="blank"
			>
				{ __( 'Learn more.', 'sensei-lms' ) }
			</a>
		</div>
	</div>
);

LessonPatternsStep.Actions = PatternsStep.Actions;

export default LessonPatternsStep;
