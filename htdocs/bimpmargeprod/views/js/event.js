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
    
    var $detailsRow = $row.parent('tbody').find('#eventMontant_' + id_montant + '_details_row');
    if ($detailsRow.length) {
        $detailsRow.remove();
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
        processing_msg: '',
        processing_padding: 0,
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

function onEventMontantPaiementFormLoaded($form) {
    if (!$.isOk($form)) {
        return;
    }
    
    if (!parseInt($form.data('bmp_event_events_init'))) {
        $('body').on('inputReloaded', function (e) {
            if (e.input_name === 'paiements' && e.$form.attr('id') === $form.attr('id')) {
                setEventMontantPaiementsInputsEvents(e.$form);
            }
        });
        
        setEventMontantPaiementsInputsEvents($form);

        $form.data('bmp_event_events_init', 1);
    }
}

function setEventMontantPaiementsInputsEvents($form) {
    if (!$.isOk($form)) {
        return;
    }

    var $inputContainer = $form.find('.paiements_inputContainer');
    if ($inputContainer.length) {
        $inputContainer.find('.payment_input').each(function () {
            if (!parseInt($(this).data('event_montant_payment_input_events_init'))) {
                $(this).change(function () {
                    onEventMontantPaiementInputChange($(this));
                });
                $(this).data('event_montant_payment_input_events_init', 1);
            }
        });
    }
}

function onEventMontantPaiementInputChange($input) {
    var $form = $input.findParentByClass('BMP_EventMontant_form_paiement');
    if (!$.isOk($form)) {
        $form = $input.findParentByClass('BMP_EventMontant_form_paiement_montant');
    }

    if ($.isOk($form)) {
        var id_coprod = parseInt($form.find('[name="id_coprod"]').val());
        var $inputContainer = $form.find('.paiements_inputContainer');

        if (!isNaN(id_coprod) && $.isOk($inputContainer)) {
            var montant_ttc = parseFloat($inputContainer.find('[name="montant_total_ttc"]').val());
            var tva_tx = 1 + (parseFloat($inputContainer.find('[name="tva_tx"]').val()) / 100);
            var remain = montant_ttc;
            var $new_val_row = $input.findParentByClass('coprod_payment_row');
            var $rows = $inputContainer.find('tr.coprod_payment_row');

            var new_amount = 0;
            var new_percent = 0;
            var new_type = '';

            if ($.isOk($new_val_row)) {
                var new_val_id_coprod = parseInt($new_val_row.data('id_coprod'));
                if ($input.hasClass('payment_amount_ht')) {
                    new_type = 'amount';
                    var new_amount_ht = parseFloat($input.val());
                    if (isNaN(new_amount_ht)) {
                        return;
                    }
                    
                    new_amount = new_amount_ht * tva_tx;

                    if (new_amount > montant_ttc) {
                        new_amount = montant_ttc;
                    }

                    if (new_amount < 0) {
                        new_amount = 0;
                    }

                    new_percent = (new_amount / montant_ttc) * 100;
                } else if ($input.hasClass('payment_amount_ttc')) {
                    new_type = 'amount';
                    new_amount = parseFloat($input.val());
                    if (isNaN(new_amount)) {
                        return;
                    }

                    if (new_amount > montant_ttc) {
                        new_amount = montant_ttc;
                    }

                    if (new_amount < 0) {
                        new_amount = 0;
                    }

                    new_percent = (new_amount / montant_ttc) * 100;
                } else if ($input.hasClass('payment_percent')) {
                    new_type = 'percent';
                    new_percent = parseFloat($input.val());
                    if (isNaN(new_percent)) {
                        return;
                    }

                    if (new_percent > 100) {
                        new_percent = 100;
                    }

                    if (new_percent < 0) {
                        new_percent = 0;
                    }

                    new_amount = montant_ttc * (new_percent / 100);
                }

                new_amount = Math.round10(new_amount, -2);
                new_percent = Math.round10(new_percent, -2);

                $new_val_row.find('input.payment_amount_ht').val(new_amount / tva_tx);
                $new_val_row.find('input.payment_amount_ttc').val(new_amount);
                $new_val_row.find('input.payment_percent').val(new_percent);
                remain -= new_amount;

                $rows.each(function () {
                    var $row = $(this);
                    var row_id_coprod = parseInt($row.data('id_coprod'));
                    if (row_id_coprod !== new_val_id_coprod && row_id_coprod !== id_coprod) {
                        var row_amount = parseFloat($row.find('.payment_amount_ttc').val());
                        if (isNaN(row_amount) || row_amount < 0) {
                            row_amount = 0;
                        }

                        if (row_amount > remain) {
                            row_amount = remain;
                        }

                        var row_percent = Math.round10((row_amount / montant_ttc) * 100, -2);
                        row_amount = Math.round10(row_amount, -2);
                        remain -= row_amount;

                        $row.find('input.payment_amount_ht').val(row_amount / tva_tx);
                        $row.find('input.payment_amount_ttc').val(row_amount);
                        $row.find('input.payment_percent').val(row_percent);
                    }
                    
                    $row.find('select.payment_type').val(new_type).change();
                });

                var $row = $inputContainer.find('#coprod_' + id_coprod + '_payment_row');
                if ($.isOk($row)) {
                    var row_amount = remain;
                    if (new_val_id_coprod === id_coprod) {
                        row_amount += parseFloat($row.find('input.payment_amount_ttc').val());
                    }
                    if (row_amount < 0) {
                        row_amount = 0;
                    }
                    var row_percent = (row_amount / montant_ttc) * 100;

                    row_amount = Math.round10(row_amount, -2);
                    row_percent = Math.round10(row_percent, -2);

                    $row.find('input.payment_amount_ht').val(row_amount / tva_tx);
                    $row.find('input.payment_amount_ttc').val(row_amount);
                    $row.find('input.payment_percent').val(row_percent);
                }
            }
        }
    }
}

function onEventMontantPaiementFormSubmit($form, extra_data) {
    if ($.isOk($form)) {
        var paiements = {};

        $form.find('.coprod_payment_row').each(function () {
            var id_coprod = $(this).data('id_coprod');
            var amount = 0;
            var type = $(this).find('select.payment_type').val();
            
            if (type === 'amount') {
                amount = parseFloat($(this).find('input.payment_amount_ttc').val());
            } else if (type === 'percent') {
                amount = parseFloat($(this).find('input.payment_percent').val());
            }

            if (isNaN(amount)) {
                amount = 0;
            }

            paiements[id_coprod] = {'type': type, 'value': amount};
        });

        extra_data['paiements'] = paiements;
    }
    return extra_data;
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

    $('body').on('formLoaded', function (e) {
        if (e.$form.attr('id') === 'BMP_EventMontant_paiement_form' || e.$form.hasClass('BMP_EventMontant_form_paiement_montant')) {
            onEventMontantPaiementFormLoaded(e.$form);
        }
    });

//    var $cp_list = $('.BMP_EventCoProd_list');
//    if ($cp_list.length) {
//        if (!$cp_list.find('tbody.listRows').find('tr.objectListItemRow').length) {
//            $('div.tabs').find('#parts').hide();
//        } else {
//            $('div.tabs').find('#parts').show();
//        }
//        $cp_list.on('listRefresh', function (e) {
//            if (!$cp_list.find('tbody.listRows').find('tr.objectListItemRow').length) {
//                $('div.tabs').find('#parts').hide();
//            } else {
//                $('div.tabs').find('#parts').show();
//            }
//        });
//    }
});