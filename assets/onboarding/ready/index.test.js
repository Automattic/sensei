import { render } from '@testing-library/react';
import { Ready } from './index';

const mockStepData = {
	admin_email: 'admin@test.local',
	mc_url: 'http://external.local/campaign',
	gdpr_field: 'SDCTZX3',
};
jest.mock( '../data/use-setup-wizard-step', () => ( {
	useSetupWizardStep: jest.fn().mockImplementation( () => ( {
		stepData: mockStepData,
	} ) ),
} ) );

describe( '<Ready />', () => {
	it( 'Should have a sign-up form pointing to the mailing list provider', () => {
		const { container } = render( <Ready /> );

		const form = container.querySelector( 'form' );
		expect( form.getAttribute( 'action' ) ).toEqual( mockStepData.mc_url );
	} );

	it( 'Should have the admin e-mail pre-filled in the sign-up form', () => {
		const { container } = render( <Ready /> );

		const form = container.querySelector( 'form' );
		expect( form.querySelector( 'input[type=email]' ).value ).toEqual(
			mockStepData.admin_email
		);
	} );

	it( 'Should have a create course button', () => {
		const { queryByText } = render( <Ready /> );

		expect(
			queryByText( 'Create a course' ).getAttribute( 'href' )
		).toEqual( 'post-new.php?post_type=course' );
	} );

	it( 'Should have a create your first course link.', () => {
		const { queryByText } = render( <Ready /> );

		expect(
			queryByText( /create your first course/ ).getAttribute( 'href' )
		).toEqual( 'https://senseilms.com/lesson/courses/' );
	} );
} );
