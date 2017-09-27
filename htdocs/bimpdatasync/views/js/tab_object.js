var ajaxRequestsUrl = '../ajax.php';

function executeObjectProcess($button, process_action, id_process, object_name, id_object) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    data = {
        'ajaxRequestsUrl': ajaxRequestsUrl,
        'id_process': id_process,
        'process_action': process_action,
        'object_name': object_name,
        'id_object': id_object
    };

    var $resultContainer = $('#process_' + id_process + '_ajaxResult');
    $resultContainer.parent('td').parent('tr').show();

    bimp_json_ajax('executeObjectProcess', data, $resultContainer, function (result) {
        displayObjectProcessResult($resultContainer, result);
        $button.removeClass('disabled');
    }, function (result) {
        $button.removeClass('disabled');
    });
}

function displayObjectProcessResult($container, result) {
    if (typeof (result.report_rows) !== 'undefined') {
        var rows = result.report_rows;
        var html = '';
        for (var i in rows) {
            html += '<p class="alert alert-' + rows[i].type + '">';
            html += rows[i].msg;
            html += '</p>';
        }
        $container.stop().slideUp(function () {
            $(this).html(html).slideDown(250);
        });
    }
}