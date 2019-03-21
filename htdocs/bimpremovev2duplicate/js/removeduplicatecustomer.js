/*
 * Global variable
 */

var doing_request = false;

/**
 * Ajax functions
 */

/**
 * 
 * @param {type} message message to display
 * @param {type} code 'mesgs' by default, 'warnings', 'errors'
 * @return {undefined}
 */
function displayMessage(message, code = 'mesgs') {
    if (code != 'mesgs' && code != 'warnings' && code != 'errors')
        alert("Le type de l'erreur est inconnu, la voici : " + code);
    if (message == '')
        alert("Message d'erreur vide");

    alert(message);
}


function getAllDuplicate(limit, s_min, s_name, s_email, s_address, s_zip, s_town, s_phone, s_siret, commercial, details) {

    if (limit <= 0) {
        displayMessage("Le nombre de doublon doit être supérieur à zéro.", 'errors');
        return -2;
    }

    doing_request = true;
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremovev2duplicate/interface.php",
        data: {
            limit: limit,
            s_min: s_min,
            s_name: s_name,
            s_email: s_email,
            s_address: s_address,
            s_zip: s_zip,
            s_town: s_town,
            s_phone: s_phone,
            s_siret: s_siret,
            commercial: commercial,
            details: details,
            action: 'get_all_duplicate'
        },
        beforeSend: function () {
            $('i#spinner').css('display', 'block');
            $('input#display_duplicates').css('display', 'none');
            $('input#init_duplicate').css('display', 'none');
        },
        error: function () {
            doing_request = false;
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            $('input#init_duplicate').css('display', 'block');
            alert("Erreur PHP 2654");
        },
        success: function (out) {
            doing_request = false;
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            $('input#init_duplicate').css('display', 'block');
            $('table#customer > tbody > tr[key_group]').remove();

            if (out) {
                try {
                    var parsed_out = JSON.parse(out);
                    var array_of_duplicate = parsed_out.duplicates;
                    for (var i in array_of_duplicate) {
                        var duplicates = array_of_duplicate[i];

                        // Get first key
                        var first_index;
                        for (var index in duplicates) {
                            first_index = index;
                            break;
                        }
                        // Get last key and check if a siret is set
                        var last_index;
                        var a_siret_is_set = 0;
                        for (var index in duplicates) {
                            last_index = index;
                            if (duplicates[index].siret != '')
                                a_siret_is_set = 1;
                        }

                        var nb_line = Object.keys(duplicates).length;
                        for (var index in duplicates) {
                            var duplicate = duplicates[index];
                            var key_group = i;
                            if (index == last_index)
                                var is_last = true;
                            else
                                var is_last = false;
                            if (index == first_index)
                                var is_first = true;
                            else
                                var is_first = false;
                            addLine(duplicate, key_group, is_last, is_first, nb_line, a_siret_is_set);
                        }
                    }


                    // Add button and his event
                    if ($('input#merge_duplicates').length == 0)
                        $('div#id-right').append('<br/><input type="submit" class="butAction" id="merge_duplicates" value="Fusionner tous les doublons">');
                    iniEventAfterDisplayDuplicate();

                    // Display number group doublon
                    var nb_row = parsed_out.nb_row;
                    $('div#db_duplicate').text(nb_row);

                    // Display time exec
                    var time_exec = parseInt(parsed_out.time_exec);
                    var date = new Date(null);
                    date.setSeconds(time_exec);
                    var time_string = date.toISOString().substr(11, 8);
                    $('div#time_exec').text(time_string);

                } catch (e) {
                    displayMessage("Erreur:" + e, 'errors');
                }
            } else {
                displayMessage("Aucune réponse du serveur", 'errors');
            }
        }
    });

    displayProgress(limit);

}

var progress = 0;

function displayProgress(limit) {
    setTimeout(function () {
        $.ajax({
            type: "POST",
            url: DOL_URL_ROOT + "/bimpremovev2duplicate/interface.php",
            data: {
                action: 'get_progress'
            },
            error: function () {
                alert("Erreur PHP 6464");
            },
            success: function (rowOut) {
                progress = rowOut;
                $('div#progress').text(progress + '/' + limit);
                if (progress < limit && doing_request) {
                    displayProgress(limit);
                }
            }
        });
    }, 3000);
}

