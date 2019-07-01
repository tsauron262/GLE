$(document).ready(function () {
    $('body').on('viewLoaded', function (e) {
        if (e.$view.hasClass('BF_Demande_view_montants')) {
            onBFDemandeViewLoaded(e.$view);
        } else if (e.$view.hasClass('BF_Demande_view_fournisseurs')) {
            onBfCommandesFournViewLoaded(e.$view);
        }
    });
    $('body').on('viewRefresh', function (e) {
        if (e.$view.hasClass('BF_Demande_view_montants')) {
            onBFDemandeViewLoaded(e.$view);
        } else if (e.$view.hasClass('BF_Demande_view_fournisseurs')) {
            onBfCommandesFournViewLoaded(e.$view);
        }
    });
    $('body').on('formLoaded', function (e) {
        if (/^BF_Demande_new_commandes_fourn_form_(\d+)$/.test(e.$form.attr('id'))) {
            onCommandesFournFormLoaded(e.$form);
        }
    });
    $('body').on('listLoaded', function (e) {
        if (e.$list.hasClass('BF_DemandeRefinanceur_list_table')) {
            setRefinanceursListRowsEvents(e.$list);
        }
    });
});

function onBFDemandeViewLoaded($view) {
    if ($view.length) {
        bf_demande_initEvents($view);
        $("body").on("listRefresh", function (event) {
            bf_demande_initEvents($view);
        });
        hideShowAvance($view.attr('id'), true);
    }
}

function bf_demande_initEvents($view) {
    var selecteur = '[name="montant_materiels"], [name="montant_services"], [name="montant_logiciels"],' +
            '[name="commission_commerciale"], [name="commission_financiere"],' +
            '[name="quantity"], [name="amount"], [name="amount_ht"],' +
            '[name="mode_calcul"],' +
            '[name="rate"], [name="coef"],' +
            '[name="periodicity"],' +
            '[name="periode2"],' +
            '[name="vr"],[name="vr_vente"]';

    var $fields_table = $view.find('.BF_Demande_fields_table_montants');

    if (!parseInt($fields_table.data('bf_demande_montants_events_init'))) {
        $view.find(selecteur).keyup(function () {
            bf_demande_calculateMontantTotal($view, $(this), false);
        });
        
        $view.find(selecteur).change(function () {
            bf_demande_calculateMontantTotal($view, $(this), false);
        });

        $view.find('[name="commission_commerciale_amount"],[name="commission_financiere_amount"]').keyup(function () {
            bf_demande_calculateMontantTotal($view, $(this), true);
        });
        $view.find('[name="commission_commerciale_amount"],[name="commission_financiere_amount"]').change(function () {
            bf_demande_calculateMontantTotal($view, $(this), true);
        });

        bf_demande_calculateMontantTotal($view);

        $fields_table.data('bf_demande_montants_events_init', 1);
    }
}

