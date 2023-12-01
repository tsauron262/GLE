function BimpContrat() {
    // Events : 
    this.onPeriodicityMassProcessFormLoaded = function ($form) {
        $form.find('.line_check').change(function () {
            var $row = $(this).findParentByClass('contrat_line_row');

            if ($(this).prop('checked')) {
                $row.addClass('selected');
            } else {
                $row.removeClass('selected');
            }
        });

        $form.find('.client_facture_select').change(function () {
            if (parseInt($(this).val())) {
                $(this).findParentByClass('client_fac_row').find('.fac_libelle_container').stop().slideUp();
            } else {
                $(this).findParentByClass('client_fac_row').find('.fac_libelle_container ').stop().slideDown();
            }
        });

        $form.find('.check_all_lines').click(function () {
            $form.find('.line_check').each(function () {
                $(this).prop('checked', true).change();
            });
        });
        $form.find('.uncheck_all_lines').click(function () {
            $form.find('.line_check').each(function () {
                $(this).prop('checked', false).change();
            });
        });
    };

    // Traitements formulaires : 
    this.onPeriodicFacProcessFormSubmit = function ($form, extra_data) {
        var has_errors = false;
        var clients = {};

        $form.find('tr.contrat_line_row').removeClass('has_errors').each(function () {
            var $line_row = $(this);

            if ($line_row.find('.line_check').prop('checked')) {
                var id_client = parseInt($line_row.data('id_client'));
                var fac_idx = parseInt($line_row.data('fac_idx'));
                var id_line = parseInt($line_row.data('id_line'));
                var nb_periods = parseInt($line_row.find('input.line_nb_periods').val());

                if (isNaN(nb_periods)) {
                    $line_row.addClass('has_errors');
                    bimp_msg('Ligne #' + id_line + ' : Nombre de périodes à facturer invalide', 'danger');
                    has_errors = true;
                } else if (nb_periods > 0) {
                    var qty_per_period = 0;
                    var $input = $line_row.find($('input.line_qty_per_period'));
                    if ($input.length) {
                        qty_per_period = parseFloat($input.val());
                        if (isNaN(qty_per_period)) {
                            $line_row.addClass('has_errors');
                            qty_per_period = 0;
                            bimp_msg('Ligne #' + id_line + ' : Quantité à facturer par période invalide', 'danger');
                            has_errors = true;
                        }
                    }
                    if (typeof (clients[id_client]) === 'undefined') {
                        clients[id_client] = {};
                    }

                    if (typeof (clients[id_client][fac_idx]) === 'undefined') {
                        var $client_fac_row = $('.client_fac_row[data-id_client=' + id_client + '][data-fac_idx=' + fac_idx + ']');

                        clients[id_client][fac_idx] = {
                            'id_facture': $form.find('[name="client_' + id_client + '_fac_' + fac_idx + '"]').val(),
                            'libelle': $form.find('[name="client_' + id_client + '_fac_' + fac_idx + '_libelle"]').val(),
                            'id_entrepot': $client_fac_row.data('id_entrepot'),
                            'secteur': $client_fac_row.data('secteur'),
                            'expertise': $client_fac_row.data('expertise'),
                            'id_mode_reglement': $client_fac_row.data('id_mode_reglement'),
                            'id_cond_reglement': $client_fac_row.data('id_cond_reglement'),
                            'lines': []
                        };
                    }

                    clients[id_client][fac_idx]['lines'].push({
                        'id_line': id_line,
                        'nb_periods': nb_periods,
                        'qty_per_period': qty_per_period
                    });
                }
            }
        });

        if (has_errors) {
            return false;
        }

//        console.log(clients);

        extra_data['clients'] = clients;
        return extra_data;
    };

    this.onPeriodicAchatProcessFormSubmit = function ($form, extra_data) {
        var has_errors = false;
        var fourns = {};

        $form.find('tr.commande_line_row').removeClass('has_errors').each(function () {
            var $line_row = $(this);

            if ($line_row.find('.line_check').prop('checked')) {
                var id_fourn = parseInt($line_row.data('id_fourn'));
                var id_entrepot = parseInt($line_row.data('id_entrepot'));
                var id_line = parseInt($line_row.data('id_line'));
                var nb_periods = parseInt($line_row.find($('input.line_nb_periods')).val());
                var qty_per_period = 0;

                if (isNaN(nb_periods)) {
                    $line_row.addClass('has_errors');
                    bimp_msg('Ligne #' + id_line + ' : Qté invalide', 'danger');
                    has_errors = true;
                } else if (nb_periods > 0) {
                    var pa_ht = parseFloat($line_row.find($('input.line_pa_ht')).val());

                    if (typeof (fourns[id_fourn]) === 'undefined') {
                        fourns[id_fourn] = {};
                    }

                    if (typeof (fourns[id_fourn][id_entrepot]) === 'undefined') {
                        fourns[id_fourn][id_entrepot] = {
                            'id_commande_fourn': $form.find('[name="fourn_' + id_fourn + '_entrepot_' + id_entrepot + '_commande_fourn"]').val(),
                            'lines': []
                        };
                    }

                    var $input = $line_row.find($('input.line_qty_per_period'));
                    if ($input.length) {
                        qty_per_period = parseFloat($input.val());
                        if (isNaN(qty_per_period)) {
                            $line_row.addClass('has_errors');
                            qty_per_period = 0;
                            bimp_msg('Ligne #' + id_line + ' : Quantité à acheter par période invalide', 'danger');
                            has_errors = true;
                        }
                    }

                    fourns[id_fourn][id_entrepot]['lines'].push({
                        'id_line': id_line,
                        'nb_periods': nb_periods,
                        'qty_per_period': qty_per_period,
                        'pa_ht': pa_ht

                    });
                }
            }
        });

        if (has_errors) {
            return false;
        }

        console.log(fourns);

        extra_data['fourns'] = fourns;

        return extra_data;
    };
}

var BimpContrat = new BimpContrat();

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if ($.isOk(e.$form)) {
            if (e.$form.hasClass('BCT_ContratLine_form_periodic_process')) {
                BimpContrat.onPeriodicityMassProcessFormLoaded(e.$form);
            }
        }
    });
});