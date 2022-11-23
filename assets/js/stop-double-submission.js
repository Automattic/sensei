( () => {
	document
		.querySelectorAll( '.sensei-stop-double-submission' )
		.forEach( ( element ) => {
			let clicks = 0;

			element.addEventListener( 'click', ( e ) => {
				if ( clicks > 0 ) {
					e.preventDefault();
					return;
				}

				clicks++;
				setTimeout( () => ( clicks = 0 ), 2000 );
			} );
		} );
} )();
