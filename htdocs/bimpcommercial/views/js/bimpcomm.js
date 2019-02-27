
function addCommandeFournLineReceptionRow($button, id_line) {
    var $container = $button.findParentByClass('line_reception_rows');

    if (!$.isOk($container) || parseInt($container.data('id_line')) !== id_line) {
        bimp_msg('une erreur est survenue');
        return;
    }

    var $tpl = $container.find('.line_reception_row_tpl');
    var idx = parseInt($tpl.data('next_idx'));

    var html = '<tr class="line_' + id_line + 'reception_row line_reception_row" data-idx="' + idx + '">';
    var tpl_html = $tpl.html().replace(/receptionidx/g, idx);
    tpl_html = tpl_html.replace(/linetotalmaxinputclass/g, 'line_' + id_line + '_reception_max');
    html += tpl_html;
    html += '<td style="text-align: right">';
    html += '<span class="rowButton" onclick="removeCommandeFournLineReceptionRow($(this), ' + id_line + ');">';
    html += '<i class="fas fa5-trash"></i></span></td>';
    html += '</tr>';

    $container.find('tbody.receptions_rows').append(html);
    $tpl.data('next_idx', idx + 1);

    setInputsEvents($container);

    checkTotalMaxQtyInput($container.find('input[name="line_' + id_line + '_reception_' + idx + '_qty"]'));
}

function removeCommandeFournLineReceptionRow($button, id_line) {
    var $container = $button.findParentByClass('line_reception_rows');

    if (!$.isOk($container)) {
        bimp_msg('une erreur est survenue');
        return;
    }

    $button.parent('td').parent('tr').remove();
    checkTotalMaxQtyInput($container.find('input[name="line_' + id_line + '_reception_1_qty"]'));
}