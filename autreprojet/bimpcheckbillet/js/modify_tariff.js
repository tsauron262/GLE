var URL_PRESTASHOP = URL_PRESTA + '/modules/zoomdici/ajax.php';

var tariffs;
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
                $("select[name=id_event]").chosen({
                    placeholder_text_single: 'Evènement',
                    no_results_text: 'Pas de résultat'});
                $('select[name=id_event]').change(function () {
                    changeEventSession($('select[name=id_event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=id_event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=id_event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $('select[name=id_event]').trigger('change');
                    }
                }
                $(".chosen-select").trigger("chosen:updated");
            } else if (out.events.length === 0) {
                alert("Aucun évènement n'a été créée, vous allez être redirigé vers la page de création des évènements.");
                window.location.replace('../view/create_event.php');
            } else {
                setMessage('alertSubmit', "Erreur 3154.", 'error');
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
            action: 'get_tariffs_for_event_with_attribute'
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
                $('select[name=tariff]').append('<option value="">Sélectionnez un tariff</option>');
                out.tariffs.forEach(function (tariff) {
                    $('select[name=tariff]').append(
                            '<option value=' + tariff.id + '>' + tariff.label + '</option>');
                });
            } else {
                $('select[name=tariff]').append('<option value="">Sélectionnez un tariff</option>');
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'error');
            }
            $('select[name=tariff]').trigger('chosen:updated');
        }
    });
}

function modifyTariff(id_tariff, label, price, number_place, require_names, date_stop_sale, time_stop_sale, date_start, time_start, date_end, time_end, email_text,
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
            date_stop_sale: date_stop_sale,
            time_stop_sale: time_stop_sale,
            date_start: date_start,
            time_start: time_start,
            date_end: date_end,
            time_end: time_end,
            email_text: email_text,
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
                alert("Tarif modifié, la page va se recharger.");
                location.reload();
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3814.', 'error');
            }
        }
    });
}

function modifyTariffPrestashop(id_tariff, label, price, number_place, require_names, date_stop_sale, time_stop_sale, date_start, time_start, date_end, time_end, email_text,
        type_extra_1, type_extra_2, type_extra_3, type_extra_4, type_extra_5, type_extra_6,
        name_extra_1, name_extra_2, name_extra_3, name_extra_4, name_extra_5, name_extra_6,
        require_extra_1, require_extra_2, require_extra_3, require_extra_4, require_extra_5, require_extra_6, id_prod_extern) {

    $.ajax({
        type: "POST",
        url: URL_PRESTASHOP,
        data: {
            id_prod_extern: id_prod_extern,
            email_text: email_text,
            label: label,
            price: price,
            action: 'updateProduct'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 8943.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.is_ok) > 0) {
                modifyTariff(id_tariff, label, price, number_place, require_names, date_stop_sale, time_stop_sale, date_start, time_start, date_end, time_end, email_text,
                        type_extra_1, type_extra_2, type_extra_3, type_extra_4, type_extra_5, type_extra_6,
                        name_extra_1, name_extra_2, name_extra_3, name_extra_4, name_extra_5, name_extra_6,
                        require_extra_1, require_extra_2, require_extra_3, require_extra_4, require_extra_5, require_extra_6);
            } else {
                setMessage('alertSubmit', 'Erreur serveur 9736.', 'error');
            }
        }
    });
}

function getCategEvent(id_tariff, id_tax) {
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
                    createPrestashopProduct(id_tariff, out.event.id_categ, out.image_name, id_tax);
                else
                    alert("Merci d'importer l'évènement associé à ce tarif avant d'importer le tarif");
            } else {
                setMessage('alertSubmit', 'Erreur serveur 7561.', 'error');
            }
        }
    });
}

