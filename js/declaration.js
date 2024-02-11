var nextWithPostData = function(url, params) {
    var form = document.createElement("form");
    form.setAttribute("method", "POST");
    form.setAttribute("accept-charset", "UTF-8");
    form.setAttribute("action", url);

    for(var key in params) {
        if(params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);

            form.appendChild(hiddenField);
        }
    }

    document.body.appendChild(form);
    form.submit();
};

$(document).ready(function() {
    $('#dateCFE').focus();
    $(".abandon").click(function(event) {
	window.location = '/';
	return event.preventDefault();
    });
    $("#abandon").click(function(event) {
	window.location = '/';
	return event.preventDefault();
    });
    $('form').on('submit', function(event) {
	event.preventDefault();
    });
    $('#doRecord').click(function() {
	$('.doRecord').trigger('click');
    });
    $('.doRecord').click(function() {
	// on vérifie ici les entrées de l'utilisateur avant d'envoyer le formulaire au serveur

	$('.invalid-feedback').remove();

	var startDate = null;
	var stopDate = null;
	// check date
	if ($('#dateCFE').length === 1) {
	    var date = $('#dateCFE').val();
	    if (date === '') {
		$('#dateCFE').after($('<div class="invalid-feedback">').text('Date obligatoire'));
	    } else {
		var now = new Date();
		date = new Date(date);
		if (date.getFullYear() != now.getFullYear()) { // l'année doit être l'année en cours
		    $('#dateCFE').after($('<div class="invalid-feedback">').text(date.getFullYear()+" n'est pas une année correcte"));
		} else if (date.getTime() > now.getTime()) { // la date saisie est après aujourd'hui !
		    $('#dateCFE').after($('<div class="invalid-feedback">').text("Impossible de pré-déclarer !"));
		}
	    }
	    startDate = date;
	    stopDate = date;
	} else {
	    // start
	    startDate = $('#startDateCFE').val();
	    if (startDate === '') {
		$('#startDateCFE').parents('.row').after($('<div class="invalid-feedback">').text('Date obligatoire'));
	    } else {
		var now = new Date();
		startDate = new Date(startDate);
		if (startDate.getFullYear() != now.getFullYear()) { // l'année doit être l'année en cours
		    $('#startDateCFE').parents('.row').after($('<div class="invalid-feedback">').text(startDate.getFullYear()+" n'est pas une année correcte"));
		} else if (startDate.getTime() > now.getTime()) { // la date saisie est après aujourd'hui !
		    $('#startDateCFE').parents('.row').after($('<div class="invalid-feedback">').text("Impossible de pré-déclarer !"));
		}
	    }
	    // stop
	    stopDate = $('#stopDateCFE').val();
	    if (stopDate === '') {
		$('#stopDateCFE').parents('.row').after($('<div class="invalid-feedback">').text('Date obligatoire'));
	    } else {
		var now = new Date();
		stopDate = new Date(stopDate);
		if (stopDate.getFullYear() != now.getFullYear()) { // l'année doit être l'année en cours
		    $('#stopDateCFE').parents('.row').after($('<div class="invalid-feedback">').text(stopDate.getFullYear()+" n'est pas une année correcte"));
		} else if (stopDate.getTime() > now.getTime()) { // la date saisie est après aujourd'hui !
		    $('#stopDateCFE').parents('.row').after($('<div class="invalid-feedback">').text("Impossible de pré-déclarer !"));
		}
	    }
	    // start vs stop
	    if (startDate instanceof Date && stopDateCFE instanceof Date && startDate.getTime() > stopDate.getTime()) {
		$('#startDateCFE').parents('.row').after($('<div class="invalid-feedback">').text("la date de fin doit être postérieure à la date de début"));
	    }
	}

	// heure
	// on vérifie que la durée est bonne
	if ($('#durationHour').val() === '') {
	    $('#durationError').append($('<div class="invalid-feedback">').text("Le nombre d'heure est attendu"));
	} else if (isNaN(parseInt($('#durationHour').val()))) {
	    $('#durationError').append($('<div class="invalid-feedback">').text("Le nombre d'heure doit être un chiffre").css('display', ''));
	} else if (parseInt($('#durationHour').val()) < 0) {
	    $('#durationError').append($('<div class="invalid-feedback">').text("Le nombre d'heure doit être chiffre positif").css('display', ''));
	} else if (parseInt($('#durationHour').val()) > 10) {
	    $('#durationError').append($('<div class="invalid-feedback">').text('Impossible de saisir plus de 10 heures').css('display', ''));
	} else { // /heure
	    if ($('#durationMinute').val() === '') {
		$('#durationError').append($('<div class="invalid-feedback">').text("Le nombre de minutes est obligatoire"));
	    } else if (isNaN(parseInt($('#durationMinute').val()))) {
		$('#durationError').append($('<div class="invalid-feedback">').text('Le nombre de minute doit être un nombre').css('display', ''));
	    } else if (parseInt($('#durationMinute').val()) < 0) {
		$('#durationError').append($('<div class="invalid-feedback">').text("Le nombre de minute doit être un nombre positif").css('display', ''));
	    } else if (parseInt($('#durationMinute').val()) >= 60) {
		$('#durationError').append($('<div class="invalid-feedback">').text('Le nombre de minute doit être un nombre entre 0 et 59').css('display', ''));
	    } else { // /minutes
		if (parseInt($('#durationHour').val()) == 0 && parseInt($('#durationMinute').val()) == 0) {
		    $('#durationError').append($('<div class="invalid-feedback">').text('La durée ne peut être nulle').css('display', ''));
		}}
	}

	if ($('#details').val() === '')
	    $('#details').after($('<div class="invalid-feedback">').text("Il faut le détail du travail effectué pour l'évaluation du travail effectué"));
	if ($('#details').val().length < 10)
	    $('#details').after($('<div class="invalid-feedback">').text("C'est un peu court, il faut plus d'information pour permettre l'évaluation de ce travail"));

	// on affiche toutes les erreurs
	$('.invalid-feedback').css({ 'display': 'initial' });
	if ($('.invalid-feedback').length === 0) {
	    // il n'y a pas d'erreur, on envoie le formulaire
	    var values = { startDateCFE: (+startDate)/1000,
			   stopDateCFE: (+stopDate)/1000,
			   durationHour: parseInt($('#durationHour').val()),
			   durationMinute: parseInt($('#durationMinute').val()),
			   type: $('#type').val(),
			   beneficiary: $('#beneficiary').val(),
			   details: $('#details').val()
			 };
	    if ($('form').attr('x-id') !== '')
		values['id'] = $('form').attr('x-id');
	    return nextWithPostData('/declaration', values);
	}
    });

    [ $('#type'), $('#beneficiary') ].forEach(function(elem) {
	var value = elem.attr('value');
	if (value != '')
	    elem.find('option[value="'+value+'"]').attr('selected', 'selected');
    });

    $('.importCSV').click(function() {
	window.location = '/importCSV';
    });
});
