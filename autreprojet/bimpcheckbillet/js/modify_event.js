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
//                    $('img#img_display').attr('src', +'../img/event/' + filename);
                }
            } else if (out.events.length === 0) {
                alert("Aucun évènement n'a été créée, vous allez être redirigé vers la page de création des évènements.");
                window.location.replace('../view/create_event.php');
            } else {
                setMessage('alertSubmit', "Erreur 1286.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}

//function setImage(id_event) {
//
//    $.ajax({
//        type: "POST",
//        url: "../interface.php",
//        data: {
//            folder: 'img/event/',
//            name: id_event,
//            action: 'get_image'
//        },
//        error: function () {
//            setMessage('alertSubmit', 'Erreur serveur 1844.', 'error');
//        },
//        success: function (rowOut) {
//            var out = JSON.parse(rowOut);
//            $("#img_display").attr('src', 'data:image/png;base64,' + atob(out.src));
//        }
//    });
//}

function modifyEventPrestashop(id_event, label, description, place, date_start, time_start, date_end, time_end, id_categ) {

    $.ajax({
        type: "POST",
        url: URL_PRESTASHOP,
        data: {
            id_event: id_event,
            label: label,
            description: description,
            place: place,
            date_start: date_start,
            time_start: time_start,
            date_end: date_end,
            time_end: time_end,
            id_categ: id_categ,
            action: 'updatecateg'
        },
        beforeSend: function () {
            $('*').css('cursor', 'wait');
        },
        complete: function () {
            $('*').css('cursor', 'auto');
        },
        error: function () {
            setMessage('alertBottom', 'Erreur serveur 5720.', 'error');
        },
        success: function (rowOut) {
            $('*').css('cursor', 'auto');
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.is_ok) > 0) {
                modifyEvent(id_event, label, description, place, date_start, time_start, date_end, time_end)
            } else {
                setMessage('alertBottom', 'Erreur serveur 6871.', 'error');
            }
        }
    });
}

function modifyEvent(id_event, label, description, place, date_start, time_start, date_end, time_end) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            label: label,
            description: description,
            place: place,
            date_start: date_start,
            time_start: time_start,
            date_end: date_end,
            time_end: time_end,
            action: 'modify_event'
        },
        error: function () {
            setMessage('alertBottom', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertBottom', "Evènement modifié.", 'msg');
            } else {
                setMessage('alertBottom', 'Erreur serveur 3584.', 'error');
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
            setMessage('alertBottom', 'Erreur serveur 7886.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertBottom', "Evènement définit comme brouillon.", 'msg');
            } else {
                setMessage('alertBottom', 'Erreur serveur 2354.', 'error');
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
            setMessage('alertBottom', 'Erreur serveur 3486.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertBottom', "Evènement validé.", 'msg');
            } else {
                setMessage('alertBottom', 'Erreur serveur 2484.', 'error');
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
            setMessage('alertBottom', 'Erreur serveur 3486.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertBottom', "Evènement fermé.", 'msg');
            } else {
                setMessage('alertBottom', 'Erreur serveur 2484.', 'error');
            }
        }
    });
}

function createPrestashopCategory(id_event, label_event, id_categ_parent, description, place) {

    $.ajax({
        type: "POST",
        url: URL_PRESTASHOP,
        data: {
            id_event: id_event,
            label: label_event,
            description: description,
            place: place,
            id_categ_parent: id_categ_parent,
            action: 'createPrestashopCategory'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 2584.', 'error');
        },
        beforeSend: function () {
            $('*').css('cursor', 'wait');
        },
        complete: function () {
            $('*').css('cursor', 'auto');
        },
        success: function (rowOut) {
            $('*').css('cursor', 'auto');
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
                alert("La catégorie a été créée dans prestashop, la page va se recharger.");
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

function deletePrestashopEvent(event) {

    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            id_event: event.id_categ,
            action: 'deleteCategAndItsProduct'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 7826.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.is_ok) {
                deleteEvent(event);
            } else {
                setMessage('alertSubmit', "Erreur inconnue 4821.", 'error');
            }
        }
    });
}