function mergeDuplicates(src_to_dest, ids_processed) {
    
//    console.log('salut');
//    console.log(src_to_dest);
//    console.log(ids_processed);
//
//    ids_processed.forEach(function (id) {
//        console.log(id);
//        console.log($('table#customer > tr#' + id));
//    });
//    return;

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremovev2duplicate/interface.php",
        data: {
            action: 'merge_duplicates',
            src_to_dest: src_to_dest,
            ids_processed: ids_processed
        },
        beforeSend: function () {
            $('i#spinner').css('display', 'block');
            $('input#display_duplicates').css('display', 'none');
        },
        error: function () {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            alert("Erreur PHP 8647");
        },
        success: function (rowOut) {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            var out = JSON.parse(rowOut);
            displayOutputMerge(out);
        }
    });
}

function initDuplicate() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremovev2duplicate/interface.php",
        data: {
            action: 'init_duplicate'
        },
        beforeSend: function () {
            $('i#spinner_init').css('display', 'block');
            $('input#display_duplicates').css('display', 'none');
            $('input#init_duplicate').css('display', 'none');
        },
        error: function () {
            $('i#spinner_init').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            $('input#init_duplicate').css('display', 'block');
            alert("Erreur PHP 8647");
        },
        success: function (rowOut) {
            $('i#spinner_init').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            $('input#init_duplicate').css('display', 'block');
            var out = JSON.parse(rowOut);
            if (out) {
                try {
                    if (parseInt(out.code) == 1)
                        displayMessage("Tous les tiers ont été réinitialisé.", 'mesgs');
                    else if (parseInt(out.code) == 0)
                        displayMessage("Rien n'a été fait.", 'warnings');
                    else
                        displayMessage("Erreur inconnue.", 'errors');
                } catch (e) {
                    displayMessage("Erreur:" + e, 'errors');
                }
            } else {
                displayMessage("Aucune réponse du serveur", 'errors');
            }
        }
    });
}


function setAsProcessed(id) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremovev2duplicate/interface.php",
        data: {
            action: 'set_as_processed',
            id: id
        },
        error: function () {
            alert("Erreur PHP 1649");
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (parseInt(out.code) === 1) {
                var key_group = $('tr#' + id).attr('key_group');
                var sucess_first = '';
                if ($('table#customer > tbody > tr#' + id).attr('id') == $('table#customer > tbody > tr[key_group=' + key_group + ']:first').attr('id'))
                    sucess_first += ' > td:not(:first)';
                $('table#customer > tbody > tr#' + id + sucess_first).css('background-color', 'rgb(153, 153, 153)');
                $('table#customer > tbody > tr#' + id + ' > td[name=errors]').html('Ignoré');
                $('tr#' + id).find('input:not([merge_this_group="true"])').remove();
                removeLastInput(key_group);
            } else {
                displayMessage("Erreur serveur 4318", 'errors');
            }
        }
    });
}


/**
 * Ready
 */

$(document).ready(function () {
    initEvents();
    $('.select2').select2();
});


/**
 * Functions
 */
function initEvents() {
    // DISPLAY DUPLICATE
    // By Clicking
    $('input#display_duplicates').click(function () {
        preGetAllDuplicate();
    });

    // By pressing enter
    $('input#limit').keypress(function (e) {
        if (e.which == 13) {
            preGetAllDuplicate();
        }
    });

    // INIT DUPLICATE
    $('input#init_duplicate').click(function () {
        if (confirm("Êtes-vous sûr de vouloir réinitialiser les tiers ?\n\
Cette action marquera TOUS les tiers comme étant non vérifiés\n\
par ce programme de détection des doublons."))
            initDuplicate();
    });
}

function preGetAllDuplicate() {
    var limit = parseInt($('input#limit').val());
    var s_min = parseInt($('input#s_min').val());
    var s_name = parseInt($('input#s_name').val());
    var s_email = parseInt($('input#s_email').val());
    var s_address = parseInt($('input#s_address').val());
    var s_zip = parseInt($('input#s_zip').val());
    var s_town = parseInt($('input#s_town').val());
    var s_phone = parseInt($('input#s_phone').val());
    var s_siret = parseInt($('input#s_siret').val());
    var commercial = getCommercial();
    getAllDuplicate(limit, s_min, s_name, s_email, s_address, s_zip, s_town, s_phone, s_siret, commercial, 'true');
}

