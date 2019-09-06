function onPackageViewLoaded($view) {

}

function addPackageEquipment($button) {

}

function addPackageProduct($button) {

}

function removeEquipmentFromPackage(id_equipment) {
    
}

function removeSelectedEquipmentsFromPackage(list_id, $button) {

}

$(document).ready(function () {
    $('body').on('viewloaded', function (e) {
        if (e.$view.hasClass('')) {
            onPackageViewLoaded(e.$view);
        }
    });
    $('body').on('viewRefresh', function (e) {
        if (e.$view.hasClass('')) {
            onPackageViewLoaded(e.$view);
        }
    });
});