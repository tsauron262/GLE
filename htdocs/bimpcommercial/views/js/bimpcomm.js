
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