

/**
 * Ajax call
 */

function register(first_name, last_name, email, login, pass_word) {
    console.log('envoyé');
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            first_name: first_name,
            last_name: last_name,
            email: email,
            login: login,
            pass_word: pass_word,
            action: 'register'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3142.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
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
        var error_code = 0;

        $('input').each(function () {
            $(this).removeClass('border_red');
            error_code += checkParameter($(this));
        });

        if ($('input[name=pass_word]').val() !== $('input[name=conf_pass_word]').val()) {
            setMessage('alertSubmit', 'Mot de passe mal ou non confirmé.', 'error');
            error_code--;
        }
        
        console.log(error_code);

        if (error_code === 0)
            register($('input[name=first_name]').val(),
                    $('input[name=last_name]').val(),
                    $('input[name=email]').val(),
                    $('input[name=login]').val(),
                    $('input[name=pass_word]').val());

    });
}

function checkParameter(element) {
    if (element.val() === '') {
        element.addClass('border_red');
        return -1;
    }
    return 0;
}



