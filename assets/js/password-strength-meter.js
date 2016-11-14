function checkPasswordStrength( $pwd, $strengthStatus, $submitBtn ) {
    var pwd = $pwd.val();

    // every time a letter is typed, reset the submit button and the strength meter status
    // disable the submit button
    $submitBtn.attr( 'disabled', 'disabled' );
    $strengthStatus.removeClass( 'short bad good strong empty' );

    if (pwd === '') {
        $strengthStatus.addClass( 'empty' ).html( pwsL10n.empty );
        return;
    }

    // calculate the password strength
    var pwdStrength = wp.passwordStrength.meter( pwd, wp.passwordStrength.userInputBlacklist() );

    // check the password strength
    switch ( pwdStrength ) {

        case 2:
            $strengthStatus.addClass( 'bad' ).html( pwsL10n.bad );
            break;

        case 3:
            $strengthStatus.addClass( 'good' ).html( pwsL10n.good );
            break;

        case 4:
            $strengthStatus.addClass( 'strong' ).html( pwsL10n.strong );
            $submitBtn.removeAttr( 'disabled' );
            break;

        case 5:
            $strengthStatus.addClass( 'short' ).html( pwsL10n.mismatch );
            break;

        default:
            $strengthStatus.addClass( 'short' ).html( pwsL10n.short );
    }

    return pwdStrength;
}

jQuery( document ).ready( function( $ ) {
    var runCheck = function() {
        if ( typeof window.zxcvbn !== 'function' ) {
            setTimeout( runCheck, 50 );
        } else {
            return checkPasswordStrength(
                $( 'input[name=sensei_reg_password]' ),
                $( '#sensei_password_strength' ),
                $( 'input[name=register]' )
            );
        }
    };

    // run the check initially to handle the empty password case
    runCheck();

    // trigger the checkPasswordStrength
    $( 'body' ).on( 'keyup', 'input[name=sensei_reg_password]', function() {
        runCheck();
    });
});
