var URL_PRESTASHOP = URL_PRESTA + '/modules/zoomdici/ajax.php';

var tariffs;

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
                        $('select[name=id_event]').trigger('change');
                    }
                }
            } else {
                setMessage('alertSubmit', "Créer un évènement avant de définir un tarif.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}

function getTariffsForEvent(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'get_tariffs_for_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.tariffs !== -1) {
                tariffs = out.tariffs;
                $('select[name=tariff] > option').remove();
                $('select[name=tariff]').append('<option value="">Sélectionnez un tariff</option>');
                out.tariffs.forEach(function (tariff) {
                    $('select[name=tariff]').append(
                            '<option value=' + tariff.id + '>' + tariff.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
            } else {
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'error');
            }
        }
    });
}

function modifyTariff(id_tariff, label, price, number_place, require_names, date_start, time_start, date_end, time_end,
        type_extra_1, type_extra_2, type_extra_3, type_extra_4, type_extra_5, type_extra_6,
        name_extra_1, name_extra_2, name_extra_3, name_extra_4, name_extra_5, name_extra_6,
        require_extra_1, require_extra_2, require_extra_3, require_extra_4, require_extra_5, require_extra_6) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            label: label,
            price: price,
            number_place: number_place,
            require_names: require_names,
            date_start: date_start,
            time_start: time_start,
            date_end: date_end,
            time_end: time_end,
            type_extra_1: type_extra_1,
            type_extra_2: type_extra_2,
            type_extra_3: type_extra_3,
            type_extra_4: type_extra_4,
            type_extra_5: type_extra_5,
            type_extra_6: type_extra_6,
            name_extra_1: name_extra_1,
            name_extra_2: name_extra_2,
            name_extra_3: name_extra_3,
            name_extra_4: name_extra_4,
            name_extra_5: name_extra_5,
            name_extra_6: name_extra_6,
            require_extra_1: require_extra_1,
            require_extra_2: require_extra_2,
            require_extra_3: require_extra_3,
            require_extra_4: require_extra_4,
            require_extra_5: require_extra_5,
            require_extra_6: require_extra_6,
            action: 'modify_tariff'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                alert("Tarif modifié");
                location.reload();
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3814.', 'error');
            }
        }
    });
}

function getCategEvent(id_tariff) {
    $.ajax({
        type: 'POST',
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            action: 'get_event_by_tariff_id'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 2564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.event !== undefined) {
                if (parseInt(out.event.id_categ) > 0)
                    createPrestashopProduct(id_tariff, out.event.id_categ, out.image_name);
                else
                    alert("Merci d'importer l'évènement associé à ce tarif avant d'importer le tarif");
            } else {
                setMessage('alertSubmit', 'Erreur serveur 7561.', 'error');
            }
        }
    });
}

function createPrestashopProduct(id_tariff, id_categ_extern, image_name) {

    var tariff;

    tariffs.forEach(function (tmp_tariff) {
        if (tmp_tariff.id === parseInt(id_tariff))
            tariff = tmp_tariff;
    });

    var id_event = parseInt($('select[name=id_event]').val());

    if (tariff.id_prod_extern === 0) {
        $.ajax({
            type: 'POST',
            url: URL_PRESTASHOP,
            data: {
                label: tariff.label,
                price: tariff.price,
                id_event: id_event,
                id_tariff: id_tariff,
                number_place: tariff.number_place,
                id_categ_extern: id_categ_extern,
                image_name: image_name,
                action: 'createPrestashopProduct'
            },
            error: function () {
                setMessage('alertSubmit', 'Erreur serveur 1894.', 'error');
            },
            success: function (rowOut) {
                var out = JSON.parse(rowOut);
                if (out.errors.length !== 0) {
                    printErrors(out.errors, 'alertSubmit');
                } else if (parseInt(out.id_inserted) > 0) {
                    addIdProdExtern(tariff.id, out.id_inserted);
                } else {
                    setMessage('alertSubmit', "Erreur inconnue.", 'error');
                }
            }
        });
    } else {
        alert("Le produit prestashop a déjà été créer");
    }
}

function addIdProdExtern(id_tariff, id_prod_extern) {


    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            id_prod_extern: id_prod_extern,
            action: 'set_id_prod_extern'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 2586.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return === 1) {
                $('p[name=product_already_created]').css('display', 'inline');
                $('div[name=create_prestashop_product]').hide();
                $('p[name=select_tariff]').css('display', 'none');
                alert('Produit créée');
            } else {
                setMessage('alertSubmit', "Erreur serveur 1873.", 'error');
            }
        }
    }
    );
}

/**
 * Ready
 */
$(document).ready(function () {
    $('input[name=date_start]').datepicker({dateFormat: 'dd/mm/yy'})
    $('input[name=date_end]').datepicker({dateFormat: 'dd/mm/yy'})
    getEvents();
}
);
/**
 * Function
 */