function getCommercial() {
    var commercial = [];
    $.each($("select#commercial > option:selected"), function () {
        commercial.push($(this).val());
    });
    return commercial;
}

function iniEventAfterDisplayDuplicate() {
    var ids_processed = [];

    // Merge duplicate
    $('input#merge_duplicates').click(function () {
        var radio_checked = {};
        $('input[type="radio"][r_keep="true"]:checked').each(function () {
            var group_name = $(this).attr('name');
            var radio_value = $(this).val();
            radio_checked[group_name] = radio_value;
            var is_processed = $(this).parent().parent().attr('processed');
            if (is_processed === 'true')
                ids_processed.push(radio_value);
        });
        var src_to_dest = {};
        $('table#customer > tbody > tr[key_group]').each(function () {
            var key_group = $(this).attr('key_group');
            var src = parseInt($(this).find('input[type="checkbox"][cb_merge="true"]:checked').val());
            if (src > 0) {
                var dest = radio_checked[key_group];
                src_to_dest[src] = dest;
            }
        });
        mergeDuplicates(src_to_dest, ids_processed);
    });

    // Merge only group
    $('input[merge_this_group="true"]').click(function () {
        var ids_processed = [];

        var key_group = $(this).attr('key_group');
        var radio_checked = {};
        $('input[name=' + key_group + '][type="radio"][r_keep="true"]:checked').each(function () {
            var radio_value = $(this).val();
            radio_checked[key_group] = radio_value;
            var is_processed = $(this).parent().parent().attr('processed');
            if (is_processed === 'true')
                ids_processed.push(radio_value);
        });
        var src_to_dest = {};
        $('table#customer > tbody > tr[key_group="' + key_group + '"]').each(function () {
            var src = parseInt($(this).find('input[type="checkbox"][cb_merge="true"]:checked').val());
            if (src > 0) {
                var dest = radio_checked[key_group];
                src_to_dest[src] = dest;
            }
        });
        mergeDuplicates(src_to_dest, ids_processed);
    });

    // Prevent merge it self, part 1
    $('input[r_keep=true]').change(function () {
        if ($(this).prop('checked'))
            $(this).parent().parent().find('input[type="checkbox"][cb_merge="true"]').prop('checked', false);
    });
    // Prevent merge it self, part 2
    $('input[cb_merge=true]').change(function () {
        if ($(this).prop('checked')) {
            if ($(this).parent().parent().find('input[type="radio"][r_keep="true"]').prop('checked')) {
                alert("Action impossible, il est impossible de fusionner un tier avec lui-même.");
                $(this).prop('checked', false);
            }
        }
    });

    // Set as processed
    $('input[set_as_processed="true"]').click(function () {
        var id = $(this).attr('id');
        setAsProcessed(id);
    });

}

/**
 * 
 * @param {type} c customer
 * @param {type} key_checkbox key used as name in checkbox
 * @param {type} is_last used to split group with black tr
 * @return {undefined}
 */
function addLine(c, key_group, is_last, is_first, nb_in_group, a_siret_is_set) {

    if (is_last) {
        var html = '<tr id="' + c.rowid + '" key_group=' + key_group + ' processed=' + c.not_processed + ' class="last">';
    } else {
        var html = '<tr id="' + c.rowid + '" key_group=' + key_group + ' processed=' + c.not_processed + '>';
    }

    if (is_first)
        html += '<td rowspan=' + nb_in_group + ' style="border-bottom: solid black 1px;"><input type="submit" class="butAction" key_group="' + key_group + '" merge_this_group="true" value="Fusionner"></td>';

    if (a_siret_is_set == 0 || (a_siret_is_set == 1 && c.siret != ''))
        html += '<td><input r_keep="true" type="radio" name="' + key_group + '" value=' + c.rowid + ' checked></td>';
    else
        html += '<td><input r_keep="true" type="radio" name="' + key_group + '" value=' + c.rowid + '></td>';

    html += '<td><input cb_merge="true" not_processed=' + c.not_processed + ' type="checkbox" name="' + key_group + '" value=' + c.rowid + '></td>';

    // Button to set as processed
    if (c.not_processed === true)
        html += '<td><input type="submit" class="butAction" set_as_processed="true" id=' + c.rowid + ' value="Ignorer"></td>';
    else
        html += '<td></td>';


    html += '<td>' + c.nom + '</td>';
    html += '<td>' + c.email + '</td>';
    html += '<td>' + c.address + '</td>';
    html += '<td>' + c.zip + '</td>';
    html += '<td>' + c.town + '</td>';
    html += '<td>' + c.phone + '</td>';
    html += '<td>' + c.siret + '</td>';
    html += '<td>' + convertDate(c.datec) + '</td>';

    // Commerciaux
    html += '<td>';
    for (var i in c.commerciaux) {
        if (i == 0)
            html += c.commerciaux[i];
        else
            html += ' - ' + c.commerciaux[i];
    }
    html += '</td>';


// Link to societe
    html += '<td><a target=blank href="' + DOL_URL_ROOT + '/comm/card.php?socid=' + c.rowid + '" title="<div class=&quot;centpercent&quot;><u>ShowCompany</u></div>" class="classfortooltip refurl">';
    html += '<img src="/bimp-8/bimp-erp/htdocs/theme/eldy/img/object_company.png" alt="" class="paddingright classfortooltip valigntextbottom"> </a></td>';
    html += '<td name="errors"></td>';
    $('table#customer').append(html);
}

