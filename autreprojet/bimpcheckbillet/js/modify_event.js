var events;

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
                events = out.events;
                out.events.forEach(function (event) {
                    $('select[name=id_event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
                });
                initEvents();
                $(".chosen-select").chosen({
                    placeholder_text_single: 'Evènement',
                    no_results_text: 'Pas de résultat'});
            } else {
                setMessage('alertSubmit', "Créer un évènement avant de définir un tarif.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}

function setImage(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            folder: 'img/event/',
            name: id_event,
            action: 'get_image'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1844.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            $("#img_display").attr('src', 'data:image/png;base64,' + atob(out.src));
        }
    });
}

function modifyEvent(id_event, label, date_start, time_start, date_end, time_end) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            label: label,
            date_start: date_start,
            time_start: time_start,
            date_end: date_end,
            time_end: time_end,
            action: 'modify_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "Evènement modifié.", 'msg');
            } else {
            setMessage('alertSubmit', 'Erreur serveur 3584.', 'error');
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
    getEvents();
    $("#img_display").attr('src', '../img/event/1.png');

});

/**
 * Functions
 */

function initEvents() {

    $("#file").change(function () {
        readURL(this, '#img_display');
    });
    

    $('button[name=modify]').click(function (e) {
        e.preventDefault();
//        if (window.FormData !== undefined) {
        modifyEvent($('select[name=id_event] > option:selected').val(),
                $('input[name=label]').val(),
                $('input[name=date_start]').val(),
                $('input[name=time_start]').val(),
                $('input[name=date_end]').val(),
                $('input[name=time_end]').val());
//        } else {
//            alert('Pas compatible avec navigateur');
//        }
//        return false;
    });

    $('select[name=id_event]').change(function () {
        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0)
            autoFill(id_event);
    });
}

function autoFill(id_event) {
    var event = getEventById(id_event);
    $('input[name=label]').val(event.label);
    $('input[name=date_start]').val(formatDate(event.date_start));
    $('input[name=time_start]').val(formatTime(event.date_start));
    $('input[name=date_end]').val(formatDate(event.date_end));
    $('input[name=time_end]').val(formatTime(event.date_end));
//    setImage(event.id);   
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

function getEventById(id_event) {
    var out;
    events.forEach(function (event) {
        if (event.id === id_event)
            out = event;
    });
    return out;
}

/**
 * From yyyy-mm-dd hh:mm:ss to dd/mm/yyyy
 * @param {type} date
 */
function formatDate(date) {
    var reg_exp = /(\d+)-(\d+)-(\d+)\s\d+:\d+:\d+/;
    return date.replace(reg_exp, '$3/$2/$1');
}

/**
 * From yyyy-mm-dd hh:mm:ss to hh:mm
 * @param {type} date
 */
function formatTime(date) {
    var reg_exp = /\d+-\d+-\d+\s(\d+):(\d+):\d+/;
    return date.replace(reg_exp, '$1:$2');
}