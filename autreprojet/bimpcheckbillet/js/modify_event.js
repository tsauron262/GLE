var URL_PRESTASHOP = URL_PRESTA + '/modules/zoomdici/ajax.php';

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
                var filename;
                out.events.forEach(function (event) {
                    $('select[name=id_event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
                    if (parseInt(event.id) === parseInt(id_event_session))
                        filename = event.filename;
                });
                initEvents();
                $(".chosen-select").chosen({
                    placeholder_text_single: 'Evènement',
                    no_results_text: 'Pas de résultat'});
                var id_event = getUrlParameter('id_event');
                if (id_event > 0) {
                    $('select[name=id_event] option[value=' + id_event + ']').prop('selected', true);
                    $('select[name=id_event]').trigger("chosen:updated");
                    $('select[name=id_event]').trigger('change');
                }
                if (id_event_session > 0) {
                    if (!$('select[name=id_event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=id_event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $(".chosen-select").trigger("chosen:updated");
                        $('select[name=id_event]').trigger('change');
                    }
                    $('img#img_display').attr('src', +'../img/event/' + filename);
                }
            } else {
                setMessage('alertSubmit', "Erreur 1286.", 'error');
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

function modifyEvent(id_event, label, description, date_start, time_start, date_end, time_end) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            label: label,
            description: description,
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

function draftEvent(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'draft_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 7886.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "Evènement définit comme brouillon.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 2354.', 'error');
            }
        }
    });
}

function validateEvent(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'validate_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3486.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "Evènement validé.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 2484.', 'error');
            }
        }
    });
}

function closeEvent(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'close_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3486.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "Evènement fermé.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 2484.', 'error');
            }
        }
    });
}

function createPrestashopCategory(id_event, label_event) {

    $.ajax({
        type: "POST",
        url: URL_PRESTASHOP,
        data: {
            label: label_event,
            action: 'createPrestashopCategory'
        },
        error: function () {

            setMessage('alertSubmit', 'Erreur serveur 2584.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
                addIdCateg(id_event, out.id_inserted);
            } else {
                setMessage('alertSubmit', 'Erreur serveur 2476.', 'error');
            }
        }
    });
}

function addIdCateg(id_event, id_categ) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            id_categ: id_categ,
            action: 'set_id_categ'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1567.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                alert("La catégorie a été créée dans prestashop.");
                location.reload();
            } else {
                setMessage('alertSubmit', 'Erreur serveur 2548.', 'error');
            }
        }
    });
}

function toggleActiveCategory(id_categ) {

    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            id_categ: id_categ,
            action: 'toggleCategActive'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 5761.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.toggled) === 1) {
                if (out.active == true)
                    alert("Cette catégorie est maintenant activée.");
                else
                    alert("Cette catégorie est maintenant désactivée.");
            } else {
                setMessage('alertSubmit', "Erreur inconnue 2349.", 'error');
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

    tinymce.init({selector: 'textarea'});

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
        var id_event = $('select[name=id_event] > option:selected').val();
        if (parseInt(id_event) > 0) {
//        if (window.FormData !== undefined) {
            modifyEvent($('select[name=id_event] > option:selected').val(),
                    $('input[name=label]').val(),
                    tinymce.get('description').getContent(),
                    $('input[name=date_start]').val(),
                    $('input[name=time_start]').val(),
                    $('input[name=date_end]').val(),
                    $('input[name=time_end]').val());
//        } else {
//            alert('Pas compatible avec navigateur');
//        }
//        return false;
        } else {
            setMessage('alertSubmit', 'Veuillez sélectionner un évènement.', 'error');
        }
    });

    $('select[name=id_event]').change(function () {
        var id_categ;
        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0) {
            var event = getEventById(id_event);
            $('img#img_display').attr('src', '..//img/event/' + event.filename);
            autoFill(event);
        } else {
            autoEmpty();
        }

        events.forEach(function (event) {
            if (parseInt(event.id) === parseInt(id_event))
                id_categ = parseInt(event.id_categ);
        });

        $('div[name=select_event]').css('display', 'none');
        $('div[name=create_prestashop_category]').css('display', 'none');
        $('div[name=categ_already_created]').css('display', 'none');
        $('div[name=toggle_active]').css('display', 'none');
        $('div[name=categ_not_created]').css('display', 'none');

        if (id_categ > 0) {
            $('div[name=categ_already_created]').css('display', 'block');
            $('div[name=toggle_active]').css('display', 'block');
        } else {
            if (id_event > 0) {
                $('div[name=create_prestashop_category]').css('display', 'block');
                $('div[name=categ_not_created]').css('display', 'block');
            } else {
                $('div[name=select_event]').css('display', 'block');
            }
        }

        changeEventSession(id_event);
    });

    $('button[name=draft]').click(function () {
//        e.preventDefault();

        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0)
            draftEvent(id_event);
        else
            setMessage('alertSubmit', "Veuillez sélectionnez un évènement.", 'error');
    });

    $('button[name=validate]').click(function () {
//        e.preventDefault();

        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0)
            validateEvent(id_event);
        else
            setMessage('alertSubmit', "Veuillez sélectionnez un évènement.", 'error');
    });

    $('button[name=close]').click(function () {
//        e.preventDefault();

        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0)
            closeEvent(id_event);
        else
            setMessage('alertSubmit', "Veuillez sélectionnez un évènement.", 'error');
    });

    $('div[name=create_prestashop_category]').click(function () {
        var id_event = $('select[name=id_event] > option:selected').val();
        var label_event = $('select[name=id_event] > option:selected').text();
        $('p#categ_already_created').css('display', 'none');
        $('p#select_event').css('display', 'none');

        if (id_event > 0) {
            var stop = false;
            events.forEach(function (event) {
                if (parseInt(event.id) === parseInt(id_event) && parseInt(event.id_categ) > 0) {
                    $('p#categ_already_created').css('display', 'inline');
                    stop = true;
                }
            });
            if (stop === false)
                createPrestashopCategory(id_event, label_event);
        } else {
            $('p#select_event').css('display', 'inline');
        }
    });

    $('div[name=toggle_active]').click(function () {
        var id_event = parseInt($('select[name=id_event] > option:selected').val());
        if (id_event > 0) {
            events.forEach(function (event) {
                if (parseInt(event.id) === id_event) {
                    if (parseInt(event.id_categ) > 0) {
                        toggleActiveCategory(parseInt(event.id_categ));
                    } else {
                        alert("Veuillez importer la catégorie sur prestashop avant de changer son status.");
                    }
                }
            });
        } else {
            alert("Veuillez sélectionner un évènement avant de changer son status.");
        }

    });
}

function autoFill(event) {
    $('input[name=label]').val(event.label);
    $("#description").val(event.description);
    $('input[name=date_start]').val(formatDate(event.date_start));
    $('input[name=time_start]').val(formatTime(event.date_start));
    $('input[name=date_end]').val(formatDate(event.date_end));
    $('input[name=time_end]').val(formatTime(event.date_end));
//    setImage(event.id);   
}

function autoEmpty() {
    $('input[name=label]').val('');
    $("#description").val('');
    $('input[name=date_start]').val(formatDate(''));
    $('input[name=time_start]').val(formatTime(''));
    $('input[name=date_end]').val(formatDate(''));
    $('input[name=time_end]').val(formatTime(''));
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