// Gestion des événements:

function onViewsListLoaded($viewsList) {
    setCommonEvents($('#' + $viewsList.attr('id') + '_container'));
    
//    $('body').on('objectChange', function (e) {
//        if ((e.module === module) && (e.object_name === object_name)) {
//            reloadObjectView($view.attr('id'));
//        }
//    });
}

$(document).ready(function () {
    $('.objectViewslist').each(function () {
        onViewsListLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.objectForm').each(function () {
                onViewsListLoaded($(this));
            });
        }
    });
});