function onBFDemandeViewLoaded(id_demande) {
    var view_id = 'BF_Demande_' + id_demande + '_default_view';
    var $view = $('#' + view_id);

    if ($view.length) {
        initEvents($view);
        $("body").on("listRefresh", function(event) {
            initEvents($view);
        });
        calculateMontantTotal($view);
    }
}


function initEvents($view){
    var selecteur = '[name="montant_materiels"], [name="montant_services"], [name="montant_logiciels"],'+
            '[name="commission_commerciale"], [name="commission_financiere"],'+
            '[name="amount"]';
    $view.find(selecteur).change(function () {
        calculateMontantTotal($view);
    });
    $view.find(selecteur).keyup(function () {
        calculateMontantTotal($view);
    });
    
}

function calculateMontantTotal($view) {
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
    if(commission_commerciale > 0)
        commC = commission_commerciale * total / 100;
    
    var commF = 0;
    if(commission_financiere > 0)
        commF = commission_financiere * (total + commC) / 100;
    
    total2 = total + commC + commF;
    
    //on a le deuxieme total
    
    displayMoneyValue(total, $view.find('#montant_total'));
    displayMoneyValue(total2, $view.find('#montant_total2'));
    
    if($view.find('#commC').length < 1)
        $commission_commerciale.parent().parent().parent().append("<span id='commC'></span>");
    displayMoneyValue(commC, $view.find('#commC'));
    
    if($view.find('#commF').length < 1)
        $commission_financiere.parent().parent().parent().append("<span id='commF'></span>");
    displayMoneyValue(commF, $view.find('#commF'));
    
    
    
    
    //Loyer intermediaire
    var totalLoyI = calculTotal('.BF_RentExcept_row input[name="amount"]');
    displayMoneyValue(totalLoyI, $view.find('#loy_inter'));
    
    
    //Frais divers
    var totalFD = calculTotal('.BF_FraisDivers_row input[name="amount"]');
    displayMoneyValue(totalFD, $view.find('#frais_div'));
    
    
    
    //CA Calculé
    var caCalc = commF + totalLoyI + totalFD;
    displayMoneyValue(caCalc, $view.find('#ca_calc'));
    
    
    //Reste a payé
    var restPaye = total - calculTotal('.BF_FraisFournisseur_row input[name="amount"]');;
    displayMoneyValue(restPaye, $view.find('#rest_fact'));
    
    
}


function calculTotal(selecteur){
    var totalLI = 0;
    $(selecteur).each(function(){
        var val = parseFloat($(this).val());
        if(val > 0)
            totalLI += val;
    });
    return totalLI;
}