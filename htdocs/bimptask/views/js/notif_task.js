notifTask = null;
class notif_task extends AbstractNotification {

    constructor(id, storage_key) {
        super('notif_task', id, storage_key);
        notifTask = this;

        this.nb_affected = 0;
        this.nb_affected_msgs = 0;
        this.nb_unaffected = 0;
        this.nb_unaffectd_msgs = 0;

        this.affected_selector = '#user_notifications_affected_tasks';
        this.unaffected_selector = '#user_notifications_unaffected_tasks';

        this.init();
    }

    init() {
        var nt = this;

        if (theme != 'BimpTheme') {
            var notif_white = 'notif_white';
        } else {
            var notif_white = '';
        }

        if ($('a#' + this.dropdown_id).length === 0) {
            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html += '<i class="fas fa5-tasks atoplogin"></i>';
            html += '</a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<div class="notifications-wrap list_notification ' + ''/*this.nom */ + '">';

            html += '<div class="header" style="padding: 5px 15px">';

            html += '<table style="width: 100%; font-size: 15px;">';
            html += '<tr>';
            html += '<td style="width: 30%">';
            html += 'Tâches en cours';
            html += this.getBoutonReload(this.dropdown_id);

            html += '</td>';
            html += '<td style="width: 70%; text-align: right">';
            html += '<a href="' + DOL_URL_ROOT + '/bimpcore/index.php?fc=user&id=' + id_user + '&navtab-maintabs=tasks&navtab-tasks=my_tasks"><i class="fas fa5-tasks iconLeft"></i>Toutes mes tâches</a>';

            var onclick = 'loadModalForm($(this), {module: \'bimptask\', object_name: \'BIMP_Task\', id_object: 0, form_name: \'addUser\'}, \'Nouvelle Tâche\')';
            html += '<span class="btn btn-default" onclick="' + onclick + '" style="margin-left: 15px">';
            html += '<i class="fas fa5-plus-circle iconLeft"></i>Nouvelle Tâche';
            html += '</span>';
            html += '</td>';
            html += '</tr>';
            html += '</table>';

            html += '</div>';

            html += '<div class="tabs-animated">';

            // Nav tabs 
            html += '<ul id="nav_task" class="nav nav-tabs" role="tablist">';
            html += '<li role="presentation" class="active"><a href="#user_notifications_affected_tasks" aria-controls="user_notifications_affected_tasks" role="tab" data-toggle="tab">Mes tâches</a></li>';
            html += '<li role="presentation"><a href="#user_notifications_unaffected_tasks" aria-controls="user_notifications_unaffected_tasks" role="tab" data-toggle="tab">Tâches non attribuées</a></li>';
            html += '</ul>';

            // Tab panels 
            html += '<div class="tab-content task_panel">';
            html += '<div role="tabpanel" class="list_notification tab-pane fade in active" id="user_notifications_affected_tasks"></div>';
            html += '<div role="tabpanel" class="list_notification tab-pane fade" id="user_notifications_unaffected_tasks"></div>';
            html += '</div>';

            html += '</div>';
            html += '</div>';
            html += '</div>';

            var $container = $(this.parent_selector);
            if ($container.length) {
                $container.prepend(html);

                // Animations slide sur le coté: 
                $('ul#nav_task > li > a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
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

                $('ul#nav_task > li > a[data-toggle="tab"]').on('show.bs.tab', function (e) {
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

                super.init();
            } else {
                console.error('Tâches : container notifs absent');
            }
        }
    }

    isNew(element) {
        return 1;
    }

    getbuttonSendMail(id_object) {
        var onclick = 'setObjectAction($(this), {module: \'bimptask\', object_name: \'BIMP_Task\', id_object: ' + id_object + '}';
        onclick += ', \'sendMail\', {}, null, null, {form_name: \'newMail\'})';
        return '<button name="rep_mail" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-paper-plane iconLeft"></i>Rep Mail</button>';
    }

    getbuttonClose(id_object) {
        var onclick = 'setObjectAction($(this), {module: \'bimptask\', object_name: \'BIMP_Task\', id_object: ' + id_object + '}';
        onclick += ', \'close\', {}, null, null, {form_name: \'close\'})';
        return '<button name="close" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-check iconLeft"></i>Terminer</button>';
    }

    getButtonAttribute(id_object) {
        var onclick = 'setObjectAction($(this), {module: \'bimptask\', object_name: \'BIMP_Task\', id_object: ' + id_object + '}';
        onclick += ', \'attribute\', {}, null, null, {form_name: \'attribute\'})';
        return '<button name="attribute" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-user iconLeft"></i>Attribuer</button>';
    }

    getButtonRefuseAttribute(id_object) {
        var onclick = 'setObjectAction($(this), {module: \'bimptask\', object_name: \'BIMP_Task\', id_object: ' + id_object + '}';
        onclick += ', \'attribute\', {id_user_owner: 0}, null, null, {confirm_msg: \'Refuser cette attribution ?\'})';
        return '<button name="refuse_attribute" class="btn btn-default  btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-times-circle iconLeft"></i>Refuser l\'attribution</button>';
    }

    getButtonNotViewed(id_object, not_viewed) {

        if (not_viewed == 0)
            return '';

        var s = ''
        if (not_viewed > 1)
            s = 's';

        return '<button not_viewed=' + not_viewed + ' class="btn  btn-danger btn-small" type="button" onclick="loadModalView('
                + '\'bimptask\', \'BIMP_Task\', ' + id_object + ', \'notes\', $(this), '
                + '\'Infos\')"><i class="fa fa-fas fa-comments iconLeft"></i>' + not_viewed + ' Info' + s + ' ' + not_viewed + ' Non lue' + s + '</button>';
    }

    getKey(element) {
        return 'task_' + element.id;
    }

    formatElement(element, key) {
        var html = '';

        html += '<div class="task_content">';
        element.append = 'div.tab-content > #' + element.user_type + ' > div.task_no_prio';

        if (element.src) {
            html += '<div class="task_src">' + element.src + '</div>';
        } else if (element.author) {
            html += '<div class="task_src">' + element.author + '</div>';
        }

        if (element.dest) {
            html += '<div class="task_dest">Destinataire : ' + element.dest + '</div>';
        }

        if (element.parent_task) {
            html += '<div class="parent_task">';
            html += 'Tâche mère : ' + element.parent_task;
            html += '</div>';
        }

        html += '<div class="task_subj">';
        if (element.status_icon) {
            html += element.status_icon;
        }

        if (element.prio_badge) {
            html += element.prio_badge;
        }

        if (element.subj) {
            html += element.subj;
        }

        html += '</div>';

        if (element.txt) {
            html += '<div class="task_txt">' + element.txt + '</div>';
        }

        element.class = 'single_task';

        return html;
    }

    getElementHeaderButtons(element, key) {
        var html = '';

        html += this.getButtonNotViewed(element.id, element.not_viewed);

        // Tâche pour l'utilisateur courant
        if (element.user_type === 'affected') {
            if (element.can_rep_mail)
                html += this.getbuttonSendMail(element.id);
            if (element.can_close)
                html += this.getbuttonClose(element.id);
            if (element.can_attribute)
                html += this.getButtonRefuseAttribute(element.id);

            // Tâche non affectée
        } else if (element.user_type === 'unaffected') {
            if (element.can_rep_mail)
                html += this.getbuttonSendMail(element.id);
            if (element.can_close)
                html += this.getbuttonClose(element.id);
            if (element.can_attribute)
                html += this.getButtonAttribute(element.id);
        }

        if (element.id) {
            html += '<span class="rowButton" onclick="loadModalView(\'bimptask\', \'BIMP_Task\', ' + element.id + ', \'full\', $(this))">';
            html += '<i class="fas fa5-eye"></i></span>';

            if (element.can_edit) {
                var data = '{module: \'bimptask\', object_name: \'BIMP_Task\', id_object: ' + element.id + ', form_name: \'default\'}';
                html += '<span class="rowButton" onclick="loadModalForm($(this), ' + data + ')">';
                html += '<i class="fas fa5-edit"></i></span>';
            }
        }
        return html;
    }

    emptyContent() {
        this.nb_affected = 0;
        this.nb_affected_msgs = 0;
        this.nb_unaffected = 0;
        this.nb_unaffectd_msgs = 0;

        $(this.affected_selector).html('');
        $(this.unaffected_selector).html('');
    }

    renderElements() {
        super.renderElements();
        this.updateNavs();
    }

    appendElement(element, key, html) {
        if (element.user_type === 'affected') {
            this.nb_affected++;
            this.nb_affectd_msgs += element.not_viewed;
            $(this.affected_selector).append(html);
        } else {
            this.nb_unaffected++;
            this.nb_unaffectd_msgs += element.not_viewed;
            $(this.unaffected_selector).append(html);
        }
    }

    updateNavs() {
        var $nav = $('ul#nav_task > li > a[href="#user_notifications_affected_tasks"]');

        if ($nav.length) {
            $nav.attr('nb_task', this.nb_affected);
            $nav.attr('nb_msg', this.nb_affectd_msgs);
        }

        var html = 'Mes tâches <span class="badge badge-' + (this.nb_affected > 0 ? 'info' : 'danger') + '" style="margin-left: 6px; font-size: 10px">' + this.nb_affected + '</span>';
        if (this.nb_affectd_msgs) {
            html += '&nbsp;&nbsp;<span style="font-size: 11px; font-style: italic">(' + this.nb_affectd_msgs + ' message' + (this.nb_affectd_msgs > 1 ? 's' : '') + ' non lu' + (this.nb_affectd_msgs > 1 ? 's' : '') + ')</span>';
        }
        $nav.html(html);

        var $nav = $('ul#nav_task > li > a[href="#user_notifications_unaffected_tasks"]');

        if ($nav.length) {
            $nav.attr('nb_task', this.nb_affected);
            $nav.attr('nb_msg', this.nb_affectd_msgs);
        }

        html = 'Tâches non attribuées <span class="badge badge-' + (this.nb_unaffected > 0 ? 'info' : 'danger') + '" style="margin-left: 6px; font-size: 10px">' + this.nb_unaffected + '</span>';
        if (this.nb_unaffectd_msgs) {
            html += '&nbsp;&nbsp;<span style="font-size: 11px; font-style: italic">(' + this.nb_unaffectd_msgs + ' message' + (this.nb_unaffectd_msgs > 1 ? 's' : '') + ' non lu' + (this.nb_unaffectd_msgs > 1 ? 's' : '') + ')</span>';
        }
        $nav.html(html);
    }

    sendBrowserNotification(elements) {
        if (!elements.length) {
            return;
        }

        var bn = this;
        var title = '';
        var content = '';

        if (elements.length > 1) {
            title = 'Vous avec reçu ' + elements.length + ' nouvelles tâches';
        } else {
            title = "Nouvelle tâche : " + elements[0].subj;
            content = elements[0].txt;
        }

        BimpBrowserNotification(title, content, function () {
            window.parent.parent.focus();
            if (parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1) {
                $('#' + bn.dropdown_id).trigger('click');
            }
        });
    }

    isMultiple(elements) {

        var nb_my_task = 0;

        for (var i in elements) {
            if (elements[i].user_type === 'affected') {
                nb_my_task++;
            }
        }

        return nb_my_task > 2;
    }

    swapButton(element, id_task) {
        var btn_refuse_attribute = element.find('button[name="refuse_attribute"]');
        if (btn_refuse_attribute.length == 1) {
            var new_button = this.getButtonAttribute(id_task);
            btn_refuse_attribute.replaceWith(new_button);
            return;
        }

        var btn_attribute = element.find('button[name="attribute"]');
        if (btn_attribute.length == 1) {
            var new_button = this.getButtonRefuseAttribute(id_task);
            btn_attribute.replaceWith(new_button);
            return;
        }

    }

    getLabel() {
        return 'Tâches';
    }
}

$(document).ready(function () {
    $('body').on('objectChange', function (e) {
        if (e.module === 'bimptask' && e.object_name === 'BIMP_Task') {
            if (typeof (notifTask) !== 'undefined' && notifTask !== null) {
                notifTask.refreshElements();
            }
        }
    });
});