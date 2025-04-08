notifTicket = null;

class notif_ticket extends AbstractNotification {

	constructor(id, storage_key) {
		super('notif_ticket', id, storage_key);
		notifTicket = this;

		this.nb_affected = 0;
		this.nb_affected_msgs = 0;
		this.nb_unaffected = 0;
		this.nb_unaffectd_msgs = 0;

		this.affected_selector = '#user_notifications_affected_tickets';
		this.unaffected_selector = '#user_notifications_unaffected_tickets';

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
			html += '<i class="fas fa5-ticket-alt atoplogin"></i>';
			html += '</a>';
			html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown dropdown_tickets" aria-labelledby="' + this.dropdown_id + '">';
			html += '<div class="notifications-wrap list_notification ' + ''/*this.nom */ + '">';

			html += '<div class="header" style="padding: 5px 15px">';

			html += '<table style="width: 100%; font-size: 15px;">';
			html += '<tr>';
			html += '<td style="width: 30%">';
			html += 'Tickets ';
			html += this.getBoutonReload(this.dropdown_id);

			html += '</td>';
			html += '<td style="width: 70%; text-align: right">';
			html += '<a href="' + DOL_URL_ROOT + '/bimpcore/index.php?fc=user&id=' + id_user + '&navtab-maintabs=tickets&navtab-tickets=my_tickets"><i class="fas fa5-ticket-alt iconLeft"></i>Tous mes tickets</a>';

			var onclick = 'loadModalForm($(this), {module: \'bimpticket\', object_name: \'Bimp_Ticket\', id_object: 0, form_name: \'create\'}, \'Nouveau Ticket\')';
			html += '<span class="btn btn-default" onclick="' + onclick + '" style="margin-left: 15px">';
			html += '<i class="fas fa5-plus-circle iconLeft"></i>Nouveau ticket';
			html += '</span>';
			html += '</td>';
			html += '</tr>';
			html += '</table>';

			html += '</div>';

			html += '<div class="tabs-animated">';

			// Nav tabs 
			html += '<ul id="nav_tickets" class="nav nav-tabs" role="tablist">';
			html += '<li role="presentation" class="active"><a href="#user_notifications_affected_tickets" aria-controls="user_notifications_affected_tickets" role="tab" data-toggle="tab">Mes tickets en cours</a></li>';
			html += '<li role="presentation"><a href="#user_notifications_unaffected_tickets" aria-controls="user_notifications_unaffected_tickets" role="tab" data-toggle="tab">Tickets non assignés</a></li>';
			html += '</ul>';

			// Tab panels 
			html += '<div class="tab-content notif_panel ticket_panel">';
			html += '<div role="tabpanel" class="list_notification tab-pane fade in active" id="user_notifications_affected_tickets"></div>';
			html += '<div role="tabpanel" class="list_notification tab-pane fade" id="user_notifications_unaffected_tickets"></div>';
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
				console.error('Tickets : container notifs absent');
			}
		}
	}

	isNew(element) {
		return 1;
	}

	getbuttonClose(element) {
		var onclick = 'setObjectAction($(this), {module: \'bimpticket\', object_name: \'Bimp_Ticket\', id_object: ' + element.id + '}';
		onclick += ', \'newStatus\', {new_status: 8}, null, null, {confirm_msg: \'Veuillez confirmer\'})';
		return '<button name="close" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-check iconLeft"></i>Terminer</button>';
	}

	getbuttonInProgress(element) {
		var onclick = 'setObjectAction($(this), {module: \'bimpticket\', object_name: \'Bimp_Ticket\', id_object: ' + element.id + '}';
		onclick += ', \'newStatus\', {new_status: 3}, null, null, {confirm_msg: \'Veuillez confirmer\'})';
		return '<button name="close" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-check iconLeft"></i>En cours</button>';
	}

	getButtonAttribute(element) {
		var onclick = 'setObjectAction($(this), {module: \'bimpticket\', object_name: \'Bimp_Ticket\', id_object: ' + element.id + '}';
		onclick += ', \'assign\', {}, null, null, {form_name: \'assign\'})';

		var html = '<button name="attribute" class="btn btn-default btn-small" type="button" onclick="' + onclick + '"><i class="fas fa5-user-plus iconLeft"></i>';
		
		if (element.affected) {
			html += 'Changer l\'assignation';
		} else {
			html += 'Assigner';
		}
		html += '</button>';
			
		return html;
	}

	getButtonMsgs(element) {
		if (!element.nb_msgs) {
			return '';
		}

		var s = ''
		if (element.nb_msgs > 1) {
			s = 's';
		}

		return '<button class="btn btn-danger btn-small" type="button" onclick="loadModalObjectCustomContent($(this), '
			+ '{module: \'bimpticket\', object_name: \'Bimp_Ticket\', id_object: \'' + element.id + '\'}, \'renderNotesList\', {}, \'Note(s) du ticket ' + element.ref + '\')">'
			+ '<i class="fas fa5-sticky-note iconLeft"></i>' + element.nb_msgs + ' Note' + s + ' non lue' + s + '</button>';
	}

	getKey(element) {
		return 'ticket_' + element.id;
	}

	formatElement(element, key) {
		var html = '';

		html += '<div class="ticket_content">';
		element.append = 'div.tab-content > #' + element.user_type + ' > div.task_no_prio';

		if (element.src) {
			html += '<div class="notif_src">' + element.src + '</div>';
		} else if (element.author) {
			html += '<div class="notif_src">' + element.author + '</div>';
		}

		if (element.dest) {
			html += '<div class="notif_dest">Destinataire : ' + element.dest + '</div>';
		}

		html += '<div class="notif_title">';
		if (element.status_icon) {
			html += element.status_icon;
		}

		if (element.subj) {
			html += element.subj;
		}

		html += '</div>';

		if (element.client) {
			html += '<div>Client : ' + element.client + '</div>';
		}

		if (element.txt) {
			html += '<div class="notif_desc">' + element.txt + '</div>';
		}

		return html;
	}

	getElementHeaderButtons(element, key) {
		var html = '';

		html += this.getButtonMsgs(element);

		if (element.can_begin) {
			html += this.getbuttonInProgress(element);
		}
		
		if (element.can_close) {
			html += this.getbuttonClose(element);
		}

		if (element.can_attribute) {
			html += this.getButtonAttribute(element);
		}

		if (element.id) {
			html += '<span class="rowButton" onclick="loadModalView(\'bimpticket\', \'Bimp_Ticket\', ' + element.id + ', \'default\', $(this))">';
			html += '<i class="fas fa5-eye"></i></span>';

			if (element.can_edit) {
				var data = '{module: \'bimpticket\', object_name: \'Bimp_Ticket\', id_object: ' + element.id + ', form_name: \'default\'}';
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
		if (element.affected) {
			this.nb_affected++;
			this.nb_affected_msgs += element.nb_msgs;
			$(this.affected_selector).append(html);
		} else {
			this.nb_unaffected++;
			this.nb_unaffectd_msgs += element.nb_msgs;
			$(this.unaffected_selector).append(html);
		}
	}

	updateNavs() {
		var $nav = $('ul#nav_tickets > li > a[href="#user_notifications_affected_tickets"]');

		if ($nav.length) {
			$nav.attr('nb_tickets', this.nb_affected);
			$nav.attr('nb_msgs', this.nb_affected_msgs);
		}

		var html = 'Mes tickets à traiter <span class="badge badge-' + (this.nb_affected > 0 ? 'info' : 'danger') + '" style="margin-left: 6px; font-size: 10px">' + this.nb_affected + '</span>';
		if (this.nb_affected_msgs) {
			html += '&nbsp;&nbsp;<span style="font-size: 11px; font-style: italic; font-weight: normal">(' + this.nb_affected_msgs + ' message' + (this.nb_affected_msgs > 1 ? 's' : '') + ' non lu' + (this.nb_affected_msgs > 1 ? 's' : '') + ')</span>';
		}
		$nav.html(html);

		var $nav = $('ul#nav_tickets > li > a[href="#user_notifications_unaffected_tickets"]');

		if ($nav.length) {
			$nav.attr('nb_tickets', this.nb_affected);
			$nav.attr('nb_msgs', this.nb_affectd_msgs);
		}

		html = 'Tickets non assignés <span class="badge badge-' + (this.nb_unaffected > 0 ? 'info' : 'danger') + '" style="margin-left: 6px; font-size: 10px">' + this.nb_unaffected + '</span>';
		if (this.nb_unaffectd_msgs) {
			html += '&nbsp;&nbsp;<span style="font-size: 11px; font-style: italic; font-weight: normal">(' + this.nb_unaffectd_msgs + ' note' + (this.nb_unaffectd_msgs > 1 ? 's' : '') + ' non lue' + (this.nb_unaffectd_msgs > 1 ? 's' : '') + ')</span>';
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
			title = elements.length + ' Nouveaux tickets vous ont été assignés';
		} else {
			title = "Nouveau ticket assigné : " + elements[0].subj;
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
			if (elements[i].affected) {
				nb_my_tickets++;
			}
		}

		return nb_my_tickets > 2;
	}

	getLabel() {
		return 'Tickets';
	}
}

$(document).ready(function () {
	$('body').on('objectChange', function (e) {
		if (e.module === 'bimpticket' && e.object_name === 'Bimp_Ticket') {
			if (typeof (notifTicket) !== 'undefined' && notifTicket !== null) {
				notifTicket.refreshElements();
			}
		}
	});
});
