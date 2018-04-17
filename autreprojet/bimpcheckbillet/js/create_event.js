
/**
 * Ajax call
 */
function createEvent() {
    var formData = new FormData($('#create_form')[0]);

    $.ajax({
        url: '../interface.php',
        type: 'POST',
        data: formData,
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                alert("L'évènement a été créée.");
                window.location.replace('home.php');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 1853.', 'error');
            }
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1895.', 'error');
        },
        cache: false,
        contentType: false,
        processData: false
    });
}
/**
 * Ready
 */
$(document).ready(function () {
    $('input[name=date_start]').datepicker({dateFormat: 'dd/mm/yy'})
    $('input[name=date_end]').datepicker({dateFormat: 'dd/mm/yy'})
    initEvents();

}
);
/**
 * Function
 */


function initEvents() {
    $("#file").change(function () {
        readURL(this, '#img_display');
    });

    $('#create_form').submit(function (e) {
        e.preventDefault();
        if (window.FormData !== undefined) {
            createEvent();
        } else {
            alert('pas compatible avec navigateur');
        }

        return false;
    });
}


function readURL(input, id_placeholder_img) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $(id_placeholder_img).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}