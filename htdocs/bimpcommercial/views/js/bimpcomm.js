
function onCheckPaFactureFormSubmit($form, extra_data) {
    if ($.isOk($form)) {
        var $inputs = $form.find('[name="lines[]"]:checked');

        var lines = [];
        $inputs.each(function () {
            var $row = $(this).findParentByClass('lineRow');
            if ($.isOk($row)) {
                lines.push({
                    id_line: parseInt($row.data('id_line')),
                    new_pa: parseFloat($row.data('new_pa'))
                });
            }
        });

        extra_data['lines'] = lines;
    }

    return extra_data;
}


function onFactureFormSubmit($form, extra_data) {
    var lines = [];

    var $rows = $form.find('.facture_lines_inputContainer').find('tr.facture_line');

    $rows.each(function () {
        var id_line = parseInt($(this).data('id_line'));
        if ($(this).hasClass('text_line')) {
            var include = parseInt($(this).find('.include_line').val());
            lines.push({
                id_line: id_line,
                qty: include,
                equipments: []
            });
        } else {
            var $qty_input = $(this).find('input.line_facture_qty');
            var qty = 0;
            if ($qty_input.length) {
                qty = parseFloat($qty_input.val());
            }

            var $periods_input = $(this).find('input.line_facture_periods');
            var periods = 0;
            if ($periods_input) {
                periods = parseInt($periods_input.val());
            }

            var $paEditableInput = $(this).find('input.line_facture_pa_editable');
            var pa_editable = 1;
            if ($paEditableInput.length) {
                pa_editable = parseInt($paEditableInput.val());
            }

            var equipments = [];
            var $row = $form.find('#facture_line_' + id_line + '_equipments');
            if ($row.length) {
                $row.find('.check_list_item_input:checked').each(function () {
                    equipments.push(parseInt($(this).val()));
                });
            }

            lines.push({
                id_line: id_line,
                qty: qty,
                periods: periods,
                pa_editable: pa_editable,
                equipments: equipments
            });
        }
    });

    extra_data['lines'] = lines;
    return extra_data;
}


