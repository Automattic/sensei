jQuery(document).ready( function($) {
	jQuery( 'select.range-input' ).each( function () {
		// Get the range field's ID value.
		var idValue = jQuery( this ).attr( 'id' );

		if ( idValue ) {
			var select = jQuery( this );

			var selectedValue = select.val();

			var numberOfOptions = jQuery( this ).find( 'option' ).length;

			var slider = jQuery( '<div class="slider"></div>' ).insertAfter( select ).slider({
				min: 1,
				max: parseInt( numberOfOptions ),
				range: 'min',
				value: select[0].selectedIndex + 1,
				slide: function( event, ui ) {
					select[0].selectedIndex = ui.value - 1;
					jQuery( this ).parents( 'td' ).find( '.slider-value' ).text( select.val() );
				}
			});

			slider.after( '<div class="slider-value">' + selectedValue + '</div>' );

			select.hide();

			select.change(function() {
				slider.slider( "value", this.selectedIndex + 1 );
				jQuery( this ).parents( 'td' ).find( '.slider-value' ).text( jQuery( this ).val() );
			});
		}
	});
});