import { render } from '@testing-library/react';
import { useSenseiColorTheme } from './use-sensei-color-theme';

describe( 'useSenseiColorTheme', () => {
	it( 'Should add sensei-color class to the body when mounted and remove when unmounted', () => {
		const TestComponent = () => {
			useSenseiColorTheme();
			return <div />;
		};

		const { unmount } = render( <TestComponent /> );
		expect(
			document.body.classList.contains( 'sensei-color' )
		).toBeTruthy();

		unmount();
		expect(
			document.body.classList.contains( 'sensei-color' )
		).toBeFalsy();
	} );
} );
