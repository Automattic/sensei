jQuery( document ).ready( function( $ ) {

  // Get the Deactivate link for the Sensei LMS plugin in the plugins page.
  $deactivation_link = jQuery('#the-list').find('[data-slug="sensei-lms"] span.deactivate a');

  // When Deactivate button clicked, instead of deactivating plugin, show survey modal.
  $deactivation_link.on('click', function ( event ) {
    event.preventDefault();

    $( '#sensei_deactivation_form_wrapper' ).modal( {
      fadeDuration: 250,
      showClose: false,
    } );

  } );

  // When submitting survey modal, send survey data and continue with plugin deactivation.
  jQuery( 'body' ).on( 'submit', '#sensei_deactivation_form_wrapper', function( event ) {

    jQuery.modal.close();
    location.href = $deactivation_link.attr('href');

  } );

  // If user skips submission, continue with plugin deactivation.
  jQuery( '#sensei_deactivation_form_cancel' ).on('click', function ( event ) {

    location.href = $deactivation_link.attr('href');

  });

} );
