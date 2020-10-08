( () => {
	const onReady = ( fn ) => {
		if ( 'loading' !== document.readyState ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	};

	onReady( () => {
		if (
			0 ===
			document.querySelectorAll(
				'.wp-block-sensei-lms-course-outline__arrow'
			).length
		) {
			return;
		}

		const modules = document.querySelectorAll(
			'.wp-block-sensei-lms-course-outline-module'
		);

		modules.forEach( ( module ) => {
			const moduleContent = module.querySelector(
				'.wp-block-sensei-lms-collapsible'
			);

			const originalHeight = moduleContent.offsetHeight;
			const toggleButton = module.querySelector(
				'.wp-block-sensei-lms-course-outline__arrow'
			);

			moduleContent.style.height = originalHeight + 'px';

			toggleButton.addEventListener( 'click', () => {
				toggleButton.classList.toggle( 'collapsed' );
				const collapsed = moduleContent.classList.toggle( 'collapsed' );

				if ( ! collapsed ) {
					moduleContent.style.height = originalHeight + 'px';
				} else {
					moduleContent.style.height = '0px';
				}
			} );
		} );
	} );
} )();
