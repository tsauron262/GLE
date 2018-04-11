

/**
 * Ajax call
 */

function register(login, password) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            login: login,
            password: password,
            action: 'register'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3142.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.id_inserted > 0) {
                window.location.replace('home.php');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3116.', 'error');
            }
        }
    });
}

/**
 * Ready
 */
$(document).ready(function () {
    initEvents();
});

/**
 * Function
 */

function initEvents() {
    $('button[name=register]').click(function () {
        $('input[name=conf_password').css('border', '1px solid #ced4da;');
        $('input[name=conf_password').css('border', '1px solid #ced4da;');
        $('input[name=conf_password').css('border', '1px solid #ced4da;');

        var stop = false;
        if ($('input[name=login').val() === '') {
            $('input[name=login').css('border', '1px solid red');
            stop = true;
        }
        if ($('input[name=password').val() === '') {
            $('input[name=password').css('border', '1px solid red');
            stop = true;
        }
        if ($('input[name=conf_password').val() === '') {
            $('input[name=conf_password').css('border', '1px solid red');
            stop = true;
        }
        if ($('input[name=conf_password').val() !== $('input[name=password]').val()) {
            setMessage('alertSubmit', 'Mot de passe non ou mal confirmé.', 'error');
            stop = true;
        }

        if (!stop)
            register($('input[name=login]').val(), $('input[name=password]').val());

    });
}



