$(document).ready(function () {
    $('body').on('viewLoaded', function (e) {
        if (e.$view.hasClass('BF_Demande_view')) {
            onBFDemandeViewLoaded(e.$view);
        }
    });
    $('body').on('viewRefresh', function (e) {
        if (e.$view.hasClass('BF_Demande_view')) {
            onBFDemandeViewLoaded(e.$view);
        }
    });
});

function onBFDemandeViewLoaded($view) {
    if ($view.length) {
        initEvents($view);
        $("body").on("listRefresh", function (event) {
            initEvents($view);
        });
    }
    hideShowAvance(true);
}

function initEvents($view) {
    var selecteur = '[name="montant_materiels"], [name="montant_services"], [name="montant_logiciels"],' +
            '[name="commission_commerciale"], [name="commission_financiere"],' +
            '[name="quantity"], [name="amount"], [name="amount_ht"],' +
            '[name="mode_calcul"],' +
            '[name="rate"], [name="coef"],' +
            '[name="periodicity"]';
    $view.find(selecteur).change(function () {
        calculateMontantTotal($view);
    });
    $view.find(selecteur).keyup(function () {
        calculateMontantTotal($view, this);
    });
    calculateMontantTotal($view);
}

function calculateMontantTotal($view, champ) {
    if(champ != undefined)
    $(champ).val($(champ).val().replace(",", ".").replace(" ", "").replace("€", ""));
    
    if (!$view.length) {
        return;
    }

    var $montant_materiels = $view.find('[name="montant_materiels"]');
    var $montant_services = $view.find('[name="montant_services"]');
    var $montant_logiciels = $view.find('[name="montant_logiciels"]');

    var $commission_commerciale = $view.find('[name="commission_commerciale"]');
    var $commission_financiere = $view.find('[name="commission_financiere"]');

    var montant_materiels = parseFloat($montant_materiels.val());
    var montant_services = parseFloat($montant_services.val());
    var montant_logiciels = parseFloat($montant_logiciels.val());

    var commission_commerciale = parseFloat($commission_commerciale.val());
    var commission_financiere = parseFloat($commission_financiere.val());

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

    //On a le premier total

    var commC = 0;
    if (commission_commerciale > 0)
        commC = commission_commerciale * total / 100;

    var commF = 0;
    if (commission_financiere > 0)
        commF = commission_financiere * (total + commC) / 100;

    total2 = total + commC + commF;

    //on a le deuxieme total

    displayMoneyValue(total, $view.find('#montant_total'));
    displayMoneyValue(total2, $view.find('#montant_total2'));

    if ($view.find('input[name="commC"]').length < 1)
        $commission_commerciale.parent().parent().parent().append("<span id='commC'></span>");
    displayMoneyValue(commC, $view.find('#commC'));

    if ($view.find('input[name="commF"]').length < 1)
        $commission_financiere.parent().parent().parent().append("<span id='commF'></span>");
    displayMoneyValue(commF, $view.find('#commF'));



    //Total loyer calculé
    var totalLoyer = 0;
    var duree = 0;
    $view.find(".BF_Rent_row").each(function () {
        totalLoyer += parseFloat($(this).find('input[name="quantity"]').val()) * parseFloat($(this).find('input[name="amount_ht"]').val());
        duree += parseFloat($(this).find('input[name="quantity"]').val()) * parseFloat($(this).find('select[name="periodicity"]').val());
    });
    displayMoneyValue(totalLoyer, $view.find('#total_loyer'));


    $view.find('#duree_total').html(duree+" mois");
    
    
    if(duree == 0)
        duree = $view.find('input[name="duration"]').val();

    //Cout banque
    var taux = 0;
    var coef = 0;
    var posTmp = 10000000;
    $view.find(".BF_Refinanceur_row").each(function () {
        if ($(this).data("position") < posTmp) {
            posTmp = $(this).data("position");
            taux = $(this).find("input[name=rate]").val();
            coef = $(this).find("input[name=coef]").val();
        }
    });
    if (coef < 1)
        coef = 1;

    var echoir = ($view.find('input[name="mode_calcul"]').val() == 2);
    var periodicity = $view.find('input[name="periodicity"]').val();
    var interet = calculInteret(total2, duree, taux, echoir);

    var coupBanque = (total2 * coef) - total2 + interet;

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
    if(taux == 0)
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


function hideShowAvance(hide){
    if($("#ca_calc").length > 0){
        var selecteur = "#montant_total, #montant_total2, #duree_total, #cout_banque, #loy_inter, #frais_div, #total_loyer,"
                +"#periodicity_inputContainer, #mode_calcul_inputContainer, #duration_inputContainer,"
                +'[name="periodicity"], [name="mode_calcul"], [name="duration"]';
        var elems = $(selecteur).parent().parent();
        var elem2 = elems.parent().parent().find(".btn-primary").parent();
        var moreBut = "erreur";

        $("#plusMoinsAvance").remove();
        if(hide == true){
            elems.hide();
            moreBut = "onClick='hideShowAvance(false)'>Mode Avancé";
        }
        else{
            elems.show();
            moreBut = "onClick='hideShowAvance(true)'>Mode Normal";
        }

        elem2.prepend('<button type="button" id="plusMoinsAvance"  class="btn btn-primary" '+moreBut+'</button>');
    }
}
