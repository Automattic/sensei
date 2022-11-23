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
import { useHideEditorWizardUpsell } from '../helpers';

/**
 * Lesson patterns step.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.wizardData Wizard data.
 */
const LessonPatternsStep = ( { wizardData, ...props } ) => {
	const replaces = {};

	if ( wizardData.title ) {
		replaces[ 'sensei-content-title' ] = wizardData.title;
	}

	const shouldHideEditorWizardUpsell = useHideEditorWizardUpsell();

	return (
		<Fragment>
			<PatternsStep
				title={ __( 'Lesson Type', 'sensei-lms' ) }
				replaces={ replaces }
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
				{ __( 'Want More Lesson Types?', 'sensei-lms' ) }
			</b>
			<br />
			{ __(
				'Get flashcards, timed quizzes, image hotspots, and more with Sensei Pro.',
				'sensei-lms'
			) }{ ' ' }
			<a
				className="sensei-editor-wizard-patterns-upsell__link"
				href="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=lesson_patterns_editor_wizard"
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
