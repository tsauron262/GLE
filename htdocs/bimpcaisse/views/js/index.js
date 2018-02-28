function BC_Vente() {

    this.reset = function () {
        this.id_vente = 0;
        this.id_client = 0;
        this.nb_articles = 0;
        this.total_ttc = 0;
        this.total_ht = 0;
        this.toPay = 0;
        this.toReturn = 0;
    };

    this.ajaxResult = function (result) {
        if (typeof (result.vente_data) === 'undefined') {
            return;

        }

        if (typeof (result.vente_data.id_vente) !== 'undefined') {
            this.id_vente = result.vente_data.id_vente;
        }

        if (typeof (result.vente_data.nb_articles) !== 'undefined') {
            this.nb_articles = result.vente_data.nb_articles;
            var html = this.nb_articles + ' article';
            if (this.nb_articles > 1) {
                html += 's';
            }
            $('#venteNbArticles').html(html);
        }

        if (typeof (result.vente_data.total_ttc) !== 'undefined') {
            this.total_ttc = result.vente_data.total_ttc;

            displayMoneyValue(this.total_ttc, $('#ventePanierTotal').find('span'));
        }

        if (typeof (result.vente_data.total_ht) !== 'undefined') {
            this.total_ht = result.vente_data.total_ht;
        }

        if (typeof (result.vente_data.toPay) !== 'undefined') {
            this.toPay = result.vente_data.toPay;
            displayMoneyValue(this.toPay, $('#venteToPay').find('span'));
            $('#ventePaiementMontant').val(this.toPay);
        }

        if (typeof (result.vente_data.toReturn) !== 'undefined') {
            this.toReturn = result.vente_data.toReturn;
            displayMoneyValue(this.toReturn, $('#venteToReturn').find('span'));
        }

        if (typeof (result.vente_data.articles) !== 'undefined') {
            for (var i in result.vente_data.articles) {
                var article = result.vente_data.articles[i];
                var $line = $('#cart_article_' + article.id_article);
                if ($line.length) {
                    var $input = $line.find('.article_qty_input');
                    if (article.qty && $input.length) {
                        $input.val(article.qty);
                    }

                    displayMoneyValue(article.total_ttc, $line.find('.product_total_price'));
                }
            }
        }
        if (this.toReturn > 0) {
            $('#venteToReturn').slideDown(250);
            $('#venteToPay').slideUp(250);
        } else {
            $('#venteToReturn').slideUp(250);
            $('#venteToPay').slideDown(250);
        }

        if (this.nb_articles > 0 && this.toPay <= 0) {
            $('#validateCurrentVenteButton').removeClass('disabled');
            $('#saveCurrentVenteButton').addClass('disabled');
        } else {
            $('#validateCurrentVenteButton').addClass('disabled');
            $('#saveCurrentVenteButton').removeClass('disabled');
        }
    };

    this.reset();
}

var Vente = new BC_Vente();

