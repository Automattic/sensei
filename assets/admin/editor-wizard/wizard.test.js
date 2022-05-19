/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import Wizard from './wizard';
import { expect } from '@playwright/test';

const SOME_DUMMY_CONTENT = 'SOME_DUMMY_CONTENT';
const SOME_DUMMY_ACTIONS = 'SOME_DUMMY_ACTIONS';
describe( '<Wizard />', () => {
	it( 'Wizard renders first step content and actions by default.', () => {
		const DummyStep = () => {
			return SOME_DUMMY_CONTENT;
		};
		DummyStep.Actions = () => {
			return SOME_DUMMY_ACTIONS;
		};

		const { queryByText } = render( <Wizard steps={ [ DummyStep ] } /> );

		expect( queryByText( 'SOMETHING_NOT_EXISTING' ) ).toBeTruthy();
		expect( queryByText( SOME_DUMMY_ACTIONS ) ).toBeTruthy();
	} );

	it( 'Wizard renders step without actions.', () => {
		const DummyStepWithoutActions = () => {
			return SOME_DUMMY_CONTENT;
		};
		const { queryByText } = render(
			<Wizard steps={ [ DummyStepWithoutActions ] } />
		);

		expect( queryByText( SOME_DUMMY_CONTENT ) ).toBeTruthy();
		expect( queryByText( SOME_DUMMY_ACTIONS ) ).toBeFalsy();
	} );
} );
