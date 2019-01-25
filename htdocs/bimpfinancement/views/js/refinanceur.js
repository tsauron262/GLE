function addNewCoefsPeriodRange($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var new_period = parseInt($('#newCoefsPeriodRangeForm').find('[name="new_period_range"]').val());

    if (isNaN(new_period) || !new_period) {
        bimp_msg('veuillez entrer une valeur valide (entier positif)', 'danger');
        return;
    }

    $button.addClass('disabled');

    var $tables = $('#coefs_ranges_content').find('table.coefsRangesTable');
    $tables.each(function () {
        var $table = $(this);
        var $cols = $table.find('thead').find('th');
        $cols.each(function () {
            var $col = $(this);
            var period = $col.data('period');
            if (period !== 'last') {
                period = parseInt(period);
            }

            if (new_period === period) {
                bimp_msg('Cette tranche de durée existe déjà', 'danger');
                $button.removeClass('disbaled');
                return;
            }
            
            if (new_period < period) {
                $col.before('<th ');
            }
        });
    });
}

function addNewCoefsAmountRange($button) {
}

function cancelCoefModifs($button) {

}

function saveCoefModifs($button) {

}