function openCaisse($button, confirm_fonds) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $form = $('#openCaisseForm');

    if (!$form.length) {
        bimp_msg('Une erreur est survenue (Formulaire absent). Opération impossible.', 'danger');
        $button.removeClass('disabled');
        return;
    }

    var id_caisse = parseInt($form.find('[name="id_caisse"]').val());
    if (!id_caisse) {
        bimp_msg('Veuillez sélectionner une caisse', 'warning');
        $button.removeClass('disabled');
        return;
    }

    var fonds = parseFloat($form.find('[name="fonds"]').val());
    if (!fonds) {
        bimp_msg('Veuillez indiquer le montant du fonds de caisse', 'warning');
        $button.removeClass('disabled');
        return;
    }

    BimpAjax('openCaisse', {
        id_caisse: id_caisse,
        fonds: fonds,
        confirm_fonds: confirm_fonds
    }, $('#openCaisseForm').find('.freeFormAjaxResult'), {
        display_success: false,
        append_html: true,
        $button: $button,
        success: function (result, bimpAjax) {
            if (typeof (result.need_confirm_fonds) !== 'undefined' && result.need_confirm_fonds) {
                bimpAjax.$button.html('<i class="fa fa-check iconLeft"></i>Confirmer');
                bimpAjax.$button.attr('onclick', 'openCaisse($(this), 1);');
                bimpAjax.$button.removeClass('disabled');
            } else {
                window.location = document.location.href.replace(document.location.search, "");
            }
        }, error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

function closeCaisse($button, confirm_fonds) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $form = $('#closeCaisseForm');

    if (!$form.length) {
        bimp_msg('Une erreur est survenue (Formulaire absent). Opération impossible.', 'danger');
        $button.removeClass('disabled');
        return;
    }

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());
    if (!id_caisse) {
        bimp_msg('Erreur: aucune caisse active', 'danger');
        $button.removeClass('disabled');
        return;
    }

    var fonds = parseFloat($form.find('[name="fonds"]').val());
    if (!fonds) {
        bimp_msg('Veuillez indiquer le montant du fonds de caisse', 'warning');
        $button.removeClass('disabled');
        return;
    }

    BimpAjax('closeCaisse', {
        id_caisse: id_caisse,
        fonds: fonds,
        confirm_fonds: confirm_fonds
    }, $form.find('.freeFormAjaxResult'), {
        display_success: false,
        append_html: true,
        $button: $button,
        success: function (result, bimpAjax) {
            if (typeof (result.need_confirm_fonds) !== 'undefined' && result.need_confirm_fonds) {
                bimpAjax.$button.html('<i class="fa fa-check iconLeft"></i>Confirmer');
                bimpAjax.$button.attr('onclick', 'closeCaisse($(this), 1);');
                bimpAjax.$button.removeClass('disabled');
            } else {
                window.location = document.location.href.replace(document.location.search, "");
            }
        }, error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

function changeUser($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $form = $('#changeUserForm');

    if (!$form.length) {
        bimp_msg('Une erreur est survenue (Formulaire absent). Opération impossible.', 'danger');
        $button.removeClass('disabled');
        return;
    }

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());
    if (!id_caisse) {
        bimp_msg('Erreur: aucune caisse active', 'danger');
        $button.removeClass('disabled');
        return;
    }

    var id_new_user = parseInt($form.find('[name="id_new_user"]').val());
    if (!id_new_user) {
        bimp_msg('Aucun nouvel utilisateur sélectionné', 'warning');
        $button.removeClass('disabled');
        return;
    }

    var id_user = parseInt($('#current_params').find('[name="id_user"]').val());
    if (!id_user) {
        bimp_msg('Erreur: ID de l\'utilisateur actuel absent', 'danger');
        $button.removeClass('disabled');
        return;
    }

    if (id_user === id_new_user) {
        bimp_msg('L\'utilisateur sélectionné est déjà assigné à cette caisse', 'warning');
        $button.removeClass('disabled');
        return;
    }

    var user_name = $form.find('[name="id_new_user"]').find('option[value="' + id_new_user + '"]').text();
    var caisse_name = $('#current_params').find('[name="caisse_name"]').val();

    var msg = 'Etes-vous sûr de vouloir assigné l\'utilisateur "' + user_name + '"';
    msg += ' à la caisse "' + caisse_name + '"?' + "\n\n";
    msg += 'Cet utilisateur devra se connecter pour accéder à cette caisse';

    if (confirm(msg)) {
        BimpAjax('changeUser', {
            id_caisse: id_caisse,
            id_new_user: id_new_user,
            logout: $form.find('[name="logout"]').val()
        }, $form.find('.freeFormAjaxResult'), {
            $button: $button,
            success: function (result, bimpAjax) {
                window.location = document.location.href.replace(document.location.search, "");
            },
            error: function (result, bimpAjax) {
                if (!result || typeof (result.errors) === 'undefined') {
                    window.location = document.location.href.replace(document.location.search, "");
                } else {
                    bimpAjax.$button.removeClass('disabled');
                }
            }
        });
    } else {
        $button.removeClass('disabled');
    }
}

function loadNewVente() {
    var $button = $('#newVenteButton');
    if (!$button.length || $button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());

    if (!id_caisse) {
        bimp_msg('Erreur: identifiant de la caisse absent', 'danger');
        return;
    }

    var $container = $('#currentVenteContainer');
    var $content = $('#currenVenteContent');
    var $listContainer = $('#listVentesContainer');

    $listContainer.stop().slideUp(250);
    $content.hide();
    $container.find('.footer_buttons').stop().hide();
    $container.stop().slideDown();
    $('#validateCurrentVenteButton').addClass('disabled');

    Vente.reset();

    BimpAjax('loadNewVente', {
        id_caisse: id_caisse
    }, $content, {
        $listContainer: $listContainer,
        $content: $content,
        $container: $container,
        display_success: false,
        display_processing: true,
        append_html: true,
        processing_msg: 'Chargement',
        error_msg: 'Echec du chargement',
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            bimpAjax.$container.find('.footer_buttons').stop().slideDown(250);

            var $view = $('#BC_Vente_creation_view');
            if ($view.length) {
                onViewLoaded($view);
            }
            Vente.ajaxResult(result);

            onVenteLoaded();
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
            bimpAjax.$listContainer.stop().slideDown(250);
            bimpAjax.$content.html('').hide();
            bimpAjax.$container.hide();
        }
    });
}

function loadVente($button, id_vente) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var id_caisse = parseInt($('#current_params').find('[name="id_caisse"]').val());

    if (!id_caisse) {
        bimp_msg('Erreur: identifiant de la caisse absent', 'danger');
        return;
    }

    var $container = $('#currentVenteContainer');
    var $content = $('#currenVenteContent');
    var $listContainer = $('#listVentesContainer');

    $listContainer.stop().slideUp(250);
    $content.hide();
    $container.find('.footer_buttons').stop().hide();
    $container.stop().slideDown();
    $('#validateCurrentVenteButton').addClass('disabled');
    $('#newVenteButton').addClass('disabled');

    Vente.reset();

    BimpAjax('loadVente', {
        id_caisse: id_caisse,
        id_vente: id_vente
    }, $content, {
        $button: $button,
        $listContainer: $listContainer,
        $content: $content,
        $container: $container,
        display_success: false,
        display_processing: true,
        append_html: true,
        processing_msg: 'Chargement',
        error_msg: 'Echec du chargement',
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            bimpAjax.$container.find('.footer_buttons').stop().slideDown(250);

            var $view = $('#BC_Vente_creation_view');
            if ($view.length) {
                onViewLoaded($view);
            }
            Vente.ajaxResult(result);

            onVenteLoaded();
            bimpAjax.$button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
            $('#newVenteButton').removeClass('disabled');
            bimpAjax.$listContainer.stop().slideDown(250);
            bimpAjax.$content.html('').hide();
            bimpAjax.$container.hide();
        }
    });
}