function bf_demande_calculateMontantTotal($view, $champ, use_comm_amount) {
    if (typeof (use_comm_amount) === 'undefined') {
        use_comm_amount = false;
    }

    if ($.isOk($champ))
        $champ.val($champ.val().replace(",", ".").replace(" ", "").replace("€", ""));

    if (!$view.length) {
        return;
    }

    var $montant_materiels = $view.find('[name="montant_materiels"]');
    var $montant_services = $view.find('[name="montant_services"]');
    var $montant_logiciels = $view.find('[name="montant_logiciels"]');

    var montant_materiels = parseFloat($montant_materiels.val());
    var montant_services = parseFloat($montant_services.val());
    var montant_logiciels = parseFloat($montant_logiciels.val());

    var total = 0;
    if (montant_materiels) {
        total += montant_materiels;
    }
    if (montant_services) {
        total += montant_services;
    }
    if (montant_logiciels) {
        total += montant_logiciels;
    }

    //Calcul du VR
    var VR_vente = parseFloat($view.find('[name="vr_vente"]').val());
    total = total;// - VR_vente;

    //On a le premier total

    var $commission_commerciale = $view.find('[name="commission_commerciale"]');
    var $commission_financiere = $view.find('[name="commission_financiere"]');
    var $commC = $view.find('[name="commission_commerciale_amount"]');
    var $commF = $view.find('[name="commission_financiere_amount"]');

    var commission_commerciale = 0;
    var commission_financiere = 0;
    var commC = 0;
    var commF = 0;

    if (use_comm_amount) {
        commC = parseFloat($commC.val());
        commF = parseFloat($commF.val());

        if (commC) {
            commission_commerciale = Math.round10((commC / total) * 100, -6);
        }

        if (commF) {
            commission_financiere = Math.round10((commF / (total + commC)) * 100, -6);
        }

        if (parseFloat($commission_commerciale.val()) !== commission_commerciale) {
            $commission_commerciale.val(commission_commerciale);
            checkTextualInput($commission_commerciale);
        }

        if (parseFloat($commission_financiere.val()) !== commission_financiere) {
            $commission_financiere.val(commission_financiere);
            checkTextualInput($commission_financiere);
        }
    } else {
        commission_commerciale = parseFloat($commission_commerciale.val());
        commission_financiere = parseFloat($commission_financiere.val());

        if (commission_commerciale > 0)
            commC = Math.round10(commission_commerciale * total / 100, -2);

        if (commission_financiere > 0)
            commF = Math.round10(commission_financiere * (total + commC) / 100, -2);

        if (parseFloat($commC.val()) !== commC) {
            $commC.val(commC);
        }

        if (parseFloat($commF.val()) !== commF) {
            $commF.val(commF);
        }
    }

    total2 = total + commC + commF;

    //on a le deuxieme total

    displayMoneyValue(total, $view.find('#montant_total'));
    displayMoneyValue(total2, $view.find('#montant_total2'));


    //Total loyer calculé
    var totalLoyer = 0;
    var duree = 0;
//    $view.find(".BF_Rent_row").each(function () {
//    });

    //Cout banque
    var taux = 0;
    var coef = 0;
    var coupBanque = 0;
    var posTmp = 10000000;
    var periodicity = 1;
    $view.find(".BF_DemandeRefinanceur_row").each(function () {
        if ($(this).find('input[name="periode2"]').val() == 0
                && $(this).find('[name="status"]').val() == 2) {
            totalLoyer += parseFloat($(this).find('input[name="quantity"]').val()) * parseFloat($(this).find('input[name="amount_ht"]').val());
        }
    });
    $view.find(".BF_DemandeRefinanceur_row").each(function () {
        if ($(this).find('input[name="periode2"]').val() == 0
                && $(this).find('[name="status"]').val() == 2) {
//            if ($(this).data("position") < posTmp) {
            posTmp = $(this).data("position");
            taux = $(this).find("input[name=rate]").val();
            coef = $(this).find("input[name=coef]").val();
            totalLoyerT = parseFloat($(this).find('input[name="quantity"]').val()) * parseFloat($(this).find('input[name="amount_ht"]').val());
            if (totalLoyerT > 0) {
                dureeT = parseFloat($(this).find('input[name="quantity"]').val()) * parseFloat($(this).find('select[name="periodicity"]').val());
                duree += dureeT;

                periodicity = $(this).find('select[name="periodicity"]').val();

                coupBanqueT = 0;
                if (coef > 0)
                    coupBanqueT += (dureeT / periodicity) * total2 * coef / 100 - total2;

                if (taux > 0) {
                    var echoir = ($view.find('input[name="mode_calcul"]').val() == 2);
                    coupBanqueT += calculInteret(total2, dureeT, taux, echoir);
                }
                coupBanque += coupBanqueT * totalLoyerT / totalLoyer;
                console.log(dureeT);
            }
//            }
        }
    });


    displayMoneyValue(totalLoyer, $view.find('#total_loyer'));


    $view.find('#duree_total').html(duree + " mois");


    if (duree == 0)
        duree = $view.find('input[name="duration"]').val();

    displayMoneyValue(coupBanque, $view.find('#cout_banque'));

    //loyer théorique
    displayMoneyValue((coupBanque + total2) / duree * periodicity, $view.find('#loyer_the'));

//alert((coupBanque + total2) / duree    -    220791291.66);
//alert((coupBanque + total2) / duree    -    233681881.07);
//alert((coupBanque + total2) / duree    -    302705845.29);
    //Diff banque demande
    var difBanqFinan = totalLoyer - coupBanque - total2;
    displayMoneyValue(difBanqFinan, $view.find('#dif_banque_demande'), (difBanqFinan < 0) ? "redT" : "");



    //Loyer intermediaire
    var totalLoyI = calculTotal('.BF_RentExcept_row input[name="amount"]');
    displayMoneyValue(totalLoyI, $view.find('#loy_inter'));


    //Frais divers
    var totalFD = calculTotal('.BF_FraisDivers_row input[name="amount"]');
    displayMoneyValue(totalFD, $view.find('#frais_div'));



    //CA Calculé
    var caCalc = commF + totalLoyI + totalFD + difBanqFinan;
    displayMoneyValue(caCalc, $view.find('#ca_calc'), (caCalc < 0) ? "redT" : "");



    //Reste a payé
    var restPaye = total - calculTotal('.BF_FraisFournisseur_row input[name="amount"]');
    displayMoneyValue(restPaye, $view.find('#rest_fact'));
}

