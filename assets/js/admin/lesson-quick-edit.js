(function($) {
    // we create a copy of the WP inline edit post function
    var $wp_inline_edit = inlineEditPost.edit;
    // and then we overwrite the function with our own code
    inlineEditPost.edit = function( id ) {
        // "call" the original WP edit function
        // we don't want to leave WordPress hanging
        $wp_inline_edit.apply( this, arguments );

        // now we take care of our business

        // get the post ID
        var postId = 0;
        if ( typeof( id ) == 'object' ) {

            postId = parseInt(this.getId(id));

        }

        if ( postId > 0 ) {

            // define the edit row
            var editRow = $( '#edit-' + postId );
            var postRow = $( '#post-' + postId );
            var senseiFieldValues = window['sensei_quick_edit_'+postId];

            //load the relod function on the save button click
            editRow.find('a.save').on( 'click', function(){

                location.reload();

            });

            // populate the data
            //data is localized in sensei_quick_edit object
            $( ':input[name="lesson_course"] option[value="'+ senseiFieldValues.lesson_course +'"] ', editRow ).attr('selected', true );
            $( ':input[name="lesson_complexity"] option[value="'+ senseiFieldValues.lesson_complexity +'"] ', editRow ).attr('selected', true );
            if( 'on' ==senseiFieldValues.pass_required ||  '1' ==senseiFieldValues.pass_required  ){
                senseiFieldValues.pass_required = 1;
            }else{
                senseiFieldValues.pass_required = 0;
            }
            $( ':input[name="pass_required"] option[value="'+ senseiFieldValues.pass_required +'"] ', editRow ).attr('selected', true );
            $( ':input[name="quiz_passmark"]', editRow ).val( senseiFieldValues.quiz_passmark );

            if( 'on' ==senseiFieldValues.enable_quiz_reset ||  '1' ==senseiFieldValues.enable_quiz_reset  ){
                senseiFieldValues.enable_quiz_reset = 1;
            }else{
                senseiFieldValues.enable_quiz_reset = 0;
            }
            $( ':input[name="enable_quiz_reset"] option[value="'+ senseiFieldValues.enable_quiz_reset +'"] ', editRow ).attr('selected', true );

        }
    };

})(jQuery);