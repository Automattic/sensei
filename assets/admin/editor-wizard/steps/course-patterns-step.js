/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternsStep from './patterns-step';

/**
 * Course patterns step.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.wizardData Wizard data.
 */
const CoursePatternsStep = ( { wizardData, ...props } ) => {
	const { user } = useSelect( ( select ) => ( {
		user: select( 'core' ).getCurrentUser(),
	} ) );
	const replaces = {};

	if ( wizardData.title ) {
		replaces[ 'sensei-content-title' ] = wizardData.title;
	}

	if ( wizardData.description ) {
		replaces[ 'sensei-content-description' ] = wizardData.description;
	}

	if ( user.name ) {
		replaces[ 'sensei-content-author' ] = `<strong>${ user.name }</strong>`;
	}

	return (
		<PatternsStep
			title={ __( 'Course Layout', 'sensei-lms' ) }
			replaces={ replaces }
			{ ...props }
		/>
	);
};

CoursePatternsStep.Actions = PatternsStep.Actions;

export default CoursePatternsStep;