function createPrestashopProduct(id_tariff, id_categ_extern, image_name, id_tax) {

    var tariff;
    var event_for_tariff;
    var date_start;
    var date_end;
    var place = '';
    var address;
    var line;
    var tmp_string;


    tariffs.forEach(function (tmp_tariff) {
        if (tmp_tariff.id === parseInt(id_tariff))
            tariff = tmp_tariff;
    });

    events.forEach(function (tmp_event) {
        if (parseInt(tmp_event.id) === parseInt(tariff.fk_event))
            event_for_tariff = tmp_event;
    });


    if (tariff.date_start === null && tariff.date_end === null) {
        date_start = event_for_tariff.date_start;
        date_end = event_for_tariff.date_end;
    } else {
        date_start = tariff.date_start;
        date_end = tariff.date_end;
    }


    var full_adresse_lines = event_for_tariff.place.match(/<p>(.*?)<\/p>/g).map(function (val) {
        return val.replace(/<\/?b>/g, '');
    });

    for (var i in full_adresse_lines) {
        line = full_adresse_lines[i].replace(/<\/p>/ig, '===');
        tmp_string = removeHtmlTags(line);
        if (parseInt(i) + 1 === full_adresse_lines.length)
            address = tmp_string.replace(/===/ig, "<br/>");
        else
            place += tmp_string.replace(/===/ig, "<br/>");
    }

    if (tariff.id_prod_extern === 0) {
        $.ajax({
            type: 'POST',
            url: URL_PRESTASHOP,
            data: {
                label: tariff.label,
                price: tariff.price,
                date_start: date_start,
                date_end: date_end,
                id_event: event_for_tariff.id,
                id_tariff: id_tariff,
                number_place: tariff.number_place,
                id_categ_extern: id_categ_extern,
                image_name: image_name,
                id_tax: id_tax,
                date_stop_sale: tariff.date_stop_sale,
                email_text: tariff.email_text,
                place: place,
                address: address,
                action: 'createPrestashopProduct'
            },
            beforeSend: function () {
                $('*').css('cursor', 'wait');
            },
            complete: function () {
                $('*').css('cursor', 'auto');
            },
            error: function () {
                setMessage('alertSubmit', 'Erreur serveur 1894.', 'error');
            },
            success: function (rowOut) {
                $('*').css('cursor', 'auto');
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
                alert('Produit créée, la page va se recharger.');
                location.reload();
            } else {
                setMessage('alertSubmit', "Erreur serveur 1873.", 'error');
            }
        }
    });
}

function toggleActiveProduct(id_prod_extern) {
    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            id_prod_extern: id_prod_extern,
            action: 'toggleProductActive'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 8721.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.toggled) === 1) {
                if (out.active == true)
                    alert("Ce produit est maintenant activé.");
                else
                    alert("Ce produit est maintenant désactivé.");
            } else {
                setMessage('alertSubmit', "Erreur inconnue 2538.", 'error');
            }
        }
    });
}

function getAttributes(id_prod_extern) {
    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            id_prod_extern: id_prod_extern,
            action: 'getAttributes'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3645.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.attributes != undefined) {
                console.log(out.attributes);
            } else {
                setMessage('alertSubmit', "Erreur inconnue 2948.", 'error');
            }
        }
    });
}

function deletePrestashopTariff(id_tariff, id_prod_extern) {
    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            id_prod_extern: id_prod_extern,
            action: 'deleteProduct'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6482.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.is_ok) {
                deleteTariff(id_tariff);
            } else {
                setMessage('alertSubmit', "Erreur inconnue 8431.", 'error');
            }
        }
    });
}

function deleteTariff(id_tariff) {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            action: 'delete_tariff'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6483.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.code_return) === 1) {
                alert('Produit supprimé, la page va se recharger.');
                location.reload();
            } else {
                setMessage('alertSubmit', "Erreur serveur 3491.", 'error');
            }
        }
    });
}


/**
 * Ready
 */
$(document).ready(function () {
    tinymce.init({selector: 'textarea'});

    $(".chosen-select").chosen();
    $('input[name=date_stop_sale]').datepicker({dateFormat: 'dd/mm/yy'})
    $('input[name=date_start]').datepicker({dateFormat: 'dd/mm/yy'})
    $('input[name=date_end]').datepicker({dateFormat: 'dd/mm/yy'})
    $("select[name=tariff]").chosen({
        placeholder_text_single: 'Tarif',
        no_results_text: 'Pas de résultat'});
    getEvents();
});


/**
 * Function
 */

