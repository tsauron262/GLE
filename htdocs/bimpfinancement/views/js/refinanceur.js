function addNewCoefsPeriodRange($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $input = $('#newCoefsPeriodRangeForm').find('[name="new_period_range"]');

    if (!$.isOk($input)) {
        return;
    }

    var new_period = parseInt($input.val());

    if (isNaN(new_period) || !new_period) {
        bimp_msg('veuillez entrer une valeur valide (entier positif)', 'warning', null, true);
        return;
    }

    $button.addClass('disabled');

    var $tables = $('#coefs_ranges_content').find('table.coefsRangesTable');
    var input_html = $('#coef_input_template').html();

    var error = false;
    $tables.each(function () {
        if (error) {
            return;
        }
        var $table = $(this);
        var $cols = $table.find('thead').find('th.coefs_period_col');
        var $rows = $table.find('tbody').find('tr.coefs_amount_row');
        var done = false;
        $cols.each(function () {
            if (!done) {
                var $col = $(this);
                var period = $col.data('period');
                if (period !== 'last') {
                    period = parseInt(period);
                }

                if (new_period === period) {
                    bimp_msg('Cette tranche de durée existe déjà', 'warning', null, true);
                    $button.removeClass('disbaled');
                    done = true;
                    error = true;
                    return;
                }

                if (period === 'last' || new_period < period) {
                    $col.before('<th class="coefs_period_col" data-period="' + new_period + '">&lt;= ' + new_period + ' mois</th>');
                    $rows.each(function () {
                        var $row = $(this);
                        var td_done = false;
                        $row.find('td').each(function () {
                            if (!td_done) {
                                var td_period = $(this).data('period');
                                if (td_period === 'last' || new_period < parseInt(td_period)) {
                                    $(this).before('<td class="coef_value" style="max-width: 90px;" data-period="' + new_period + '" data-amount="' + $row.data('amount') + '">' + input_html + '</td>');
                                    td_done = true;
                                }
                            }
                        });
                    });
                    var btn_td_done = false;
                    $table.find('tbody').find('tr.delte_col_buttons_row').find('td').each(function () {
                        if (!btn_td_done) {
                            var td_period = $(this).data('period');
                            if (typeof (td_period) !== 'undefined') {
                                if (td_period === 'last' || new_period < parseInt(td_period)) {
                                    $(this).before('<td data-period="' + new_period + '" style="border: none;text-align: center;">' + $('#delete_col_btn_template').html() + '</td>');
                                    btn_td_done = true;
                                }
                            }
                        }
                    });
                    if (period === 'last') {
                        $col.html('&gt; ' + new_period + ' mois');
                    }
                    done = true;
                }
            }
        });

        setCommonEvents($table);
        setInputsEvents($table);
    });

    $button.removeClass('disabled');
    $input.val('');
}

function addNewCoefsAmountRange($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $input = $('#newCoefsAmountRangeForm').find('[name="new_amount_range"]');

    if (!$.isOk($input)) {
        return;
    }

    var new_amount = parseFloat($input.val());

    if (isNaN(new_amount) || !new_amount) {
        bimp_msg('veuillez entrer une valeur valide (nombre décimal positif)', 'warning', null, true);
        return;
    }

    $button.addClass('disabled');

    var $tables = $('#coefs_ranges_content').find('table.coefsRangesTable');
    var input_html = $('#coef_input_template').html();

    var error = false;
    $tables.each(function () {
        if (error) {
            return;
        }

        var $table = $(this);
        var $rows = $table.find('tbody').find('tr.coefs_amount_row');
        var $cols = $table.find('thead').find('th.coefs_period_col');
        var done = false;
        $rows.each(function () {
            if (!done) {
                var $row = $(this);
                var amount = $row.data('amount');
                if (amount !== 'last') {
                    amount = Math.round10(parseFloat(amount), -2);
                }

                if (new_amount === amount) {
                    bimp_msg('Cette tranche de mountant existe déjà', 'warning', null, true);
                    $button.removeClass('disbaled');
                    done = true;
                    error = true;
                    return;
                }

                if (amount === 'last' || new_amount < amount) {
                    var html = '<tr class="coefs_amount_row" data-amount="' + new_amount + '">';
                    html += '<th style="width: 120px;">&lt;= ' + lisibilite_nombre(new_amount) + ' &euro;</th>';
                    $cols.each(function () {
                        html += '<td class="coef_value" style="max-width: 90px;" data-period="' + $(this).data('period') + '" data-amount="' + new_amount + '">' + input_html + '</td>';
                    });
                    html += '<td data-amount="' + new_amount + '" style="border: none">';
                    html += $('#delete_row_btn_template').html();
                    html += '</td>';
                    html += '</tr>';

                    $row.before(html);

                    if (amount === 'last') {
                        $row.find('th').html('&gt; ' + lisibilite_nombre(new_amount) + ' &euro;');
                    }
                    done = true;
                }
            }
        });

        setCommonEvents($table);
        setInputsEvents($table);
    });

    $button.removeClass('disabled');
    $input.val('');
}