function saveCurrentVente($button, status) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (status === 0) {
        if (Vente.nb_articles > 0) {
            if (!confirm('Etes-vous sûr de vouloir abandonner cette vente?')) {
                return;
            }
        }
    }

    BimpAjax('saveVenteStatus', {
        id_vente: Vente.id_vente,
        status: status
    }, null, {
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            var $container = $('#currentVenteContainer');
            var $content = $('#currenVenteContent');
            var $listContainer = $('#listVentesContainer');

            $content.slideUp(250, function () {
                $(this).html('');
            });

            $container.find('.footer_buttons').hide();
            $container.stop().slideUp(250);
            $listContainer.slideDown(250);
            $('#newVenteButton').removeClass('disabled');
            Vente.reset();
            reloadObjectList('BC_Vente_default_list_table');
        },
        error: function (result, bimpAjax) {

        }
    });
}

function loadNewClientForm($button) {
    loadModalForm($button, {
        module: 'bimpcore',
        object_name: 'Bimp_Societe',
        form_name: 'client_light'
    }, 'Ajout d\'un nouveau client', function () {
        var $button = $('#page_modal').find('.modal-footer').find('.save_object_button');
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                var $form = $('#page_modal').find('.modal-ajax-content').find('.Bimp_Societe_form');
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        var $modal = $('#page_modal');
                        $modal.modal('hide');
                        $('#venteClientFormContainer').find('[name="id_client"]').val(result.id_object);
                        $('#venteClientFormContainer').find('[name="id_client_contact"]').val(0);
                        saveClient();
                    });
                }
            });
        }
    });
}

function loadNewContactForm($button, id_client) {
    loadModalForm($button, {
        module: 'bimpcore',
        object_name: 'Bimp_Contact',
        form_name: 'default',
        id_parent: id_client
    }, 'Ajout d\'un nouveau contact', function () {
        var $button = $('#page_modal').find('.modal-footer').find('.save_object_button');
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                var $form = $('#page_modal').find('.modal-ajax-content').find('.Bimp_Contact_form');
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        var $modal = $('#page_modal');
                        $modal.modal('hide');
                        BimpAjax('saveContact', {
                            id_vente: Vente.id_vente,
                            id_contact: result.id_object
                        }, $('#contactViewContainer'), {
                            display_success: false,
                            display_errors_in_popup_only: true,
                            append_html: true
                        });
                    });
                }
            });
        }
    });
}

