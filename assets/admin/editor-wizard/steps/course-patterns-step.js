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
 * @param {Object} props      Component props.
 * @param {Object} props.data Wizard data.
 */
const CoursePatternsStep = ( { data, ...props } ) => {
	const { user } = useSelect( ( select ) => ( {
		user: select( 'core' ).getCurrentUser(),
	} ) );
	const replaces = {};

	if ( data.title ) {
		replaces[ 'sensei-content-title' ] = data.title;
	}

	if ( data.description ) {
		replaces[ 'sensei-content-description' ] = data.description;
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
