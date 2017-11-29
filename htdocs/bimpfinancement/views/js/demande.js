function onBFDemandeViewLoaded(id_demande) {
    var view_id = 'BF_Demande_' + id_demande + '_default_view';
    var $view = $('#' + view_id);

    if ($view.length) {
        $view.find('[name="montant_materiels"]').change(function () {
            calculateMontantTotal($view);
        });
        $view.find('[name="montant_services"]').change(function () {
            calculateMontantTotal($view);
        });
        $view.find('[name="montant_logiciels"]').change(function () {
            calculateMontantTotal($view);
        });
        calculateMontantTotal($view);
    }

    var $list = $('#BF_Rent_default_list');
    $list.on('listRefresh', function () {
        // ...
    });
}

function calculateMontantTotal($view) {
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
    displayMoneyValue(total, $view.find('#montant_total'));
}