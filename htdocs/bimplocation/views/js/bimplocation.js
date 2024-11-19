function BimpLocation() {
    this.onNewFactureFormSubmit = function ($form, extra_data) {
        var lines = [];

        $form.find('input.line_check').each(function () {
            if ($(this).prop('checked')) {
                var id_line = parseInt($(this).data('id_line'));

                if (id_line) {
                    lines.push(id_line);
                }
            }
        });

        extra_data['lines'] = lines;
        return extra_data;
    };
}

var BimpLocation = new BimpLocation();