function displayOutputMerge(tab) {

    var success = tab.success;
    var errors = tab.errors;

    // Success
    for (var i in success) {
        var id_success = i;
        var id_merge = success[i];

        var sucess_first = '';
        var merge_first = '';

        var key_group = $('table#customer > tbody > tr#' + id_success).attr('key_group');
        if ($('table#customer > tbody > tr#' + id_success).attr('id') == $('table#customer > tbody > tr[key_group=' + key_group + ']:first').attr('id'))
            sucess_first += ' > td:not(:first)';

        if ($('table#customer > tbody > tr#' + id_merge).attr('id') == $('table#customer > tbody > tr[key_group=' + key_group + ']:first').attr('id'))
            merge_first += ' > td:not(:first)';

        // Add color
        $('table#customer > tbody > tr#' + id_success + sucess_first).css('background-color', 'rgba(0, 153, 0, 0.5)');
        // Remove input
        $('table#customer > tbody > tr#' + id_success).find('input').remove();
        $('table#customer > tbody > tr#' + id_success + ' > td[name=errors]').html('Fusionné avec <a style="color: blue;">' + id_merge + "</a>");
        $('table#customer > tbody > tr#' + id_success + ' > td[name=errors]').hover(function () {
            $(this).css('cursor', 'pointer');
            $('table#customer > tbody > tr#' + id_merge + merge_first).css('background-color', 'rgb(255, 255, 153)');
        }, function () {
            $('table#customer > tbody > tr#' + id_merge + merge_first).css('background-color', '');
        });
        removeLastInput($('table#customer > tbody > tr#' + id_success).attr('key_group'));
    }

    // Errors
    for (var i in errors) {
        var id_error = i;
        var error = errors[id_error];
        var error_first = '';

        var key_group = $('table#customer > tbody > tr#' + id_error).attr('key_group');
        if ($('table#customer > tbody > tr#' + id_error).attr('id') == $('table#customer > tbody > tr[key_group=' + key_group + ']:first').attr('id')) {
            error_first += ' > td:not(:first)';
        }

        // Add color
        $('table#customer > tbody > tr#' + id_error + error_first).css('background-color', 'rgba(255, 0, 0, 0.5)');
        // Remove input
        $('table#customer > tbody > tr#' + id_error + ' > td[name=errors]').text(error);
        removeLastInput($('table#customer > tbody > tr#' + id_error).attr('key_group'));
    }
}

function removeLastInput(key_group) {
    var nb_line_for_group = $('table#customer > tbody > tr[key_group=' + key_group + ']').find('input[type="radio"][r_keep="true"]').length;
    if (nb_line_for_group == 1)
        $('table#customer > tbody > tr[key_group=' + key_group + ']').find('input').remove();
}


/**
 * Functions Annexes
 */

/* Get the parameter sParam */
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

function convertDate(inputFormat) {
    if (inputFormat === null)
        return '';
    function pad(s) {
        return (s < 10) ? '0' + s : s;
    }
    var d = new Date(inputFormat);
    return [pad(d.getDate()), pad(d.getMonth() + 1), d.getFullYear()].join('/');
}