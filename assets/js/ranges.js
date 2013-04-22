jQuery(document).ready( function($) {
	jQuery( 'select.range-input' ).each( function () {
		// Get the range field's ID value.
		var idValue = jQuery( this ).attr( 'id' );

		if ( idValue ) {
			var select = jQuery( this ); // The select element being targeted.
			var selectedValue = select.val(); // The currently selected value.
			var numberOfOptions = jQuery( this ).find( 'option' ).length; // The number of options in the current select element.
			var sliderValue = jQuery( '<div></div>' ).addClass( 'slider-value' ); // A jQuery object to create the slider value display. To be filled below.

			// Initialize the slider.
			var slider = jQuery( '<div></div>' ).addClass( 'slider' ).insertAfter( select ).slider({
				min: 1,
				max: parseInt( numberOfOptions ),
				range: 'min',
				value: select[0].selectedIndex + 1,
				slide: function( event, ui ) {
					select[0].selectedIndex = ui.value - 1;
					jQuery( this ).parents( 'td' ).find( '.slider-value' ).text( select.val() );
				}
			});

			sliderValue.text( selectedValue ); // Fill the slider value element with the correct slider value.
			slider.after( sliderValue ); // Insert the slider value display.
			select.hide(); // Hide the select element.

			// When the select element changes, update the slider value. The select element is changed by the slider.
			select.change(function() {
				slider.slider( 'value', this.selectedIndex + 1 );
				jQuery( this ).parents( 'td' ).find( '.slider-value' ).text( jQuery( this ).val() );
			});
		}
	});
});