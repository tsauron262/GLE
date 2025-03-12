notifEvent = null;

class actioncomm_event extends AbstractNotification {
	constructor(id, storage_key) {
		super('actioncomm_event', id, storage_key);
		notifEvent = this;
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
			html += '<i class="fas fa5-calendar-alt atoplogin"></i></a>';
			html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
			html += '<h4 class="header">';
			html += 'Mes rendez-vous' + this.getBoutonReload(this.dropdown_id);
			html += '<a style="float:right" href="' + dol_url_root + '/synopsistools/agenda/vue.php"><i class="fas fa5-calendar-alt iconLeft"></i>Mon agenda</a>';
			html += '</h4>';
			html += '<div class="notifications-wrap list_notification actioncomm_event">';
			html += '</div>';
			html += '</div>';

			var $container = $(this.parent_selector);
			if ($container.length) {
				$container.prepend(html);
				super.init();
			} else {
				console.error('Actioncomm events : container notifs absent');
			}
		}
	}

	avatarIcone(text, color, classSup) {
		return '<span  style="background-color:' + color + '!important" class="badge badge-md badge-circle badge-floating badge-danger border-white' + classSup + '">' + text + '</span>';
	}

	formatElement(element, key) {
		var html = '';

		element.is_new = this.isNew(element);

		html += '<div style="padding: 10px">';
		html += element.link;
		html += '<div/>';

		return html;
	}

	getElementHeaderButtons(element, key) {
		return '';
	}

	isNew(element) {
		return 1;
	}

	getKey(element) {
		if (!element) {
			return '';
		}

		return 'ac_event_' + element.id;
	}

	sendBrowserNotification(elements) {
	}

	getLabel() {
		return 'Rendez-vous';
	}

}

$(document).ready(function () {
	$('body').on('objectChange', function (e) {
		if (e.module === 'bimpcore' && e.object_name === 'Bimp_ActionComm') {
			if (notifEvent) {
				notifEvent.refreshElements();
			}
		}
	});
});
