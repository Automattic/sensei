/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatternsStep from './patterns-step';
import LogoIcon from '../../../icons/logo.svg';
import { EXTENSIONS_STORE } from '../../../extensions/store';

/**
 * Lesson patterns step.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.data Wizard data.
 */
const LessonPatternsStep = ( { data, ...props } ) => {
	const { senseiProExtension } = useSelect(
		( select ) => ( {
			senseiProExtension: select(
				EXTENSIONS_STORE
			).getSenseiProExtension(),
		} ),
		[]
	);

	const replaces = {};

	if ( data.lessonTitle ) {
		replaces[ 'sensei-content-title' ] = data.lessonTitle;
	}

	const isSenseiProInstalled =
		! senseiProExtension || senseiProExtension.is_installed === true;

	return (
		<Fragment>
			<PatternsStep
				title={ __( 'Lesson Patterns', 'sensei-lms' ) }
				replaces={ replaces }
				{ ...props }
			/>
			<PatternsStep.UpsellFill>
				{ isSenseiProInstalled ? null : <UpsellBlock /> }
			</PatternsStep.UpsellFill>
		</Fragment>
	);
};

/**
 * The Pattern Upsell block, which is shown only for Sensei Free users.
 */
const UpsellBlock = () => (
	<div className="sensei-editor-wizard-patterns-upsell">
		<LogoIcon className="sensei-editor-wizard-patterns-upsell__logo" />
		<div className="sensei-editor-wizard-patterns-upsell__text">
			<b>
				{ __(
					'Want more lesson types, check out Sensei Pro.',
					'sensei-lms'
				) }
			</b>{ ' ' }
			<br />
			{ __(
				'Flashcards, timed quizes, image hotspots, tasklists, and more.',
				'sensei-lms'
			) }{ ' ' }
			<a
				className="sensei-editor-wizard-patterns-upsell__link"
				href="https://senseilms.com/pricing/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=lesson_patterns_editor_wizard"
				rel="noreferrer external"
			>
				{ __( 'Learn more', 'sensei-lms' ) }
			</a>
		</div>
	</div>
);

LessonPatternsStep.Actions = PatternsStep.Actions;

export default LessonPatternsStep;
