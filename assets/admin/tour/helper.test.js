/**
 * Internal dependencies
 */
import { PerformStepAction } from './helper';

describe( 'PerformStepAction', () => {
	it( 'should execute action if it exists for the given index', () => {
		const steps = [
			{ action: jest.fn() },
			{ action: jest.fn() },
			{ action: jest.fn() },
		];

		PerformStepAction( 0, steps );

		expect( steps[ 0 ].action ).toHaveBeenCalled();
	} );

	it( 'should not execute action if index is greater than or equal to steps length', () => {
		const steps = [ { action: jest.fn() }, { action: jest.fn() } ];

		PerformStepAction( 2, steps );

		expect( steps[ 0 ].action ).not.toHaveBeenCalled();
		expect( steps[ 1 ].action ).not.toHaveBeenCalled();
	} );
} );
