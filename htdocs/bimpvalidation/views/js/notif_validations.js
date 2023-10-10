
class notif_validations extends AbstractNotification {
    /**
     * Overrides
     */

    constructor(nom) {
        super(nom);
    }

    init() {
        if (theme != 'BimpTheme')
            var notif_white = 'notif_white';
        else
            var notif_white = '';

        if (!$('a#' + this.dropdown_id).length) {
            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html += '<i class="far fa5-check-circle atoplogin"></i>';
            html += '</a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';

            html += '<div class="header" style="padding: 10px 15px">';

            html += '<table style="width: 100%; font-size: 15px;">';
            html += '<tr>';
            html += '<td style="width: 30%">';
            html += 'Demandes de validation';
            html += this.getBoutonReload(this.dropdown_id);
            html += '</td>';
            html += '<td style="width: 70%; text-align: right">';
            html += '<a href="' + DOL_URL_ROOT + '/bimpvalidation/index.php?fc=user"><i class="far fa5-check-circle iconLeft"></i>Toutes mes demandes de validation</a>';
            html += '</td>';
            html += '</tr>';
            html += '</table>';

            html += '</div>';

            html += '<div class="tabs-animated">';

            // Nav tabs 
            html += '<ul id="nav_bv_demandes" class="nav nav-tabs" role="tablist">';
            html += '<li role="presentation" class="active"><a href="#user_demandes" aria-controls="user_demandes" role="tab" data-toggle="tab">Mes demandes assignées à valider&nbsp;&nbsp;<span class="badge badge-default nb_elements">0</span></a></li>';
            html += '<li role="presentation"><a href="#other_demandes" aria-controls="other_demandes" role="tab" data-toggle="tab">Autres demandes validables&nbsp;&nbsp;<span class="badge badge-default nb_elements">0</span></a></li>';
            html += '</ul>';

            // Tab panels 
            html += '<div class="tab-content bv_demandes_panel">';
            html += '<div role="tabpanel" class="list_notification tab-pane fade in active" id="user_demandes"></div>';
            html += '<div role="tabpanel" class="list_notification tab-pane fade" id="other_demandes"></div>';
            html += '</div>';

            html += '</div>';
            html += '</div>';

            $(this.parent_selector).prepend(html);

//            // Animation slide sur le coté
            $('ul#nav_bv_demandes > li > a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
                var $old_tab = $($(e.target).attr("href"));
                var $new_tab = $($(e.relatedTarget).attr("href"));

                if ($new_tab.index() < $old_tab.index()) {
                    $old_tab.css('position', 'relative').css("right", "0").show();
                    $old_tab.animate({"right": "-100%"}, 300, function () {
                        $old_tab.css("right", 0).removeAttr("style");
                    });
                } else {
                    $old_tab.css('position', 'relative').css("left", "0").show();
                    $old_tab.animate({"left": "-100%"}, 300, function () {
                        $old_tab.css("left", 0).removeAttr("style");
                    });
                }
            });

            $('ul#nav_bv_demandes > li > a[data-toggle="tab"]').on('show.bs.tab', function (e) {
                var $new_tab = $($(e.target).attr("href"));
                var $old_tab = $($(e.relatedTarget).attr("href"));

                if ($new_tab.index() > $old_tab.index()) {
                    $new_tab.css('position', 'relative').css("right", "-2500px");
                    $new_tab.animate({"right": "0"}, 500);
                } else {
                    $new_tab.css('position', 'relative').css("left", "-2500px");
                    $new_tab.animate({"left": "0"}, 500);
                }
            });

            super.init(this);
        }
    }

    addElement(element) {
        if (typeof element.content === "object" && element.content.length > 0) {
            this.emptyNotifs();

            var new_items = [];
            for (var i in element.content) {
                var item = element.content[i];

                if (parseInt(item.id) > this.id_max) {
                    this.id_max = parseInt(item.id);
                    new_items.push(item);
                }

                var key = this.getKey(item);
                var item_html = this.formatElement(item, key);
                this.addInList(item_html, item.url, item, key, item.append);
            }

            if (new_items.length > 0) {
                // todo : améliorer les notifications (ne compte que les user_demandes
                var title = 'Vous avez ' + new_items.length;

                if (new_items.length > 1) {
                    title += ' nouvelles demandes';
                } else {
                    title += ' nouvelle demande';
                }

                title += ' de validation à traiter';

//                this.displayNotification(title, ''); // A debugguer
            }

            $('#nav_bv_demandes a[aria-controls="user_demandes"] span.nb_elements').html(element.nb_user_demandes);
            $('#nav_bv_demandes a[aria-controls="other_demandes"] span.nb_elements').html(element.nb_other_demandes);

            var $span = $('a#' + this.dropdown_id + ' > span.badge.bg-danger');

            if (!$span.length && element.nb_user_demandes > 0) {
                $('a#' + this.dropdown_id).append('<span class="badge bg-danger">' + element.nb_user_demandes + '</span>');
            } else if (!element.nb_user_demandes) {
                $span.remove();
            } else {
                $span.html(element.nb_user_demandes);
            }
        }
    }

    formatElement(element, key) {
        var html = '';

        element.append = 'div.bv_demandes_panel > #' + element.id_tab;

        html += '<div class="bv_demande_content">';
        html += '<b>Validation ' + element.type + '</b><br/>';
        html += '<div class="user_demande">' + element.user_demande + '</div>';

        if (typeof element.obj_link !== 'undefined') {
            html += '<b>Pièce : </b>' + element.obj_link + '<br/>';
        }
        if (typeof element.client !== 'undefined') {
            html += '<b>Client : </b>' + element.client + '<br/>';
        }
        if (typeof element.val !== 'undefined') {
            html += element.val + '<br/>';
        }
        html += '</div>';


        return html;
    }

    getElementHeaderButtons(element, key) {
        var html = '';

        if (element.id) {
            if (element.can_process) {
                var onclick = 'setObjectAction($(this), {module: \'bimpvalidation\', object_name: \'BV_Demande\', id_object: ' + element.id + '}, \'accept\')';
                html += '<span class="btn btn-default btn-small" onclick="' + onclick + '"><i class="fas fa5-check iconLeft"></i>Accepter</span>';

                var onclick = 'setObjectAction($(this), {module: \'bimpvalidation\', object_name: \'BV_Demande\', id_object: ' + element.id + '}';
                onclick += ', \'refuse\', {}, null, null, {form_name: \'refuse\'})';
                html += '<button class="btn btn-default btn-small" onclick="' + onclick + '"><i class="fas fa5-times iconLeft"></i>Refuser</button>';
            }

            if (element.can_view) {
                html += '<span class="rowButton" onclick="loadModalView(\'bimpvalidation\', \'BV_Demande\', ' + element.id + ', \'default\', $(this))">';
                html += '<i class="fas fa5-eye"></i></span>';
            }
        }

        return html;
    }

    emptyNotifs() {
        $('div.bv_demandes_panel > #user_demandes').empty();
        $('div.bv_demandes_panel > #other_demandes').empty();
        $('#nav_bv_demandes span.nb_elements').html('0');
    }

    elementAdded(nb_add) {

    }

    elementRemoved(nb_rm) {

    }

    displayNotification(title, msg) {
        var dvn = this;

        if (window.Notification && Notification.permission === "granted") {
            var n = new Notification(title, {
                body: msg,
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });

            n.onclick = function () {
                if (parseInt($('div[aria-labelledby="' + dvn.dropdown_id + '"]').attr('is_open')) !== 1)
                    $('#' + dvn.dropdown_id).trigger('click');
            };
        }
    }
}
