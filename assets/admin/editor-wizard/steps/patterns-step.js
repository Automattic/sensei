/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';

/**
 * Choose patterns step.
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.title        Step title.
 * @param {Object}   props.data         Wizard data.
 * @param {Function} props.onCompletion On completion callback.
 */
const PatternsStep = ( { title, data, onCompletion } ) => (
	<div className="sensei-editor-wizard-modal__content">
		<h1 className="sensei-editor-wizard-modal__sticky-title">{ title }</h1>
		<PatternsList
			title={ data.newCourseTitle }
			description={ data.newCourseDescription }
			onChoose={ onCompletion }
		/>
	</div>
);

/**
 * Choose patterns step.
 *
 * @param {Object}   props            Compoent props.
 * @param {Function} props.skipWizard Skip wizard function.
 */
PatternsStep.Actions = ( { skipWizard } ) => (
	<div>
		<Button isTertiary onClick={ skipWizard }>
			{ __( 'Start with default layout', 'sensei-lms' ) }
		</Button>
	</div>
);

export default PatternsStep;