function deleteEvent(event) { // check server

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: event.id,
            action: 'delete_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3549.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                alert("La catégorie a été supprimée, la page va se recharger.");
                location.reload();
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3482.', 'error');
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
            var event = getEventById(id_event);
            if (parseInt(event.id_categ) > 0) {
                modifyEventPrestashop($('select[name=id_event] > option:selected').val(),
                        $('input[name=label]').val(),
                        tinymce.get('description').getContent(),
                        tinymce.get('place').getContent(),
                        $('input[name=date_start]').val(),
                        $('input[name=time_start]').val(),
                        $('input[name=date_end]').val(),
                        $('input[name=time_end]').val(),
                        event.id_categ);
            } else {
                modifyEvent($('select[name=id_event] > option:selected').val(),
                        $('input[name=label]').val(),
                        tinymce.get('description').getContent(),
                        tinymce.get('place').getContent(),
                        $('input[name=date_start]').val(),
                        $('input[name=time_start]').val(),
                        $('input[name=date_end]').val(),
                        $('input[name=time_end]').val());
            }
        } else {
            setMessage('alertSubmit', 'Veuillez sélectionner un évènement.', 'error');
        }
    });

    $('select[name=id_event]').change(function () {
        var id_categ;
        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0) {
            var event = getEventById(id_event);
            $('img#img_display').attr('src', '../img/event/' + event.filename);
            autoFill(event);
        } else {
            $('img#img_display').attr('src', '');
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
            setMessage('alertBottom', "Veuillez sélectionnez un évènement.", 'error');
    });

    $('button[name=validate]').click(function () {
//        e.preventDefault();

        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0)
            validateEvent(id_event);
        else
            setMessage('alertBottom', "Veuillez sélectionnez un évènement.", 'error');
    });

    $('button[name=close]').click(function () {
//        e.preventDefault();

        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0)
            closeEvent(id_event);
        else
            setMessage('alertBottom', "Veuillez sélectionnez un évènement.", 'error');
    });

    $('div[name=create_prestashop_category]').click(function () {
        var place;
        var description;
        var id_categ_parent;
        var id_event = $('select[name=id_event] > option:selected').val();
        var label_event = $('select[name=id_event] > option:selected').text();
        $('p#categ_already_created').css('display', 'none');
        $('p#select_event').css('display', 'none');

        if (id_event > 0) {
            var stop = false;
            events.forEach(function (event) {
                if (!stop && parseInt(event.id) === parseInt(id_event)) {
                    id_categ_parent = event.id_categ_parent;
                    place = event.place;
                    description = event.description;
                    if (parseInt(event.id_categ) > 0) {
                        $('p#categ_already_created').css('display', 'inline');
                        stop = true;
                    }
                }
            });
            if (stop === false)
                createPrestashopCategory(id_event, label_event, id_categ_parent, description, place);
            else
                alert("Impossible de retrouver les information concernant cet évènement");
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

    $('button[name=delete]').click(function () {
        var id_event = $('select[name=id_event] > option:selected').val();
        if (id_event > 0) {
            if (confirm("Vous êtes sur le point de supprimer un évènement,\n\
les tariffs et les tickets (pdf inclus) qui lui sont rattachés seront également supprimés, poursuivre ?")) {
                var event = getEventById(id_event);
                if (parseInt(event.id_categ) > 0)
                    deletePrestashopEvent(event);
                else
                    deleteEvent(event);
            }
        } else
            setMessage('alertBottom', "Veuillez sélectionnez un évènement.", 'error');
    });
}

function autoFill(event) {
    $('input[name=label]').val(event.label);
    $(tinymce.get('description').getBody()).html(event.description);
    $(tinymce.get('place').getBody()).html(event.place);
//    $("#description").val(event.description);
//    $("#place").val(event.place);
    $('input[name=date_start]').val(formatDate(event.date_start));
    $('input[name=time_start]').val(formatTime(event.date_start));
    $('input[name=date_end]').val(formatDate(event.date_end));
    $('input[name=time_end]').val(formatTime(event.date_end));
//    setImage(event.id);   
}

function autoEmpty() {
    $('input[name=label]').val('');
    $(tinymce.get('description').getBody()).html('');
    $(tinymce.get('place').getBody()).html('');
    $("#description").val('');
    $("#place").val('');
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