function calculInteret(montant, duree, taux, echoir) {
    if (taux == 0)
        return 0;

    if (typeof (echoir) === 'undefined') {
        echoir = true;
    }
    var tauxPM = taux / 100 / 12;
    var moins = 1;
    if (echoir) {
        //duree --;
        //montant = montant - montant/duree * (tauxPM / (1 - Math.pow(1 + tauxPM, -1)))* (1 + taux/100*1.1710415);
//            montant = montant - montant/duree * (tauxPM / (1 - Math.pow(1 + tauxPM, -1)))* (1 + taux/100*1.5473440);
        //moins = 1 + taux/100 * duree / 576;//Pour 48
        //moins = 1 + taux/100 * duree / 432;//Pour 36
        moins = 1 + taux / 100 * 0.083333333333333;//Pour 36
    }

    return ((montant) * (tauxPM / (1 - Math.pow((1 + tauxPM), -(duree)))) * duree) / moins - montant; //calcul du montant avec interet
}

function calculTotal(selecteur) {
    var totalLI = 0;
    $(selecteur).each(function () {
        var val = parseFloat($(this).val());
        if (val > 0)
            totalLI += val;
    });
    return totalLI;
}

function hideShowAvance(view_id, hide) {
    if ($("#ca_calc").length > 0) {
        var $container = $('.BF_Demande_fields_table_montants');
        console.log($('#' + view_id));
        var selecteur = "#montant_total";
        var selecteur2 = "#montant_total, #montant_total2, #duree_total, #cout_banque, #loy_inter, #frais_div, #total_loyer,"
                + "#periodicity_inputContainer, #mode_calcul_inputContainer, #duration_inputContainer,"
                + '[name="periodicity"], [name="mode_calcul"], [name="duration"]';

        var elems = $container.find(selecteur).parent().parent();
        var elemsACacher = $container.find(selecteur2).parent().parent();
        var elem2 = elems.findParentByClass('panel').find(".panel-footer");
        var moreBut = "erreur";

        $("#plusMoinsAvance").remove();
        if (hide == true) {
            elemsACacher.hide();
            moreBut = "onClick='hideShowAvance(\"" + view_id + "\", false)'>Mode Avancé";
        }
        else {
            elemsACacher.show();
            moreBut = "onClick='hideShowAvance(\"" + view_id + "\", true)'>Mode Normal";
        }

        elem2.prepend('<button type="button" id="plusMoinsAvance"  class="btn btn-primary" ' + moreBut + '</button>');
    }
}

// Factures frais: 

function addSelectedElementsToFacture(list_id, id_demande, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#' + list_id);

    if (!$.isOk($list)) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
        return;
    }

    var $selected = $list.find('tbody').find('input.item_check:checked');
    var object_name = $list.data('object_name');

    if (!$selected.length) {
        var msg = '';
        if (object_labels[object_name]['is_female']) {
            msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
        } else {
            msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
        }
        bimp_msg(msg, 'danger', null, true);
    } else {
        var elements = [];

        $selected.each(function () {
            elements.push(parseInt($(this).data('id_object')));
        });
        addElementsToFacture(object_name, id_demande, elements, $button);
    }
}

function addElementsToFacture(object_name, id_demande, elements, $button) {
    // bimpcore/views/js/object.js :
    setObjectAction($button, {
        module: 'bimpfinancement',
        object_name: 'BF_Demande',
        id_object: id_demande
    }, 'addElementsToFacture', {
        object_name: object_name,
        elements: elements
    }, 'add_to_invoice');
}

// Refinanceurs: 

