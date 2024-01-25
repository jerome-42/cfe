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
    $("#abandon").click(function(event) {
	history.back();
	return event.preventDefault();
    });
    $('form').on('submit', function(event) {
	event.preventDefault();
    });
    $('#doRecord').click(function() {
	// on vérifie ici les entrées de l'utilisateur avant d'envoyer le formulaire au serveur

	$('.invalid-feedback').remove();

	// check date
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

	// DUREE
	// on remplace les virgules par des . (notation française versus anglaise)
	var duree = $('#duree').val().replace(',', '.');
	$('#duree').val(duree);
	// on vérifie que la durée est bonne
	if ($('#duree').val() === '') {
	    $('#duree').after($('<div class="invalid-feedback">').text('Type obligatoire'));
	} else if (isNaN(parseFloat($('#duree').val()))) {
	    $('#duree').after($('<div class="invalid-feedback">').text('Un nombre est attendu').css('display', ''));
	} else if (parseFloat($('#duree').val()) <= 0) {
	    $('#duree').after($('<div class="invalid-feedback">').text('Un nombre positif est attendu').css('display', ''));
	} else if (parseFloat($('#duree').val()) > 10) {
	    $('#duree').after($('<div class="invalid-feedback">').text('Impossible de saisir plus de 10 heures').css('display', ''));
	}
	// /DUREE

	// on affiche toutes les erreurs
	$('.invalid-feedback').css({ 'display': 'initial' });
	if ($('.invalid-feedback').length === 0) {
	    // il n'y a pas d'erreur, on envoie le formulaire
	    return nextWithPostData('/declaration', { dateCFE: date, duree: duree, type: $('#type').val(), beneficiaire: $('#beneficiaire').val(), commentaires: $('#commentaires').val() });
	}
    });
});
