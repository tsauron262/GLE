function closeInterView($button) {
//    var msg = 'Attention, la durée indiquée par le chronomètre ne sera pas automatiquement enregistrée.' + "\n";
//    msg += 'Souhaitez-vous quand même fermer cette fiche?';
//    if (confirm(msg)) {
    var view_id = $button.data('view_id');
    if (view_id) {
        var $view = $('#' + view_id);
        $view.slideUp(250, function () {
            $(this).remove();
        });
    }
//    }
}