function saveClient() {
    var $container = $('#venteClientFormContainer');
    var $button = $container.find('#saveClientButton');
    var $resultContainer = $container.find('#saveClientResult');

    if ($button.hasClass('disabled')) {
        return;
    }

    if (!Vente.id_vente) {
        bimp_msg('Erreur opération impossible (ID de la vente absent)', 'danger');
        return;
    }

    var id_client = parseInt($container.find('[name="id_client"]').val());
    var id_client_contact = parseInt($container.find('[name="id_client_contact"]').val());

    if (!id_client) {
        bimp_msg('Aucun client sélectionné', 'danger');
        return;
    }

    $button.addClass('disabled');

    BimpAjax('saveClient', {
        id_vente: Vente.id_vente,
        id_client: id_client,
        id_client_contact: id_client_contact
    }, $resultContainer, {
        $container: $container,
        $button: $button,
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined' && result.html) {
                $('#venteClientViewContainer').html(result.html).slideDown(250);
                bimpAjax.$container.slideUp(250);
            }
            $button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
        }
    });
}

function editClient($button, id_client, client_name) {
    var title = 'Edition du client "' + client_name + '"';
    loadModalForm($button, {
        'module': 'bimpcore',
        'object_name': 'Bimp_Societe',
        'id_object': id_client,
        'form_name': 'default'
    }, title, function () {
        var $button = $('#page_modal').find('.modal-footer').find('.save_object_button');
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                var $form = $('#page_modal').find('.modal-ajax-content').find('.Bimp_Societe_form');
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        var $modal = $('#page_modal');
                        $modal.modal('hide');
                        $('#venteClientFormContainer').find('[name="id_client"]').val(result.id_object);
                        saveClient();
                    });
                }
            });
        }
    });
}

function editContact($button, id_contact, contact_name) {
    var title = 'Edition du contact "' + contact_name + '"';
    loadModalForm($button, {
        'module': 'bimpcore',
        'object_name': 'Bimp_Contact',
        'id_object': id_contact,
        'form_name': 'default'
    }, title, function () {
        var $button = $('#page_modal').find('.modal-footer').find('.save_object_button');
        if ($button.length) {
            $button.unbind('click').removeAttr('onclick').click(function () {
                var $form = $('#page_modal').find('.modal-ajax-content').find('.Bimp_Contact_form');
                if ($form.length) {
                    saveObjectFromForm($form.data('identifier'), $button, function (result) {
                        var $modal = $('#page_modal');
                        $modal.modal('hide');
                        BimpAjax('saveContact', {
                            id_vente: Vente.id_vente,
                            id_contact: result.id_object
                        }, $('#contactViewContainer'), {
                            display_success: false,
                            display_errors_in_popup_only: true,
                            append_html: true
                        });
                    });
                }
            });
        }
    });
}

function changeContact($select) {
    var id_contact = parseInt($select.val());

    BimpAjax('saveContact', {
        id_vente: Vente.id_vente,
        id_contact: id_contact
    }, $('#contactViewContainer'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true
    });
}

function findProduct($button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $input = $('#venteSearchProduct');
    var search = $input.val();

    if (typeof (search) === 'undefined' || !search) {
        bimp_msg('Veuillez saisir un code-barres ou un numéro de série', 'warning');
        return;
    }

    $button.addClass('disabled');

    var $resultContainer = $('#findProductResult');

    BimpAjax('findProduct', {
        id_vente: Vente.id_vente,
        search: search
    }, $resultContainer, {
        display_success: false,
        display_processing: true,
        processing_padding: 0,
        processing_msg: 'Recherche en cours',
        $button: $button,
        $input: $input,
        success: function (result, bimpAjax) {
            if (typeof (result.result_html) !== 'undefined' && result.result_html) {
                $resultContainer.html(result.result_html).slideDown(250).css('height', 'auto');
            } else if (typeof (result.cart_html) !== 'undefined' && result.cart_html) {
                $('#ventePanierLines').prepend(result.cart_html);
                var $line = $('#ventePanierLines').find('.cartArticleLine:first');
                setCartLineEvents($line);
            }
            Vente.ajaxResult(result);
            bimpAjax.$input.val('').focus();
            bimpAjax.$button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            bimpAjax.$input.focus().select();
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

function selectArticle($button, id_object, object_name) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $('.selectArticleLine').each(function () {
        $(this).find('button').addClass('disabled');
    });

    BimpAjax('selectArticle', {
        id_vente: Vente.id_vente,
        id_object: id_object,
        object_name: object_name
    }, null, {
        display_success: false,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('#findProductResult').slideUp(250, function () {
                $(this).html('');
            });
            if (typeof (result.html) !== 'undefined' && result.html) {
                $('#ventePanierLines').prepend(result.html);
                var $line = $('#ventePanierLines').find('.cartArticleLine:first');
                setCartLineEvents($line);
            } else {
                $('.selectArticleLine').each(function () {
                    $(this).find('button').removeClass('disabled');
                });
            }
            Vente.ajaxResult(result);
            $('#venteSearchProduct').focus();
        },
        error: function (result, bimpAjax) {
            $('.selectArticleLine').each(function () {
                $(this).find('button').removeClass('disabled');
            });
        }
    });
}

function removeArticle($button, id_article) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    BimpAjax('removeArticle', {
        id_vente: Vente.id_vente,
        id_article: id_article
    }, null, {
        display_success: false,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('#cart_article_' + id_article).fadeOut(250, function () {
                $(this).remove();
            });
            Vente.ajaxResult(result);
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
        }
    });
}

