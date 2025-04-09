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
			html += '<i class="far fa5-calendar-alt atoplogin"></i></a>';
			html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown dropdown_actioncomm_event" aria-labelledby="' + this.dropdown_id + '">';
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

		html += '<div style="padding: 8px">';
		html += '<div style="display: inline-block; width: 50%">';
		if (element.type) {
			html += '<span class="type" style="margin-right: 12px;">' + element.type + '</span>';
		}
		if (element.lieu) {
			html += '<span class="lieu"><i class="fas fa5-map-marker-alt iconLeft"></i>' + element.lieu + '</span>';
		}
		html += '</div>';
		html += '<div style="display: inline-block; width: 49%; text-align: right">';
		if (element.state) {
			html += '<span class="state" style="font-size: 12px">' + element.state + '</span>';
		}
		html += '</div>';


		html += '<div class="notif_title">';
		if (element.icon) {
			html += '<span class="' + element.bg_type + '"><i class="fas fa5-' + element.icon + ' iconLeft"></i></span>';
		}
		if (element.label) {
			html += element.label;
		}
		html += '</div>';

		if (element.obj) {
			html += '<div>' + element.obj + '</div>';
		}

		if (element.tiers) {
			html += '<div>' + element.tiers + '</div>';
		}
		if (element.contact) {
			html += '<div><b>Contact : </b>' + element.contact + '</div>';
		}

		if (element.desc) {
			html += '<div class="notif_desc">' + element.desc + '</div>';
		}

		html += '<div/>';

		return html;
	}

	getElementHeaderButtons(element, key) {
		if (element.close_btn) {
			var onclick = 'setObjectAction($(this), {module: \'bimpcore\', object_name: \'Bimp_ActionComm\', id_object: ' + element.id + '}';
			onclick += ', \'done\', {}, null, null, {display_processing: false})';
			return '<button name="close" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-check iconLeft"></i>Terminer</button>';	
		}		
		
		return '';
	}

	isNew(element) {
		return element.today;
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
