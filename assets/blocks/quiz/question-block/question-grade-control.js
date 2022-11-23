/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import { _n } from '@wordpress/i18n';
/**
 * External dependencies
 */
import { v4 as uuid } from 'uuid';
/**
 * Internal dependencies
 */
import NumberControl from '../../editor-components/number-control';

/**
 * Question Grade control. Number spinner with points suffix.
 *
 * @param {Object} props NumberControl props.
 */
export const QuestionGradeControl = ( props ) => {
	const id = useMemo( () => uuid(), [] );
	return (
		<NumberControl
			id={ id }
			min={ 0 }
			step={ 1 }
			{ ...props }
			suffix={ _n( 'Point', 'Points', props.value, 'sensei-lms' ) }
		/>
	);
};
