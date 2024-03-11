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
import { PerformStepAction } from '../../helper';

export default function SenseiTourKitStep( { ...props } ) {
	useEffect( () => {
		PerformStepAction( props.currentStepIndex, props.steps );
	}, [ props.currentStepIndex, props.steps ] );
	return <WpcomTourKitStep { ...props } />;
}