function deleteCoefsPeriodRange($button) {
    if (!confirm('Veuillez confirmer la suppression de cette tranche' + "\n" + 'Veuillez noter que cette tranche sera supprimée pour toutes les périodicités')) {
        return;
    }

    var period = $button.parent('td').data('period');

    var $tables = $('#coefs_ranges_content').find('table.coefsRangesTable');

    $tables.each(function () {
        var $rows = $(this).find('tr');
        $rows.each(function () {
            var done = false;
            var prev_period = 0;
            $(this).find('th,td').each(function () {
                var td_period = $(this).data('period');
                if (td_period === 'last' && $(this).tagName() === 'th') {
                    $(this).html('&gt; ' + prev_period + ' mois');
                } else if (typeof (td_period) !== 'undefined') {
                    if (!done && parseInt(td_period) === period) {
                        $(this).remove();
                        done = true;
                    } else {
                        prev_period = td_period;
                    }
                }
            });
        });
    });
}

function deleteCoefsAmountRange($button) {
    if (!confirm('Veuillez confirmer la suppression de cette tranche' + "\n" + 'Veuillez noter que cette tranche sera supprimée pour toutes les périodicités')) {
        return;
    }

    var amount = $button.parent('td').data('amount');

    var $tables = $('#coefs_ranges_content').find('table.coefsRangesTable');

    $tables.each(function () {
        var $rows = $(this).find('tr.coefs_amount_row');
        var done = false;
        var prev_amount = 0;

        $rows.each(function () {
            var tr_amount = $(this).data('amount');
            if (!done && tr_amount === amount) {
                $(this).remove();
            } else if (tr_amount === 'last') {
                $(this).find('th').html('&gt; ' + prev_amount + ' &euro;');
            } else {
                prev_amount = tr_amount;
            }
        });
    });
}

function cancelCoefsModifs($button, id_refinanceur) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (!confirm('Veuillez confirmer l\'annulation des modifications.' + "\n" + 'Toutes les données non enregistrées seront perdues.')) {
        return;
    }

    var $container = $('#coefs_ranges_content');

    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue. Veuillez actualiser la page', 'danger');
        return;
    }

    BimpAjax('LoadRefinanceurCoefsForm', {
        id_refinanceur: id_refinanceur
    }, $container, {
        $button: $button,
        display_success: false,
        display_errors_in_popup_only: true,
        display_warnings_in_popup_only: true,
        append_html: true,
        success: function (result, bimpAjax) {
            setCommonEvents(bimpAjax.$resultContainer);
            setInputsEvents(bimpAjax.$resultContainer);
        }
    });
}

function saveCoefsModifs($button, id_refinanceur) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var data = {};

    var $tables = $('#coefs_ranges_content').find('table.coefsRangesTable');
    var hasErrors = false;
    
    $tables.each(function() {
        var $table = $(this);
        data[$table.data('periodicity')] = {};
        
        var periodicity = {};
        $table.find('tbody').find('tr.coefs_amount_row').find('td.coef_value').each(function() {
            var $td = $(this);
            var period = $td.data('period');
            var amount = $td.data('amount');
            
            if (typeof(periodicity[amount]) === 'undefined') {
                periodicity[amount] = {};
            }
            
            var $input = $td.find('[name="coef"]');
            var coef = parseFloat($input.val());
            if (isNaN(coef)) {
                $input.addClass('error');
                hasErrors = true;
            } else {
                $input.removeClass('error');
                periodicity[amount][period] = coef;
            }
        });
        
        data[$table.data('periodicity')] = periodicity;
    });
    
    if (hasErrors) {
        bimp_msg('Certains coefficients sont incorrects', 'danger');
        return;
    }
    
    BimpAjax('saveRefinanceursCoefs', {
        id_refinanceur: id_refinanceur,
        coefs: data
    }, $('#coefsAjaxResult'), {
        display_success_in_popup_only: true,
        display_processing: true,
        processing_padding: 10
    });
}