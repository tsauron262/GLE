

/**
 * Ajax call
 */

function getEvents() {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_events'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.events.length !== 0) {
                out.events.forEach(function (event) {
                    $('select[name=id_event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
                });
                initEvents();
                $(".chosen-select").chosen({
                    placeholder_text_single: 'Evènement',
                    no_results_text: 'Pas de résultat'});
                $('select[name=id_event]').change(function () {
                    changeEventSession($('select[name=id_event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=id_event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=id_event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $(".chosen-select").trigger("chosen:updated");
                    }
                }
            } else {
                setMessage('alertSubmit', "Créer un évènement avant de définir un tarif.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}

function createTariff() {

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
                setMessage('alertSubmit', 'Tariff créer.', 'msg');
                $('input[name=label]').val('');
                $('input[name=price]').val('');
                $('input[name=number_place]').val('');
                $('input[name=file]').val('');
                $('input[name=date_start]').val('');
                $('input[name=date_end]').val('');
                $('#img_display').attr('src', '');

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
    getEvents();
});

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
            createTariff();
        } else {
            alert('Navigateur non compatible');
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