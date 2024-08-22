function BimpContrat() {
    var ptr = this;
    // Events:

    this.onPeriodicityMassProcessFormLoaded = function ($form) {
        $form.find('.line_check').change(function () {
            var $row = $(this).findParentByClass('contrat_line_row');

            var id_line = parseInt($row.data('id_line'));

            if ($(this).prop('checked')) {
                $row.addClass('selected');

                if (id_line) {
                    $form.find('tr.line_' + id_line + '_sub_line').addClass('selected');

                    var $input = $row.find('input[name="line_' + id_line + '_nb_periods"]');
                    if ($input.length && !parseInt($input.val())) {
                        var nb_periods_default = parseInt($row.data('nb_periods_default'));
                        $input.val(nb_periods_default).change();
                    }
                }
            } else {
                $row.removeClass('selected');

                if (id_line) {
                    $form.find('tr.line_' + id_line + '_sub_line').removeClass('selected');

                    var $input = $row.find('input[name="line_' + id_line + '_nb_periods"]');
                    if ($input.length && parseInt($input.val())) {
                        $input.val(0).change();
                    }
                }
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
                if (!$(this).prod('checked')) {
                    $(this).prop('checked', true).change();
                }
            });
        });
        $form.find('.uncheck_all_lines').click(function () {
            $form.find('.line_check').each(function () {
                if ($(this).prod('checked')) {
                    $(this).prop('checked', false).change();
                }
            });
        });

        $form.find('input.line_nb_periods').change(function () {
            var val = parseFloat($(this).val());

            var $row = $(this).findParentByClass('contrat_line_row');

            if (isNaN(val) || !val) {
                $row.find('.line_check').prop('checked', false).change();
            } else {
                $row.find('.line_check').prop('checked', true).change();
            }

            if (!val) {
                $row.find('.variable_qty_inputs_container').stop().slideUp(250, function () {
                    $(this).attr('style', 'margin-top: 10px; display: none');
                });
            } else {
                $row.find('.variable_qty_inputs_container').stop().slideDown(250, function () {
                    $(this).attr('style', 'margin-top: 10px');
                });
            }

            $row.find('.period_qty_input_container').each(function () {
                var period = parseInt($(this).data('period'));

                if (period <= val) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            ptr.checkPeriodicProcessFormLineTotalQty($row);
        });

        $form.find('.line_period_qty').change(function () {
            var $row = $(this).findParentByClass('contrat_line_row');
            if ($.isOk($row)) {
                ptr.checkPeriodicProcessFormLineTotalQty($row);
            }
        });

        $form.find('select.line_qty_mode').change(function () {
            var mode = $(this).val();
            var id_line = parseInt($(this).data('id_line'));

            if (id_line) {
                switch (mode) {
                    case 'per_period':
                        $('.line_' + id_line + '_qties_per_period').stop().slideDown(250);
                        $('.line_' + id_line + '_total_qty').stop().slideUp(250);
                        break;

                    case 'total':
                        $('.line_' + id_line + '_total_qty').stop().slideDown(250);
                        $('.line_' + id_line + '_qties_per_period').stop().slideUp(250);
                        break;
                }
            }
        });
    };

    this.checkPeriodicProcessFormLineTotalQty = function ($row) {
        if ($row.find('.variable_qty_inputs_container').length) {
            var id_line = parseInt($row.data('id_line'));
            var nb_periods = parseFloat($row.find('input.line_nb_periods').val());
            var nb_decimals = parseInt($row.data('nb_decimals'));

            if (!isNaN(nb_periods) && nb_periods) {
                var total = 0;
                var qty_per_period = parseFloat($row.find('input[name="line_' + id_line + '_qty_per_period"]'));

                for (var i = 1; i <= nb_periods; i++) {
                    var $input = $row.find('input[name="line_' + id_line + '_qty_period_' + i + '"]');

                    if ($input.length) {
                        total += parseFloat($input.val());
                    } else {
                        total += qty_per_period;
                    }
                }

                total = Math.round10(total, -nb_decimals);

                $row.find('input[name="line_' + id_line + '_total_qty"]').val(total);
                $row.find('span.line_total_qty').text(total);
            }
        }
    };

    // Traitements formulaires:

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
                var subprice = null;

                if (isNaN(nb_periods)) {
                    $line_row.addClass('has_errors');
                    bimp_msg('Ligne #' + id_line + ' : Nombre de périodes à facturer invalide', 'danger');
                    has_errors = true;
                } else if (nb_periods > 0) {
                    var total_qty = 0;
                    var $input = $line_row.find($('input[name="line_' + id_line + '_total_qty"]'));
                    if ($input.length) {
                        total_qty = parseFloat($input.val());
                        if (isNaN(total_qty)) {
                            $line_row.addClass('has_errors');
                            total_qty = 0;
                            bimp_msg('Ligne #' + id_line + ' : Quantité totale à facturer invalide', 'danger');
                            has_errors = true;
                        } else if (!total_qty) {
                            $line_row.addClass('has_errors');
                            bimp_msg('Ligne #' + id_line + ' : Veuillez saisir une quantité totale à facturer supérieure à 0', 'danger');
                            has_errors = true;
                        }
                    }
                    $input = $line_row.find($('input[name="line_' + id_line + '_subprice"]'));
                    if ($input.length) {
                        var subprice = parseFloat($input.val());
                        if (isNaN(subprice)) {
                            $line_row.addClass('has_errors');
                            subprice = 0;
                            bimp_msg('Ligne #' + id_line + ' : prix de vente invalide', 'danger');
                            has_errors = true;
                        } else if (!subprice) {
                            $line_row.addClass('has_errors');
                            bimp_msg('Ligne #' + id_line + ' : Veuillez saisir un prix de vente à facturer supérieur à 0', 'danger');
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

                    var sub_lines = {};

                    $form.find('tr.line_' + id_line + '_sub_line').each(function () {
                        var $sub_line_row = $(this);
                        var id_sub_line = parseInt($(this).data('id_line'));

                        if (!isNaN(id_sub_line) && id_sub_line) {
                            var sub_line_data = {};
                            var $input = $sub_line_row.find($('input[name="line_' + id_sub_line + '_total_qty"]'));

                            if ($input.length) {
                                var sub_line_total_qty = parseFloat($input.val());
                                if (isNaN(sub_line_total_qty)) {
                                    $sub_line_row.addClass('has_errors');
                                    total_qty = 0;
                                    bimp_msg('Sous-ligne #' + id_sub_line + ' : Quantité totale à facturer invalide', 'danger');
                                    has_errors = true;
                                } else if (!sub_line_total_qty) {
                                    $sub_line_row.addClass('has_errors');
                                    bimp_msg('Sous-ligne #' + id_line + ' : Veuillez saisir une quantité totale à facturer supérieure à 0', 'danger');
                                    has_errors = true;
                                } else {
                                    sub_line_data['total_qty'] = sub_line_total_qty;
                                }
                            }

                            $input = $sub_line_row.find($('input[name="line_' + id_sub_line + '_subprice"]'));
                            if ($input.length) {
                                var sub_line_subprice = parseFloat($input.val());
                                if (isNaN(sub_line_subprice)) {
                                    $sub_line_row.addClass('has_errors');
                                    sub_line_subprice = 0;
                                    bimp_msg('Sous-ligne #' + id_sub_line + ' : prix de vente invalide', 'danger');
                                    has_errors = true;
                                }
//                                else if (!sub_line_subprice) {
//                                    $sub_line_row.addClass('has_errors');
//                                    bimp_msg('Sous-ligne #' + id_sub_line + ' : Veuillez saisir un prix de vente à facturer supérieur à 0', 'danger');
//                                    has_errors = true;
//                                } 
                                else {
                                    sub_line_data['subprice'] = sub_line_subprice;
                                }
                            }

                            sub_lines[id_sub_line] = sub_line_data;
                        }
                    });

                    clients[id_client][fac_idx]['lines'].push({
                        'id_line': id_line,
                        'nb_periods': nb_periods,
                        'total_qty': total_qty,
                        'subprice': subprice,
                        'sub_lines': sub_lines
                    });
                }
            }
        });

        if (has_errors) {
            return false;
        }

        extra_data['clients'] = clients;
        return extra_data;
    };

    this.onPeriodicAchatProcessFormSubmit = function ($form, extra_data) {
        var has_errors = false;
        var fourns = {};

        $form.find('tr.contrat_line_row').removeClass('has_errors').each(function () {
            var $line_row = $(this);

            if ($line_row.find('.line_check').prop('checked')) {
                var id_fourn = parseInt($line_row.data('id_fourn'));
                var id_entrepot = parseInt($line_row.data('id_entrepot'));
                var id_line = parseInt($line_row.data('id_line'));
                var nb_periods = parseInt($line_row.find($('input.line_nb_periods')).val());

                if (isNaN(nb_periods)) {
                    $line_row.addClass('has_errors');
                    bimp_msg('Ligne #' + id_line + ' : Qté invalide', 'danger');
                    has_errors = true;
                } else if (nb_periods > 0) {
                    var total_qty = 0;
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

                    var $input = $line_row.find($('input[name="line_' + id_line + '_total_qty"]'));
                    if ($input.length) {
                        total_qty = parseFloat($input.val());
                        if (isNaN(total_qty)) {
                            $line_row.addClass('has_errors');
                            total_qty = 0;
                            bimp_msg('Ligne #' + id_line + ' : Quantité totale à acheter invalide', 'danger');
                            has_errors = true;
                        } else if (!total_qty) {
                            $line_row.addClass('has_errors');
                            bimp_msg('Ligne #' + id_line + ' : Veuillez saisir une quantité totale à acheter supérieure à 0', 'danger');
                            has_errors = true;
                        }
                    }

                    fourns[id_fourn][id_entrepot]['lines'].push({
                        'id_line': id_line,
                        'nb_periods': nb_periods,
                        'total_qty': total_qty,
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

    this.onRenouvAbonnementFormSubmit = function ($form, extra_data) {
        var lines = [];

        extra_data['id_main_line'] = parseInt($form.find('input[name="id_main_line"]').val());

        $form.find('.line_check:checked').each(function () {
            lines.push(parseInt($(this).data('id_line')));
        });

        if (!lines.length) {
            bimp_msg('Aucune ligne sélectionnée', 'danger');
            return false;
        }

        extra_data['lines'] = lines;

        return extra_data;
    };

    this.onResiliateAbonnementFormSubmit = function ($form, extra_data) {
        var lines = [];

        $form.find('.line_check:checked').each(function () {
            lines.push(parseInt($(this).data('id_line')));
        });

        if (!lines.length) {
            bimp_msg('Aucune ligne sélectionnée', 'danger');
            return false;
        }

        extra_data['lines'] = lines;

        return extra_data;
    };

    this.onAddUnitsFormSubmit = function ($form, extra_data) {
        var lines = [];

        $form.find('input.line_nb_units').each(function () {
            var nb_units = parseInt($(this).val());

            if (nb_units) {
                var id_line = parseInt($(this).data('id_line'));
                lines.push({
                    'id_line': id_line,
                    'nb_units': nb_units
                });
            }
        });

        if (!lines.length) {
            bimp_msg('Aucune unité à ajouter ou retirer', 'danger');
            return false;
        }

        extra_data.lines = lines;
        return extra_data;
    };

    // Synthèse : 
    this.onSyntheseProdLineDisplayFilterChange = function ($input) {
        if ($.isOk($input)) {
            var checked = $input.prop('checked');
            var status_code = $input.val();

            var $container = $input.findParentByClass('prod_sublines_container');

            if ($.isOk($container)) {
                var $rows = $container.find('tr.status_' + status_code);

                if ($rows.length) {
                    if (checked) {
//                        bimp_msg('show : ' + status_code);
                        $rows.show();
                    } else {
//                        bimp_msg('hide : ' + status_code);
                        $rows.hide();
                    }
                }
            }
        }
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

    $('body').on('objectChange', function (e) {
        if (e.object_name === 'BCT_ContratLine' || e.object_name === 'BCT_Contrat') {
            $('.refreshContratSyntheseButton').click();
            $('.refreshContratFacturesButton').click();
            $('.refreshContratAchatsButton').click();
        }
    });
});