function setRefinanceursListRowsEvents($list) {
    if (!$.isOk($list)) {
        return;
    }

    var $rows = $list.find('tbody.listRows').find('tr.objectListItemRow');

    if ($rows.length) {
        $rows.each(function () {
            if (!parseInt($(this).data('calc_loyer_events_init'))) {
                var $button = $(this).find('span.loyer_calc_btn');
                var $row = $(this);
                if ($button.length) {
                    $row.find('.inputContainer').each(function () {
                        var field_name = $(this).data('field_name');
                        if (field_name) {
                            var $input = $(this).find('[name="' + field_name + '"]');
                            if ($input.length) {
                                $input.change(function () {
                                    reloadRefinanceurLoyerCalc($(this).findParentByClass('objectListItemRow').data('id_object'));
                                });
                            }
                        }
                    });
                }

                $(this).data('calc_loyer_events_init', 1);
            }
        });
    }
}

function reloadRefinanceurLoyerCalc(id_refinanceur) {
    var $row = $('#BF_DemandeRefinanceur_row_' + id_refinanceur);
    if ($.isOk($row)) {
        var new_values = {};
        new_values[id_refinanceur] = {};

        $row.find('.inputContainer').each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                new_values[id_refinanceur][field_name] = getInputValue($(this));
            }
        });

        var $span = $row.find('span.loyer_calc_btn');
        $span.hide().popover('hide');
        var $td = $span.parent('td');
        if (!$td.find('.loading-spin').length) {
            $td.append('<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>');
        }

        BimpAjax('getRefinanceurLoyerCalc', {
            id_refinanceur: id_refinanceur,
            new_values: new_values
        }, null, {
            $td: $td,
            display_success: false,
            display_errors: false,
            display_warnings: false,
            success: function (result, bimpAjax) {
                if (result.span_html) {
                    var $span = bimpAjax.$td.find('span.loyer_calc_btn').popover('destroy');
                    bimpAjax.$td.html(result.span_html);
                    bimpAjax.$td.find('span.loyer_calc_btn').popover();
                }
            },
            error: function (result, bimpAjax) {
                bimpAjax.$td.find('.loading-spin').remove();
            }
        });
    }
}

function majLoyerAuto(elem, montant) {
    elem2 = elem.parent().parent().find("input[name='amount_ht']");
    elem2.val(montant);
    elem2.trigger("keyup");
}

// Commandes fournisseurs:

function onCommandesFournFormLoaded($form) {
    if (!parseInt($form.data('demande_form_events_init'))) {
        $form.find('.fournisseur_container').each(function () {
            $(this).find('input[name="fournisseurs[]"]').change(function () {
                var $container = $(this).findParentByClass('fournisseur_container');
                if ($.isOk($container)) {
                    if ($(this).prop('checked')) {
                        $container.find('.commande_fourn_lines').slideDown(250);
                    } else {
                        $container.find('.commande_fourn_lines').slideUp(250);
                    }
                }
            });
            $(this).find('.commande_fourn_lines > table > tbody > tr').each(function () {
                $(this).find('.fourn_line_check').change(function () {
                    var $row = $(this).parent('td').parent('tr');
                    if ($(this).prop('checked')) {
                        $row.removeClass('deactivated');
                        var qty = $row.find('.qtyInput').data('max');
                        if (qty) {
                            $row.find('.qtyInput').val(qty);
                        }
                    } else {
                        $row.addClass('deactivated');
                        $row.find('.qtyInput').val(0);
                    }
                });
            });
        });

        $form.data('demande_form_events_init', 1);
    }
}

function onCommandesFournFormSubmit($form, extra_data) {
    if ($.isOk($form)) {
        var fourns = [];

        $form.find('.fournisseur_container').each(function () {
            if ($(this).find('input[name="fournisseurs[]"]').prop('checked')) {
                var lines = [];

                $(this).find('.commande_fourn_lines > table > tbody > tr').each(function () {
                    var $check = $(this).find('.fourn_line_check');
                    if ($check.prop('checked')) {
                        var $qty = $(this).find('.qtyInput');
                        lines.push({id_line: $check.val(), qty: $qty.val()});
                    }
                });

                if (lines.length) {
                    fourns.push({
                        id_fourn: $(this).find('input[name="fournisseurs[]"]').val(),
                        lines: lines
                    });
                }
            }
        });

        if (fourns.length) {
            extra_data['commandes_fourn'] = fourns;
        }
    }
    return extra_data;
}