function changeArticleQty($button, id_article, movement) {
    var $input = $('#article_' + id_article + '_qty');
    if (!$input.length) {
        bimp_msg('Une erreur est survenue. Impossible de changer les quantités de l\'article', 'danger');
        return;
    }

    var val = parseInt($input.val());

    switch (movement) {
        case 'up':
            val++;
            break;

        case 'down':
            if (val <= 1) {
                val = 1;
            } else {
                val--;
            }
            break;
    }

    $input.val(val).change();
}

function saveArticleQty(id_article) {
    var $input = $('#article_' + id_article + '_qty');
    if (!$input.length) {
        bimp_msg('Une erreur est survenue. Impossible d\'enregistrer les quantités de l\'article', 'danger');
        return;
    }
    var val = parseInt($input.val());

    if (val) {
        BimpAjax('saveArticleQty', {
            id_vente: Vente.id_vente,
            id_article: id_article,
            qty: val
        }, null, {
            display_success: false,
            display_errors_in_popup_only: true,
            error_msg: 'Echec de l\'enregistrement de la quantité',
            success: function (result, bimpAjax) {
                Vente.ajaxResult(result);

                if (result.total_ttc) {
                    displayMoneyValue(result.total_ttc, $('#cart_article_' + id_article).find('.product_total_price'));
                }
            }
        });
    }
}

function saveVente() {

}

function addPaiement() {

}

function validateVente() {

}

function displayNewPaiementForm($button) {
    if ($button.hasClass('selected')) {
        return;
    }

    $button.addClass('selected');

    $('#ventePaiementButtons').find('.ventePaiementButton').each(function () {
        $(this).removeClass('selected').removeClass('btn-primary').addClass('btn-default');
    });

    $button.addClass('selected').removeClass('btn-default').addClass('btn-primary');

    var code = $button.data('code');
    $('#venteAddPaiementFormContainer').find('#ventePaiementCode').val(code);

    $('#venteAddPaiementFormContainer').slideDown(250, function () {
        $('#ventePaiementMontant').focus().select();
    });
}

function addVentePaiement($button) {
    if ($button.hasClass('disabled')) {
        return;
    }
    $button.addClass('disabled');

    var montant = parseFloat($('#ventePaiementMontant').val());
    if (!montant) {
        bimp_msg('Veuillez saisir un montant');
        $button.removeClass('disabled');
        return;
    }

    BimpAjax('addPaiement', {
        id_vente: Vente.id_vente,
        code: $('#ventePaiementCode').val(),
        montant: montant
    }, $('#ventePaimentsLines'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        success: function (result, bimpAjax) {
            $('#ventePaiementCode').val('');
            $('#ventePaiementMontant').val('');
            $('#venteAddPaiementFormContainer').slideUp(250);
            $('#ventePaiementButtons').find('.ventePaiementButton').each(function () {
                $(this).removeClass('selected').removeClass('btn-primary').addClass('btn-default');
            });
            Vente.ajaxResult(result);
            $button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
        }
    });
}

function deletePaiement($button, id_paiement) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    BimpAjax('deletePaiement', {
        id_vente: Vente.id_vente,
        id_paiement: id_paiement
    }, $('#ventePaimentsLines'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        success: function (result, bimpAjax) {
            Vente.ajaxResult(result);
            $button.removeClass('disabled');
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
        }
    });
}

