import { render } from '@testing-library/react';

import useWpAdminFullscreen from './use-wp-admin-fullscreen';

describe( 'useWpAdminFullscreen', () => {
	it( 'Should add classes to the body when mounted and remove when unmounted', () => {
		const testClassName = 'test-class';

		const TestComponent = () => {
			useWpAdminFullscreen( [ testClassName ] );

			return <div />;
		};

		const { unmount } = render( <TestComponent /> );
		expect(
			document.body.classList.contains( testClassName )
		).toBeTruthy();

		unmount();
		expect( document.body.classList.contains( testClassName ) ).toBeFalsy();
	} );
} );