function onBfCommandesFournViewLoaded($view) {
    if ($view.length) {

        $view.find('input.line_qty_input').each(function () {
            if (!parseInt($(this).data('bf_demande_commande_fourn_events_init'))) {
                $(this).change(function () {
                    var $row = $(this).findParentByClass('commande_fourn_element_row');
                    checkBfLineCommandesfournQties($view.attr('id'), $row.data('id_fourn'), $row.data('id_bf_line'));
                });
                $(this).data('bf_demande_commande_fourn_events_init', 1);
            }
        });
    }
}

function checkBfLineCommandesfournQties(view_id, id_fourn, id_line) {
    var $view = $('#' + view_id);
    if ($view.length) {
        var $rows = $view.find('.commande_fourn_element_row.fourn_' + id_fourn + '_line_' + id_line);
        if ($rows.length) {
            var total_initial = 0;
            var total_set = 0;
            var remain_qty = parseFloat($('#fourn_' + id_fourn + '_line_' + id_line + '_remain_qty').val());
            if (isNaN(remain_qty)) {
                remain_qty = 0;
            }
            var qties_set = {};
            var error = false;
            $rows.each(function () {
                var $input = $(this).find('input.line_qty_input');
                if ($input.length) {
                    var initial_qty = parseFloat($input.data('initial_qty'));
                    if (isNaN(initial_qty)) {
                        initial_qty = 0;
                    }
                    var qty_set = parseFloat($input.val());
                    if (isNaN(qty_set)) {
                        $input.val(initial_qty).change();
                        error = true;
                    }
                    var max = parseFloat($input.data('max'));
                    if (isNaN(max)) {
                        max = initial_qty;
                    }
                    if (qty_set > max) {
                        error = true;
                    }
                    total_initial += initial_qty;
                    total_set += qty_set;
                    var id_comm = parseInt($(this).data('id_commande'));
                    qties_set[id_comm] = qty_set;
                }
            });
            if (error) {
                return;
            }
            remain_qty -= (total_set - total_initial);
            if (remain_qty < 0) {
//                bimp_msg('Une incohérence dans les quantités a été détecté.<br/>Les quantités ont été réinitialisées' + ': ' + remain_qty + ', ' + total_set + ', ' + total_initial);
                resetBfLineCommandesFournQties(view_id, id_fourn, id_line);
                return;
            }
            $rows.each(function () {
                var $input = $(this).find('input.line_qty_input');
                if ($input.length) {
                    var id_comm = parseInt($(this).data('id_commande'));
                    var new_max = qties_set[id_comm] + remain_qty;
                    $input.data('max', new_max);
                    $(this).find('span.qty_max_value').text(new_max);
                }
            });
            checkCommandesFournLinesModifs(view_id);
        }
    }
}

function resetBfLineCommandesFournQties(view_id, id_fourn, id_line) {
    var $view = $('#' + view_id);
    if ($view.length) {
        var $rows = $view.find('.commande_fourn_element_row.fourn_' + id_fourn + '_line_' + id_line);
        var remain_qty = parseFloat($('#fourn_' + id_fourn + '_line_' + id_line + '_remain_qty'));
        if (isNaN(remain_qty)) {
            remain_qty = 0;
        }
        if ($rows.length) {
            $rows.each(function () {
                var $input = $(this).find('input.line_qty_input');
                if ($input.length) {
                    var initial_qty = parseFloat($input.data('initial_qty'));
                    if (isNaN(initial_qty)) {
                        initial_qty = 0;
                    }
                    $input.val(initial_qty);

                    var max = (initial_qty + remain_qty);
                    $input.data('max', max);
                    $(this).find('span.qty_max_value').text(max);
                }
            });
        }
    }
}

function checkCommandesFournLinesModifs(view_id) {
    var $view = $('#' + view_id);

    if ($view.length) {
        var has_modif = false;
        $view.find('.fourn_row').each(function () {
            $(this).find('.commande_fourn_element_row').each(function () {
                var is_modif = false;
                var $input = $(this).find('input.line_qty_input');
                if ($input.length) {
                    var initial_qty = parseFloat($input.data('initial_qty'));
                    var qty_set = parseFloat($input.val());
                    if (!isNaN(initial_qty) && !isNaN(qty_set)) {
                        if (qty_set !== initial_qty) {
                            is_modif = true;
                        }
                    }
                }
                if (is_modif) {
                    has_modif = true;
                    $(this).addClass('modified');
                    $(this).find('.cancel_line').removeClass('hidden');
                    $(this).find('.save_line').removeClass('hidden');
                } else {
                    $(this).removeClass('modified');
                    $(this).find('.cancel_line').addClass('hidden');
                    $(this).find('.save_line').addClass('hidden');
                }
            });
        });
        if (has_modif) {
            $view.find('div.commandes_fourn_modif_buttons').stop().slideDown(250);
        } else {
            $view.find('div.commandes_fourn_modif_buttons').stop().slideUp(250);
        }
    }
}