function setCartLineEvents($line) {
    if ($line.length) {
        if (!$line.data('event_init')) {
            setInputsEvents($line);
            var $qtyInput = $line.find('.article_qty_input');
            if ($qtyInput.length) {
                $qtyInput.keyup(function (e) {
                    if (e.key === 'Enter') {
                        $qtyInput.change();
                    }
                });
                $qtyInput.change(function () {
                    if (!parseInt($(this).val())) {
                        $(this).val(1);
                    }
                    saveArticleQty(parseInt($(this).data('id_article')));
                });
            }

            $line.data('event_init', 1);
        }
    }
}

function onVenteLoaded() {
    var $input = $('#venteSearchProduct');

    if ($input.length && !$input.data('event_init')) {
        $input.keyup(function (e) {
            if (e.key === 'Enter') {
                $('#findProductButton').click();
            }
        });
        $input.data('event_init', 1);
    }

    $input = $('#ventePaiementMontant');

    if ($input.length && !$input.data('event_init')) {
        $input.keyup(function (e) {
            if (e.key === 'Enter') {
                $('#venteAddPaiementButton').click();
            } else {
                checkTextualInput($(this));
            }
        });
        $input.data('event_init', 1);
    }

    $('#ventePanierLines').find('.cartArticleLine').each(function () {
        setCartLineEvents($(this));
    });

}

$(document).ready(function () {

    var $mainContainer = $('#bc_main_container');
    setCommonEvents($mainContainer);

    $('#openCaisseForm').find('[name="id_entrepot"]').change(function () {
        var id_entrepot = parseInt($(this).val());
        if (id_entrepot) {
            BimpAjax('loadCaisseSelect', {
                'id_entrepot': id_entrepot
            }, $('#caisseSelectContainer'), {
                display_success: false,
                display_errors_in_popup_only: true,
                append_html: true,
                success: function () {
                    $('#openCaisseForm').find('input[name="fonds"]').keyup(function () {
                        checkTextualInput($(this));
                    });
                    if ($('#openCaisseForm').find('#id_caisse').find('option').length) {
                        $('#openCaisseForm').find('#openCaisseButton').removeClass('disabled');
                    }
                }
            });
        }
    });

    $('#closeCaisseButton').click(function () {
        $('closeCaisseForm').find('[name="fonds"]').val('');
        $('#venteToolbar').slideUp(250);
        $('#closeCaisseForm').slideDown(250);
    });

    $('#cancelCloseCaisseButton').click(function () {
        $('#closeCaisseForm').slideUp(250);
        $('#venteToolbar').slideDown(250);
    });

    $('#changeUserButton').click(function () {
        $('#venteToolbar').slideUp(250);
        $('#changeUserForm').slideDown(250);
    });

    $('#cancelChangeUserButton').click(function () {
        $('#changeUserForm').slideUp(250);
        $('#venteToolbar').slideDown(250);
    });

    $('#closeCaisseForm').find('[name="fonds"]').keyup(function () {
        checkTextualInput($(this));
    });

    $('#newVenteButton').click(function () {
        loadNewVente();
    });

    $('#cancelCurrentVenteButton').click(function () {

    });

    $('.windowMaximiseButton').click(function () {
        if ($mainContainer.hasClass('fullScreen')) {
            $('#id-left').show();
            $mainContainer.removeClass('fullScreen');
            $(this).data('content', 'Agrandir').find('i').attr('class', 'fa fa-window-maximize');
            $('.fullScreenButton').show();
        } else {
            $('#id-left').hide();
            $mainContainer.addClass('fullScreen');
            $(this).data('content', 'Rétrécir').find('i').attr('class', 'fa fa-times');
            $('.fullScreenButton').hide();
        }
    });

    $('.fullScreenButton').click(function () {
        if ($mainContainer.hasClass('fullScreen')) {
            $('#id-left').show();
            $mainContainer.removeClass('fullScreen');
            $(this).data('content', 'Plein écran').find('i').attr('class', 'fa5 fa5-expand-arrows-alt');
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
            else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            }
            else if (document.webkitCancelFullScreen) {
                document.webkitCancelFullScreen();
            }
            $('.windowMaximiseButton').show();
        } else {
            $('#id-left').hide();
            $mainContainer.addClass('fullScreen');
            $(this).data('content', 'Quitter le plein écran').find('i').attr('class', 'fa fa-times');

            var docElm = document.documentElement;
            if (docElm.requestFullscreen) {
                docElm.requestFullscreen();
            }
            else if (docElm.mozRequestFullScreen) {
                docElm.mozRequestFullScreen();
            }
            else if (docElm.webkitRequestFullScreen) {
                docElm.webkitRequestFullScreen();
            }
            $('.windowMaximiseButton').hide();
        }
    });
});