/**
 * External dependencies
 */
/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const RigthWrongToggle = ( { value, onChange } ) => (
	<div className="sensei-lms-question-block__answer--multiple-choice__toggle__wrapper">
		<Button
			isPrimary
			className="sensei-lms-question-block__answer--multiple-choice__toggle"
			onClick={ onChange }
		>
			{ value
				? __( 'Right', 'sensei-lms' )
				: __( 'Wrong', 'sensei-lms' ) }
		</Button>
	</div>
);
