/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
/**
 * External dependencies
 */
import WpcomTourKitStep from '@automattic/tour-kit/src/variants/wpcom/components/wpcom-tour-kit-step';
/**
 * Internal dependencies
 */
import { performStepAction } from '../../helper';

function SenseiTourKitStep( { ...props } ) {
	useEffect( () => {
		performStepAction( props.currentStepIndex, props.steps );
	}, [ props.currentStepIndex, props.steps ] );
	return <WpcomTourKitStep { ...props } />;
}

export default SenseiTourKitStep;
