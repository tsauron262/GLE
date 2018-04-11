

/**
 * Ajax call
 */

function connect(login, password) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            login: login,
            password: password,
            action: 'connect'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3185.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return.length !== 0) {
                setMessage('alertSubmit', 'Connection.', 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3186.', 'error');
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
    $('select[name=connect]').click(function () {
        connect($('input[name=login]').val(), $('input[name=password]').val());
    });
}



