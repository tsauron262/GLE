

/**
 * Ajax call
 */
function createEvent(first_name, last_name, email, date_born) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            first_name: first_name,
            last_name: last_name,
            email: email,
            date_born: date_born,
            action: 'registration_user'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 8895.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "L'utilisateur a été créée.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 2635.', 'error');
            }
        }
    });
}

/**
 * Ready
 */
$(document).ready(function () {
    $('input[name=date_born]').datepicker({dateFormat: 'dd/mm/yy'})
    initEvents();
});

/**
 * Function
 */

function initEvents() {
    $('button[name=create]').click(function () {
        createEvent($('input[name=first_name]').val(),
                $('input[name=last_name]').val(),
                $('input[name=email]').val(),
                $('input[name=date_born]').val());
    });
}



