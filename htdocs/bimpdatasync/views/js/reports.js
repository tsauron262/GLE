function setReportEvents() {
    $('#reportRowsFilter').change(function () {
        var typeClass = $(this).val();
        $('.reportRowsContainer').find('tbody').find('tr.reportRow').each(function () {
            if ((typeClass === 'all') || (typeClass === $(this).data('msg_type'))) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
}

function filterReportsList() {
    var id_process = $('#processesToDisplay').val();
    var type = $('#typesToDisplay').val();

    var selected = $('#reportToLoad').val();
    if (!selected) {
        selected = '';
    }
    $('#reportToLoad').find('option').each(function () {
        var show = true;
        if (id_process !== 'all') {
            if (parseInt(id_process) !== parseInt($(this).data('id_process'))) {
                show = false;
            }
        }
        if (type !== 'all') {
            if (type !== $(this).data('type')) {
                show = false;
            }
        }

        if (show) {
            if (selected === '') {
                selected = $(this).val();
                $('#reportToLoad').val(selected);
            }
            $(this).show();
        } else {
            if (selected === $(this).val()) {
                $('#reportToLoad').val('');
                selected = '';
            }
            $(this).hide();
        }
    });
}

$(document).ready(function () {
    $('#processesToDisplay').add('#typesToDisplay').change(function () {
        filterReportsList();
    });
    setReportEvents();
});