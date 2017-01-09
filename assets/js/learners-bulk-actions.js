jQuery(document).ready( function() {
    var $ = jQuery.noConflict();
    var config = sensei_learners_bulk_data;

    if (config.is_debug) {
        console.log('Sensei v' + config.sensei_version +  ': Learners Bulk Actions');
    }

    $('#bulk-import-users-from-course-options').select2({
      placeholder: sensei_learners_bulk_data.select_course_placeholder,
      width:'300px',
    });

    // jQuery('input#add_learner_search').select2({
    //     minimumInputLength: 3,
    //     placeholder: woo_learners_general_data.selectplaceholder,
    //     width:'300px',
    //
    //     ajax: {
    //         // in wp-admin ajaxurl is supplied by WordPress and is available globaly
    //         url: ajaxurl,
    //         dataType: 'json',
    //         cache: true,
    //         id: function(user){ return bond._id; },
    //         data: function (input, page) { // page is the one-based page number tracked by Select2
    //             return {
    //                 term: input, //search term
    //                 page: page || 1,
    //                 action: 'sensei_json_search_courses',
    //                 security: 	woo_learners_general_data.search_users_nonce,
    //                 default: ''
    //             };
    //         },
    //         results: function (users, page) {
    //             var validUsers = [];
    //             jQuery.each( users, function (i, val) {
    //                 if( ! jQuery.isEmptyObject( val )  ){
    //                     validUser = { id: i , details: val  };
    //                     validUsers.push( validUser );
    //                 }
    //             });
    //             // wrap the users inside results for select 2 usage
    //             return {  results: validUsers };
    //         }
    //     },
    //
    //     initSelection: function (element, callback) {
    //         //callback();
    //     },
    //     formatResult: function( user ){
    //         return  user.details ;
    //     },
    //     formatSelection: function( user ){
    //         return user.details;
    //     }
    // }); // end select2
});