function initEvents() {

    $('select[name=id_event]').change(function () {
        $('select[name=tariff]').empty();
        var id_event = $(this).val();
        if (id_event > 0)
            getTariffsForEvent(id_event);
    });

    $('div[name=create_prestashop_product]').click(function () {
        $('div#div_tax').css('display', 'block');
        var element_div_tax = document.getElementById("div_tax");
        element_div_tax.scrollIntoView({behavior: 'smooth'});
    });

    $('div#conf_create_prestashop_category').click(function () {
        var id_tariff = $('select[name=tariff] > option:selected').val();
        if (!id_tariff > 0) {
            alert('Veuillez séléctionner un tariff avant de créer un produit dans prestashop');
        } else {
            var id_tax = parseInt($('#container_tax').find('label.active').find('input').first().val());
            getCategEvent(id_tariff, id_tax);
        }
    });


    $('button[name=modify]').click(function (e) {
        e.preventDefault();
        var id_tariff = parseInt($('select[name=tariff]').val());
        if (id_tariff > 0) {

            var tariff = getTariffById(id_tariff);

            if (parseInt(tariff.id_prod_extern) > 0) {
                modifyTariffPrestashop(
                        id_tariff,
                        $('input[name=label]').val(),
                        $('input[name=price]').val(),
                        $('input[name=number_place]').val(),
                        $('input[name=require_names]:checked').val(),
                        $('input[name=date_stop_sale]').val(),
                        $('input[name=time_stop_sale]').val(),
                        $('input[name=date_start]').val(),
                        $('input[name=time_start]').val(),
                        $('input[name=date_end]').val(),
                        $('input[name=time_end]').val(),
                        tinymce.get('email_text').getContent(),
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
                        $('input[name=require_extra_6]:checked').val(),
                        tariff.id_prod_extern);
            } else {
                modifyTariff(
                        id_tariff,
                        $('input[name=label]').val(),
                        $('input[name=price]').val(),
                        $('input[name=number_place]').val(),
                        $('input[name=require_names]:checked').val(),
                        $('input[name=date_stop_sale]').val(),
                        $('input[name=time_stop_sale]').val(),
                        $('input[name=date_start]').val(),
                        $('input[name=time_start]').val(),
                        $('input[name=date_end]').val(),
                        $('input[name=time_end]').val(),
                        tinymce.get('email_text').getContent(),
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
            }
        }
    });

    $('select[name=tariff]').change(function (e) {
        $('div#div_tax').css('display', 'none');

        var id_prod_extern;
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        if (id_tariff > 0) {
            autoFill(id_tariff);
        } else {
            alert("Ce tariff n'est pas valide.");
        }
        $('img#img_display').attr('src', '');

        tariffs.forEach(function (tariff) {
            if (tariff.id === id_tariff) {
                id_prod_extern = tariff.id_prod_extern;
                $('img#img_display').attr('src', '../img/event/' + tariff.filename);
                if (tariff.filename_custom !== null)
                    $('img#img_custom_display').attr('src', '../img/tariff_custom/' + tariff.filename_custom);
            }
        });


        $('div[name=select_tariff]').css('display', 'none');
        $('div[name=create_prestashop_product]').css('display', 'none');
        $('div[name=product_already_created]').css('display', 'none');
        $('div[name=toggle_active]').css('display', 'none');
        $('div[name=product_not_created]').css('display', 'none');

        if (id_prod_extern > 0) {
            $('div[name=product_already_created]').css('display', 'block');
            $('div[name=toggle_active]').css('display', 'block');
        } else {
            if (id_tariff > 0) {
                $('div[name=create_prestashop_product]').css('display', 'block');
                $('div[name=product_not_created]').css('display', 'block');
            } else {
                $('div[name=select_tariff]').css('display', 'block');
            }
        }
    }
    );
    $('div[name=toggle_active]').click(function () {
        var id_prod_extern;
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        if (id_tariff > 0) {
            tariffs.forEach(function (tariff) {
                if (tariff.id === id_tariff) {
                    id_prod_extern = parseInt(tariff.id_prod_extern);
                }
            });
            if (id_prod_extern > 0)
                toggleActiveProduct(id_prod_extern);
            else
                alert("Veillez importer le tariff dans prestashop avant de modifier son status.");
        } else {
            alert("Veuillez sélectionner un produit avant de changer un status.");
        }
    });

    $('div[name=get_attributes]').click(function () {
        var id_prod_extern;
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        if (id_tariff > 0) {
            tariffs.forEach(function (tariff) {
                if (tariff.id === id_tariff) {
                    id_prod_extern = parseInt(tariff.id_prod_extern);
                }
            });
            if (id_prod_extern > 0)
                getCombinatoons(id_prod_extern);
            else
                alert("Veillez importer le tariff dans prestashop avant de modifier son status.");
        } else {
            alert("Veuillez sélectionner un produit avant de changer un status.");
        }
    });

    $('div[name=delete]').click(function () {
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        var tariff;
        if (id_tariff > 0) {
            tariffs.forEach(function (tmp_tariff) {
                if (tmp_tariff.id === id_tariff)
                    tariff = tmp_tariff;
            });
            if (parseInt(tariff.id_prod_extern) > 0)
                deletePrestashopTariff(tariff.id, tariff.id_prod_extern);
            else
                deleteTariff(tariff.id);
        } else {
            alert("Veuillez sélectionner un produit avant de changer un status.");
        }
    }
    );
}

function autoFill(id_tariff) {
    var tariff = getTariffById(id_tariff);
    $('input[name=label]').val(tariff.label);
    $('input[name=date_stop_sale]').val(formatDate(tariff.date_stop_sale));
    $('input[name=time_stop_sale]').val(formatTime(tariff.date_stop_sale));
    $('input[name=date_start]').val(formatDate(tariff.date_start));
    $('input[name=time_start]').val(formatTime(tariff.date_start));
    $('input[name=date_end]').val(formatDate(tariff.date_end));
    $('input[name=time_end]').val(formatTime(tariff.date_end));
    $('input[name=price]').val(tariff.price);
    $('input[name=number_place]').val(tariff.number_place);

    var element_email_text = tinymce.get('email_text');
    element_email_text.setContent(tariff.email_text);

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

function removeHtmlTags(inputString) {
    return inputString.replace(/(<([^>]+)>)/ig, '');
}
