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

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            message: message,
            code: code,
            action: 'set_message'
        },
        error: function () {
            console.log("Erreur PHP 1861");
        },
        success: function () {
            location.reload();
        }
    });
}


function getAllFactures(limit, details) {

    if (limit > 100) {
        displayMessage("La requête est trop longue (100 doublons ou plus) pour être exécuter.", 'errors');
        return -1;
    } else if (limit <= 0) {
        displayMessage("Le nombre de doublon doit être supérieur à zéro.", 'errors');
        return -2
    }

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            limit: limit,
            details: details,
            action: 'get_all_duplicate'
        },
        beforeSend: function () {
            $('i#spinner').css('display', 'block');
            $('input#display_duplicates').css('display', 'none');
        },
        error: function () {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            alert("Erreur PHP 2654");
        },
        success: function (out) {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            $('table#customer > tbody > tr[key_group]').remove();

            var parsed_out = JSON.parse(out);
            var array_of_duplicate = parsed_out.duplicates;
            for (var i in array_of_duplicate) {
                var duplicates = array_of_duplicate[i];
                var last = Object.keys(duplicates).length - 1;
                duplicates.forEach(function (useless, index) {
                    var duplicate = duplicates[index];
                    var key_group = i;
                    if (index == last)
                        var is_last = true;
                    else
                        var is_last = false;
                    if (index == 0)
                        var is_first = true;
                    else
                        var is_first = false;
                    add_line(duplicate, key_group, is_last, is_first, last + 1);
                });
            }

            // Add button and his event
            if ($('input#merge_duplicates').length == 0)
                $('div#id-right').append('<br/><input type="submit" class="butAction" id="merge_duplicates" value="Fusionner tous les doublons">');

            // Display number group doublon
            var nb_row = parsed_out.nb_row;
            $('div#db_duplicate').text(nb_row);
            iniEventAfterDisplayDuplicate();
        }
    });
}

function mergeDuplicates(src_to_dest) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            action: 'merge_duplicates',
            src_to_dest: src_to_dest
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

/**
 * Ready
 */

$(document).ready(function () {
    initEvents();
});
/**
 * Functions
 */
function initEvents() {
    // By Clicking
    $('input#display_duplicates').click(function () {
        var limit = parseInt($('input#limit').val());
        getAllFactures(limit, 'true');
    });

    // By pressing enter
    $('input#limit').keypress(function (e) {
        if (e.which == 13) {
            var limit = parseInt($('input#limit').val());
            getAllFactures(limit, 'true');
        }
    });
}

function iniEventOne() {
    // Merge duplicate
    $('input#merge_duplicates').click(function () {
        var radio_checked = {};
        $('input[type="radio"][r_keep="true"]:checked').each(function () {
            var group_name = $(this).attr('name');
            var radio_value = $(this).val();
            radio_checked[group_name] = radio_value;
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
        mergeDuplicates(src_to_dest);
    });
}


function iniEventAfterDisplayDuplicate() {
    // Merge only group
    $('input[merge_this_group="true"]').click(function () {
        var key_group = $(this).attr('key_group');
        var radio_checked = {};
        $('input[name=' + key_group + '][type="radio"][r_keep="true"]:checked').each(function () {
            var radio_value = $(this).val();
            radio_checked[key_group] = radio_value;
        });
        var src_to_dest = {};
        $('table#customer > tbody > tr[key_group="' + key_group + '"]').each(function () {
            var src = parseInt($(this).find('input[type="checkbox"][cb_merge="true"]:checked').val());
            if (src > 0) {
                var dest = radio_checked[key_group];
                src_to_dest[src] = dest;
            }
        });
        mergeDuplicates(src_to_dest);
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
}

/**
 * 
 * @param {type} c customer
 * @param {type} key_checkbox key used as name in checkbox
 * @param {type} is_last used to split group with black tr
 * @return {undefined}
 */
function add_line(c, key_group, is_last, is_first, nb_in_group) {

    if (is_last) {
        var html = '<tr id="' + c.rowid + '" key_group=' + key_group + ' class="last">';
    } else {
        var html = '<tr id="' + c.rowid + '" key_group=' + key_group + '>';
    }

    if (is_first)
        html += '<td rowspan=' + nb_in_group + ' style="border-bottom: solid black 1px;"><input type="submit" class="butAction" key_group="' + key_group + '" merge_this_group="true" value="Fusionner"></td>';
    html += '<td><input r_keep="true" type="radio" name="' + key_group + '" value=' + c.rowid + ' checked></td>'; // TODO remove "checked"
    html += '<td><input cb_merge="true" type="checkbox" name="' + key_group + '" value=' + c.rowid + '></td>';
    html += '<td>' + c.nom + '</td>';
    html += '<td>' + c.email + '</td>';
    html += '<td>' + c.address + '</td>';
    html += '<td>' + c.zip + '</td>';
//    html += '<td>' + ((c.statut == '1') ? 'Oui' : 'Non') + '</td>';
    html += '<td>' + c.town + '</td>';
    html += '<td>' + c.phone + '</td>';

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
    html += '<td><a target=blank href="' + DOL_URL_ROOT + '/societe/card.php?socid=' + c.rowid + '" title="<div class=&quot;centpercent&quot;><u>ShowCompany</u></div>" class="classfortooltip refurl">';
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
