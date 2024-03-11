/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SenseiTourKitStep from './index';

jest.mock(
	'@automattic/tour-kit/src/variants/wpcom/components/wpcom-tour-kit-step',
	() => () => {
		return (
			<div data-testid="wpcom-tour-kit-step">WpcomTourKitStep output</div>
		);
	}
);

describe( 'SenseiTourKitStep', () => {
	it( 'should call PerformStepAction with correct arguments when mounted', () => {
		const stepAction0 = jest.fn();
		const stepAction1 = jest.fn();
		const stepAction2 = jest.fn();
		const props = {
			currentStepIndex: 1,
			steps: [
				{
					action: stepAction0,
				},
				{
					action: stepAction1,
				},
				{
					action: stepAction2,
				},
			],
		};

		render( <SenseiTourKitStep { ...props } /> );
		props.currentStepIndex = 2;
		render( <SenseiTourKitStep { ...props } /> );

		// Check if PerformStepAction was called with the correct arguments.
		expect( stepAction1 ).toHaveBeenCalled();
		expect( stepAction2 ).toHaveBeenCalled();
		expect( stepAction0 ).not.toHaveBeenCalled();
	} );

	it( 'should render WpcomTourKitStep component with correct props', () => {
		// Mock props
		const props = {
			currentStepIndex: 0,
			steps: [ 'step1', 'step2', 'step3' ],
		};

		const { getByTestId } = render( <SenseiTourKitStep { ...props } /> );

		// Check if WpcomTourKitStep component is rendered with correct props.
		expect( getByTestId( 'wpcom-tour-kit-step' ) ).toBeInTheDocument();
	} );
} );
