function onPackageViewLoaded($view) {
    if ($.isOk($view)) {
        var $input = $view.find('input[name="search_serial"]');

        if ($input.length) {
            if (!parseInt($input.data('package_view_events_init'))) {
                $input.keydown(function (e) {
                    if (e.key === 'Tab' || e.key === 'Enter') {
                        var $btn = $(this).findParentByClass('singleLineFormContent').find('#addPackageEquipmentButton');
                        if ($.isOk($btn)) {
                            $btn.click();
                        } else {
                            bimp_msg('Une erreur est survenue (conteneur absent)', 'danger');
                        }
                    }
                });
                $input.data('package_view_events_init', 1);
            }
        }
    }
}

function addPackageEquipment($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $container = $button.findParentByClass('packageAddEquipmentForm');

    if ($.isOk($container)) {
        var $resultContainer = $container.find('.quickAddForm_ajax_result');
        var $input = $container.find('input[name="search_serial"]');

        if ($.isOk($input)) {
            var id_package = parseInt($container.data('id_package'));
            var serial = $input.val();

            if (!serial) {
                bimp_msg('Veuillez saisir un numéro de série', 'warning', $resultContainer);
            } else {
                $input.val('');

                setObjectAction($button, {
                    'module': 'bimpequipment',
                    'object_name': 'BE_Package',
                    'id_object': id_package
                }, 'addEquipment', {
                    'serial': serial
                }, '', $resultContainer, function () {
                    var $list = $container.findParentByClass('BE_Package_view_default').find('.Equipment_list_table_package');
                    if ($.isOk($list)) {
                        reloadObjectList($list.attr('id'));
                    }
                }, null, null, true);
            }

            return;
        }
    }

    bimp_msg('Une erreur est survenue. Opération abandonnée');
}

function addPackageProduct($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $container = $button.findParentByClass('packageAddProductForm');

    if ($.isOk($container)) {
        var id_product = 0;
        var qty = 0;
        var id_entrepot = 0;
        var $resultContainer = $container.find('.quickAddForm_ajax_result');

        var $input = $container.find('input[name="id_product"]');
        if ($input.length) {
            id_product = parseInt($input.val());
            if (!id_product) {
                bimp_msg('Veuillez sélectionner un produit', 'warning', $resultContainer);
                return;
            }
            $input.val(0).change();
            $container.find('input[name="search_id_product"]').val('');
        } else {
            bimp_msg('Erreur: champ "produit" non trouvé', 'danger');
            return;
        }

        $input = $container.find('input[name="qty_product"]');
        if ($input.length) {
            qty = parseInt($input.val());
            if (!qty) {
                bimp_msg('Veuillez indiquer une quantité supérieure à 0', 'warning', $resultContainer);
                return;
            }
            $input.val(1).change();
        } else {
            bimp_msg('Erreur: champ "quantité" non trouvé', 'danger');
            return;
        }

        $input = $container.find('[name="id_entrepot_src"]');
        if ($input.length) {
            id_entrepot = parseInt($input.val());
        }

        var id_package = parseInt($container.data('id_package'));

        setObjectAction($button, {
            'module': 'bimpequipment',
            'object_name': 'BE_Package',
            'id_object': id_package
        }, 'addProduct', {
            'id_product': id_product,
            'qty': qty,
            'id_entrepot': id_entrepot
        }, '', $resultContainer, function () {
            var $list = $container.findParentByClass('BE_Package_view_default').find('.BE_PackageProduct_list_table');
            if ($.isOk($list)) {
                reloadObjectList($list.attr('id'));
            }
        }, null, null, true);

        return;
    }

    bimp_msg('Une erreur est survenue. Opération abandonnée');
}

function removeSelectedEquipmentsFromPackage(list_id, $button, ) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
        return;
    }

    var id_package = 0;
    var $packageView = $list.findParentByClass('BE_Package_view');
    if ($.isOk($packageView)) {
        id_package = parseInt($packageView.data('id_object'));
    }

    if (!id_package) {
        bimp_msg('Erreur technique: ID du package absent', 'danger', null, true);
        return;
    }

    var $selected = $list.find('tbody').find('input.item_check:checked');

    if (!$selected.length) {
        bimp_msg('Aucun équipement sélectionné', 'warning', null, true);
        return;
    }

    var equipments_list = [];

    $selected.each(function () {
        equipments_list.push($(this).data('id_object'));
    });

    setObjectAction($button, {
        'module': 'bimpequipment',
        'object_name': 'BE_Package',
        'id_object': id_package
    }, 'removeEquipment', {
        'equipments': equipments_list
    }, 'remove_equipment', null, function () {
        reloadObjectList($list.attr('id'));
    }, null, null, true);
}

function removeSelectedProductsFromPackage(list_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
        return;
    }

    var id_package = 0;
    var $packageView = $list.findParentByClass('BE_Package_view');
    if ($.isOk($packageView)) {
        id_package = parseInt($packageView.data('id_object'));
    }

    if (!id_package) {
        bimp_msg('Erreur technique: ID du package absent', 'danger', null, true);
        return;
    }

    var $selected = $list.find('tbody').find('input.item_check:checked');

    if (!$selected.length) {
        bimp_msg('Aucun produit sélectionné', 'warning', null, true);
        return;
    }

    var packageProducts = [];

    $selected.each(function () {
        packageProducts.push($(this).data('id_object'));
    });

    setObjectAction($button, {
        'module': 'bimpequipment',
        'object_name': 'BE_Package',
        'id_object': id_package
    }, 'removeProduct', {
        'packageProducts': packageProducts
    }, 'remove_product', null, function () {
        reloadObjectList($list.attr('id'));
    }, null, null, true);
}

$(document).ready(function () {
    $('body').on('viewLoaded', function (e) {
        if (e.$view.hasClass('BE_Package_view_default')) {
            onPackageViewLoaded(e.$view);
        }
    });
    $('body').on('viewRefresh', function (e) {
        if (e.$view.hasClass('BE_Package_view_default')) {
            onPackageViewLoaded(e.$view);
        }
    });
});