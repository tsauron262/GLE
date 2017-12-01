// Gestion des événements:

function onViewslistLoaded($viewsList) {
    setCommonEvents($('#' + $viewsList.attr('id') + '_container'));
}

$(document).ready(function () {
    $('.objectViewslist').each(function () {
        onViewslistLoaded($(this));
    });
});