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
        var $post_id = 0;
        if ( typeof( id ) == 'object' )
            $post_id = parseInt( this.getId( id ) );

        if ( $post_id > 0 ) {
            // define the edit row
            var $edit_row = $( '#edit-' + $post_id );
            var $post_row = $( '#post-' + $post_id );

            // get the data
            var $book_author = $( '.column-book_author', $post_row ).html();
            var $inprint = !! $('.column-inprint>*', postRow).attr('checked');

            // populate the data
            $( ':input[name="book_author"]', $edit_row ).val( $book_author );
            $( ':input[name="inprint"]', $edit_row ).attr('checked', $inprint );


        }
    };

})(jQuery);