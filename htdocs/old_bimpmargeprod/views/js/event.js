var coprods = 0;

function insertEventMontantDetailsListRow(id_montant, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $row = $button.parent('td').parent('tr');

    if (!$row.length) {
        bimp_msg('Une erreur est survenue. impossible de charger la liste des détails', 'danger', null, true);
        $button.hide();
        return;
    }

    var html = '<tr id="eventMontant_' + id_montant + '_details_row">';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td style="display: none" id="eventMontant_' + id_montant + '_details_container" colspan="' + ($row.find('td').length - 3) + '"></td>';
    html += '</tr>';

    $row.after(html);

    var $resultContainer = $row.parent('tbody').find('#eventMontant_' + id_montant + '_details_container');

    BimpAjax('loadEventMontantDetails', {
        id_event_montant: id_montant
    }, $resultContainer, {
        $button: $button,
        display_success: false,
        display_processing: true,
        processing_msg: 'Chargement en cours',
        error_msg: 'Echec du chargement de la liste des détails',
        append_html: true,
        success: function (result, bimpAjax) {
            bimpAjax.$button.hide();
            bimpAjax.$button.parent('td').find('.hideDetailsList').removeClass('hidden').show();
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.hide();
        }
    });
}

function removeEventMontantDetailsListRow(id_montant, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $row = $('#eventMontant_' + id_montant + '_details_row');
    if ($row.length) {
        $row.stop().slideUp(250, function () {
            $(this).remove();
            $button.removeClass('disabled').hide();
            $button.parent('td').find('.showDetailsList').show();
        });
    } else {
        bimp_msg('Une erreur est survenue', 'danger', null, true);
        $button.hide();
    }
}

$(document).ready(function () {
    $('div.tabs').find('#calcauto').parent('div.tabsElem').css('float', 'right');

    $('body').on('controllerTabLoaded', function (e) {
        if (e.tab_name === 'default') {
            var $list = $('.BMP_EventCoProd_list');
            if ($list.length) {
                if (!$list.find('tbody.listRows').find('tr.objectListItemRow').length) {
                    $('div.tabs').find('#parts').hide();
                } else {
                    $('div.tabs').find('#parts').show();
                }
                $list.on('listRefresh', function (e) {
                    if (!$list.find('tbody.listRows').find('tr.objectListItemRow').length) {
                        $('div.tabs').find('#parts').hide();
                    } else {
                        $('div.tabs').find('#parts').show();
                    }
                });
            }
        }
    });

    $('body').on('objectChange', function (e) {
        if (e.object_name === 'BMP_Event') {
            bimp_msg_enable = false;
            e.stopPropagation();
            window.location.reload();
        }
    });

    var $cp_list = $('.BMP_EventCoProd_list');
    if ($cp_list.length) {
        if (!$cp_list.find('tbody.listRows').find('tr.objectListItemRow').length) {
            $('div.tabs').find('#parts').hide();
        } else {
            $('div.tabs').find('#parts').show();
        }
        $cp_list.on('listRefresh', function (e) {
            if (!$cp_list.find('tbody.listRows').find('tr.objectListItemRow').length) {
                $('div.tabs').find('#parts').hide();
            } else {
                $('div.tabs').find('#parts').show();
            }
        });
    }
});