function initEvents() {

    $('select[name=id_event]').change(function () {
        var id_event = $(this).val();
        if (id_event > 0)
            getTariffsForEvent(id_event);
    });

    $('div[name=create_prestashop_product]').click(function (e) {
        var id_tariff = $('select[name=tariff] > option:selected').val();
        if (!id_tariff > 0) {
            alert('Veuillez séléctionner un tariff avant de créer un produit dans prestashop');
        } else {
            getCategEvent(id_tariff);
        }
    });

    $('button[name=modify]').click(function (e) {
        e.preventDefault();
        modifyTariff(
                $('select[name=tariff]').val(),
                $('input[name=label]').val(),
                $('input[name=price]').val(),
                $('input[name=number_place]').val(),
                $('input[name=require_names]:checked').val(),
                $('input[name=date_start]').val(),
                $('input[name=time_start]').val(),
                $('input[name=date_end]').val(),
                $('input[name=time_end]').val(),
                $('select[name=type_extra_1] > option:selected').val(),
                $('select[name=type_extra_2] > option:selected').val(),
                $('select[name=type_extra_3] > option:selected').val(),
                $('select[name=type_extra_4] > option:selected').val(),
                $('select[name=type_extra_5] > option:selected').val(),
                $('select[name=type_extra_6] > option:selected').val(),
                $('input[name=name_extra_1]').val(),
                $('input[name=name_extra_2]').val(),
                $('input[name=name_extra_3]').val(),
                $('input[name=name_extra_4]').val(),
                $('input[name=name_extra_5]').val(),
                $('input[name=name_extra_6]').val(),
                $('input[name=require_extra_1]:checked').val(),
                $('input[name=require_extra_2]:checked').val(),
                $('input[name=require_extra_3]:checked').val(),
                $('input[name=require_extra_4]:checked').val(),
                $('input[name=require_extra_5]:checked').val(),
                $('input[name=require_extra_6]:checked').val());
    });

    $('select[name=tariff]').change(function (e) {
        var id_prod_extern;
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        if (id_tariff > 0)
            autoFill(id_tariff);
        
        $('img#img_display').attr('src', '');

        tariffs.forEach(function (tariff) {
            if (tariff.id === id_tariff) {
                id_prod_extern = tariff.id_prod_extern;
                $('img#img_display').attr('src', URL_CHECK + '/img/event/' + tariff.filename);
            }
        });

        if (id_prod_extern > 0) {
            $('div[name=create_prestashop_product]').hide();
            $('p[name=product_already_created]').css('display', 'inline');
            $('p[name=select_tariff]').css('display', 'none');
        } else {
            $('p[name=product_already_created]').css('display', 'none');
            if (id_tariff > 0) {
                $('div[name=create_prestashop_product]').show();
                $('p[name=select_tariff]').css('display', 'none');
            } else {
                $('p[name=select_tariff]').css('display', 'inline');
                $('div[name=create_prestashop_product]').hide();
            }
        }
    });
}

function autoFill(id_tariff) {
    var tariff = getTariffById(id_tariff);
    $('input[name=label]').val(tariff.label);
    $('input[name=date_start]').val(formatDate(tariff.date_start));
    $('input[name=time_start]').val(formatTime(tariff.date_start));
    $('input[name=date_end]').val(formatDate(tariff.date_end));
    $('input[name=time_end]').val(formatTime(tariff.date_end));
    $('input[name=price]').val(tariff.price);
    $('input[name=number_place]').val(tariff.number_place);

    if (tariff.require_names === 0) {
        $('input[name=require_names][value=0]').prop('checked', true);
        $('input[name=require_names][value=0]').closest('.btn').button('toggle');
        $('input[name=require_names][value=1]').prop('checked', false);
    } else {
        $('input[name=require_names][value=1]').prop('checked', true);
        $('input[name=require_names][value=1]').closest('.btn').button('toggle');
        $('input[name=require_names][value=0]').prop('checked', false);
    }

    fillExtra(tariff, 6);

    $('.chosen-select').trigger('chosen:updated');

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

/**
 * From yyyy-mm-dd hh:mm:ss to dd/mm/yyyy
 * @param {type} date
 */
function formatDate(date) {
    var reg_exp = /(\d+)-(\d+)-(\d+)\s\d+:\d+:\d+/;
    try {
        return date.replace(reg_exp, '$3/$2/$1');
    } catch (e) {
        return '';
    }
}

/**
 * From yyyy-mm-dd hh:mm:ss to hh:mm
 * @param {type} date
 */
function formatTime(date) {
    var reg_exp = /\d+-\d+-\d+\s(\d+):(\d+):\d+/;
    try {
        return date.replace(reg_exp, '$1:$2');
    } catch (e) {
        return '';
    }
}

function getTariffById(id_tariff) {
    var out;
    tariffs.forEach(function (tariff) {
        if (tariff.id === parseInt(id_tariff))
            out = tariff;
    });
    return out;
}

function fillExtra(tariff, max) {
    for (var i = 1; i < max; i++) {
        var name_type = 'type_extra_' + i;
        var value_type = tariff[name_type];
        var name_name = 'name_extra_' + i;
        var value_name = tariff[name_name];
        if (value_type !== 0) {
            $('select[name=' + name_type + '] > option[value=' + value_type + ']').prop('selected', true);
            $('input[name=' + name_name + ']').val(value_name);
        }

        var require_extra = 'require_extra_' + i;
        var value_require_extra = tariff[require_extra];
        if (value_require_extra === 1) {
            $('input[name=require_extra_' + i + '][value=1]').prop('checked', true);
            $('input[name=require_extra_' + i + '][value=1]').closest('.btn').button('toggle');
        } else {
            $('input[name=require_extra_' + i + '][value=0]').prop('checked', true);
            $('input[name=require_extra_' + i + '][value=0]').closest('.btn').button('toggle');
        }
    }
}
