notifHashtag = null;

class hashtag extends AbstractNotification {
    constructor(id, storage_key) {
        super('hashtag', id, storage_key);
        notifHashtag = this;

        this.init();
    }

    init() {
        if ($('a#' + this.dropdown_id).length == 0) {
            if (theme != 'BimpTheme') {
                var notif_white = 'notif_white';
            } else {
                var notif_white = '';
            }

            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html += '<i class="fas fa5-hashtag atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<h4 class="header">';
            html += 'Objets liés' + this.getBoutonReload(this.dropdown_id);
            html += '<a style="float:right" onclick="loadModalObjectCustomContent($(this), {module: \'bimpcore\', object_name: \'Bimp_User\', id_object: id_user}, \'renderLinkedObjectsLists\', [\'sources\'], \'Objets liés par citation\', null, \'medium\');"><i class="fas fa5-comments iconLeft"></i>Mes objets liés</a>';
            html += '</h4>';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';
            html += '</div>';

            var $container = $(this.parent_selector);
            if ($container.length) {
                $container.prepend(html);
                super.init();
            } else {
                console.error('Hashtags : container notifs absent');
            }
        }
    }

    formatElement(element, key) {
        var html = '';

        element.is_new = this.isNew(element);

        if (element.obj_link) {
            html += element.obj_link;
        }

        if (element.src) {
            html += '<div class="hashtag_src">' + element.src + '</div>';
        }

        html += '<div class="hashtag_content">' + element.content + '</div>';

        return html;
    }

    getElementHeaderButtons(element, key) {
        var html = '';
        var callback_set_as_viewed = 'notifHashtag.setAsViewed("' + key + '","' + element.id + '")';

        // Marquer comme lu
        if (!element.is_viewed) {
            html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Marquer Lu" data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" ';
            html += 'onclick=\'' + callback_set_as_viewed + ';\' data-original-title="" title="Marquer comme lu"><i class="far fa5-envelope-open"></i></span>'
        } else {
            html += '<span style="font-size: 11px" class="success"><i class="fas fa5-check iconLeft"></i>Lu</span>';
        }

        return html;
    }

    isNew(element) {
        if (parseInt(element.is_viewed) === 1) {
            return 0;
        }

        return 1;
    }

    getKey(element) {
        return element.id;
    }

    sendBrowserNotification(elements) {
        if (!elements.length) {
            return;
        }

        var bn = this;
        var title = '';
        var content = '';

        if (elements.length > 1) {
            title = elements.length + ' nouvelles citations';
        } else {
            title = "Nouvelle citation";
            content = elements[0].content;
        }

        BimpBrowserNotification(title, content, function () {
            window.parent.parent.focus();
            if (parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1) {
                $('#' + bn.dropdown_id).trigger('click');
            }
        });
    }

    getLabel() {
        return 'Hashtags';
    }
}

$(document).ready(function () {
    $('body').on('objectChange', function (e) {
        if (e.module === 'bimpcore' && e.object_name === 'BimpLink') {
            if (typeof (notifHashtag) !== 'undefined' && notifHashtag !== null) {
                notifHashtag.refreshElements();
            }
        }
    });
});