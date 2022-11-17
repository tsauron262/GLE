function BimpFinancement() {
    this.calculateMontantLoyerHT = function ($btn, id_demande_refin) {
        var data = {
            id_demande_refin: id_demande_refin,
            id_demande: 0,
            id_refinanceur: 0,
            qty: 0,
            periodicity: 0,
            rate: 0
        };

        var $resultInput = null;

        if ($.isOk($btn)) {
            var $inputContainer = $btn.findParentByClass('inputContainer');
            if ($.isOk($inputContainer)) {
                $resultInput = $inputContainer.find('input[name="amount_ht"]');
            }

            var $form = $btn.findParentByClass('BF_DemandeRefinanceur_form');

            if ($.isOk($form)) {
                var $input = $form.find('input[name="id_demande"]');
                if ($input.length) {
                    data.id_demande = parseInt($input.val());
                }

                $input = $form.find('input[name="id_refinanceur"]');
                if ($input.length) {
                    data.id_refinanceur = parseInt($input.val());
                }

                $input = $form.find('input[name="periodicity"]');
                if ($input.length) {
                    data.periodicity = parseInt($input.val());
                }

                $input = $form.find('input[name="qty"]');
                if ($input.length) {
                    data.qty = parseInt($input.val());
                }

                $input = $form.find('input[name="rate"]');
                if ($input.length) {
                    data.rate = parseFloat($input.val());
                }
            }
        }

        if (!$resultInput) {
            bimp_msg('Champ absent', 'danger');
            return;
        }

        $btn.parent().find('.calc_loading').show();
        BimpAjax('calculateMontantLoyerHT', data, null, {
            $btn: $btn,
            $resultInput: $resultInput,
            url: dol_url_root + '/bimpfinancement/index.php?fc=demande',
            display_success: false,
            display_processing: false,
            success: function (result, bimpAjax) {
                bimpAjax.$btn.parent().find('.calc_loading').hide();
                bimpAjax.$resultInput.val(result.amount_ht).change();
            }
        });
    };
}

var BimpFinancement = new BimpFinancement();