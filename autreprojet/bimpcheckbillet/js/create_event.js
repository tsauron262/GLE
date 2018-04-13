

/**
 * Ajax call
 */
function createEvent(label, date_start, date_end) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            label: label,
            date_start: date_start,
            date_end: date_end,
            action: 'create_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1895.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "L'évènement a été créée.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 1853.', 'error');
            }
        }
    });
}

/**
 * Ready
 */
$(document).ready(function () {
    $('input[name=date_start]').datepicker({dateFormat: 'dd/mm/yy'})
    $('input[name=date_end]').datepicker({dateFormat: 'dd/mm/yy'})
    initEvents();
});

/**
 * Function
 */

function initEvents() {
    $('button[name=create]').click(function () {
        createEvent($('input[name=label]').val(),
                $('input[name=date_start]').val(),
                $('input[name=date_end]').val());
    });
}



