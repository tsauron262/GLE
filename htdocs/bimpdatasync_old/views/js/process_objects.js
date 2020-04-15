var ajaxRequestsUrl = './ajax.php';

function executeObjectProcess($button, process_action, id_process, object_name, id_object) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var data = {
        'ajaxRequestsUrl': ajaxRequestsUrl,
        'id_process': id_process,
        'process_action': process_action,
        'object_name': object_name,
        'id_object': id_object
    };

    var $resultContainer = $('#' + object_name + '_' + id_object + '_ajaxResult');
    $resultContainer.parent('td').parent('tr').show();

    bimp_json_ajax('executeObjectProcess', data, $resultContainer, function (result) {
        displayObjectProcessResult($resultContainer, result);
        $button.removeClass('disabled');
    }, function (result) {
        $button.removeClass('disabled');
    });
}

function bulkExecuteObjectProcess($buttons, objects, index, data) {
    if (index >= objects.length) {
        $buttons.each(function () {
            $(this).removeClass('disabled');
        });
    }
    data['id_object'] = objects[index];
    var $resultContainer = $('#' + data['object_name'] + '_' + objects[index] + '_ajaxResult');
    bimp_json_ajax('executeObjectProcess', data, $resultContainer, function (result) {
        displayObjectProcessResult($resultContainer, result);
        index++;
        bulkExecuteObjectProcess($buttons, objects, index, data);
    }, function (result) {
        index++;
        bulkExecuteObjectProcess($buttons, objects, index, data);
    });
}

function executeSelectedObjectProcess(process_action, id_process, object_name) {
    var $buttons = $('.bulkActionButton');
    $buttons.each(function () {
        if ($(this).hasClass('disabled')) {
            return;
        }
        $(this).addClass('disabled');
    });
    
    $('.objectAjaxResult').each(function() {
        $(this).html('').parent('td').parent('tr').hide();
    });

    var $inputs = $('input.' + object_name + '_check');
    if (!$inputs.length) {
        return;
    }

    var objects = [];

    $buttons.each(function () {
        $(this).addClass('disabled');
    });

    $inputs.each(function () {
        if ($(this).prop('checked')) {
            var $row = $(this).parent('td').parent('tr');
            var id_object = parseInt($row.data('id_object'));
            objects.push(id_object);
        }
    });

    var html = '<p class="alert alert-info">En attente de traitement</p>';
    for (var i in objects) {
        $('#' + object_name + '_' + objects[i] + '_ajaxResult').html(html).parent('td').parent('tr').show();
    }

    var data = {
        'ajaxRequestsUrl': ajaxRequestsUrl,
        'id_process': id_process,
        'process_action': process_action,
        'object_name': object_name
    };

    bulkExecuteObjectProcess($buttons, objects, 0, data);
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

function toggleObjectListCheck(object_name, $input) {

    var $inputs = $('.' + object_name + '_check');
    if ($input.prop('checked')) {
        $inputs.each(function () {
            $(this).prop('checked', 1);
        });
    } else {
        $inputs.each(function () {
            $(this).prop('checked', 0);
        });
    }
}
