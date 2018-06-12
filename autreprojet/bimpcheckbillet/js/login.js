

/**
 * Ajax call
 */

function login(login, pass_word) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            login: login,
            pass_word: pass_word,
            action: 'login'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3185.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.errors === undefined) {
                setMessage('alertSubmit', 'Erreur serveur 3174.', 'error');
            } else {
                window.location.replace($('input#url_after_login').val());
            }
        }
    });
}

/**
 * Ready
 */
$(document).ready(function () {
    initEvents();
    $('input[name=login]').focus();
});

/**
 * Function
 */

function initEvents() {
    $('button[name=connect]').click(function () {
        login($('input[name=login]').val(), $('input[name=pass_word]').val());
    });

    $('input[name=pass_word]').keyup(function (e) {
        var code = e.which;
        if (code === 13) {
            e.preventDefault();
            login($('input[name=login]').val(), $('input[name=pass_word]').val());
        }
    });
}