function cancelCommandesFournLinesModifs($button, view_id) {
    var $view = $('#' + view_id);

    if ($view.length) {
        if ($button.hasClass('cancel_line')) {
            var $row = $button.findParentByClass('commande_fourn_element_row');
            if ($row.length) {
                var $input = $row.find('input.line_qty_input');
                if ($input.length) {
                    var initial_qty = parseFloat($input.data('initial_qty'));
                    if (!isNaN(initial_qty)) {
                        $input.val(initial_qty).change();
                    }
                }
            }
        } else {
            $view.find('.fourn_row').each(function () {
                $(this).find('.commande_fourn_element_row').each(function () {
                    var $input = $(this).find('input.line_qty_input');
                    if ($input.length) {
                        var initial_qty = parseFloat($input.data('initial_qty'));
                        if (!isNaN(initial_qty)) {
                            $input.val(initial_qty).change();
                        }
                    }
                });
            });
        }
    }
}

function saveCommandesFournLinesModifs($button, view_id, id_demande) {
    if ($button.hasClass('deactivated')) {
        return;
    }

    var $view = $('#' + view_id);

    if ($view.length) {
        if ($button.hasClass('save_line')) {
            var $row = $button.findParentByClass('commande_fourn_element_row');
            if ($row.length) {

                var $input = $row.find('input.line_qty_input');
                if ($input.length) {
                    var initial_qty = parseFloat($input.data('initial_qty'));
                    var qty_set = parseFloat($input.val());
                    if (!isNaN(initial_qty) && !isNaN(qty_set) && (qty_set !== initial_qty)) {
                        var id_bf_line = parseInt($row.data('id_bf_line'));
                        var id_commande = parseInt($row.data('id_commande'));
                        var new_qties = [{
                                id_commande: id_commande,
                                id_bf_line: id_bf_line,
                                qty: qty_set
                            }];

                        setObjectAction($button, {
                            module: 'bimpfinancement',
                            object_name: 'BF_Demande',
                            id_object: id_demande
                        }, 'setCommandesFournLinesQties', {
                            new_qties: new_qties
                        });
                        return;
                    }
                }
            }
        } else {
            var $rows = $view.find('.commande_fourn_element_row');
            var new_qties = [];
            $rows.each(function () {
                var $input = $(this).find('input.line_qty_input');
                if ($input.length) {
                    var initial_qty = parseFloat($input.data('initial_qty'));
                    var qty_set = parseFloat($input.val());
                    if (!isNaN(initial_qty) && !isNaN(qty_set) && (qty_set !== initial_qty)) {
                        var id_bf_line = parseInt($(this).data('id_bf_line'));
                        var id_commande = parseInt($(this).data('id_commande'));
                        new_qties.push({
                            id_commande: id_commande,
                            id_bf_line: id_bf_line,
                            qty: qty_set
                        });
                    }
                }
            });
            if (new_qties.length) {
                setObjectAction($button, {
                    module: 'bimpfinancement',
                    object_name: 'BF_Demande',
                    id_object: id_demande
                }, 'setCommandesFournLinesQties', {
                    new_qties: new_qties
                });
                return;
            } else  {
                bimp_msg('Aucune quantité à mettre à jour trouvée', 'warning', null, true);

            }
        }
    }
    $button.removeClass('deactivated');
}

function toggleBfCommandeFournDetailDisplay($button) {
    if ($.isOk($button)) {
        var $row = $button.parent('td').parent('tr');
        if ($row.length) {
            var $next = $row.next();
            if ($next.length && $next.hasClass('commande_fourn_elements_rows')) {
                if ($next.css('display') === 'none') {
                    $next.stop().slideDown(250);
                    $button.find('i.iconRight').attr('class', 'fas fa5-caret-up iconRight');
                } else {
                    $next.stop().slideUp(250);
                    $button.find('i.iconRight').attr('class', 'fas fa5-caret-down iconRight');
                }
            }
        }
    }
}