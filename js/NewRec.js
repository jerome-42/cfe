$(document).ready(function() {
    $('#dateCFE').focus();
    $("#Abandon").click(function() {
	history.back();
    });
    $('#doRecord').click(function() {
	$('#newRec').submit();
    });
    $('newRec').on('submit', function(event) {
	// on vérifie ici les entrées
	$('.invalid-feedback').remove();

	// TODO check date

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
	$('.invalid-feedback').css({ 'display': 'initial' });
	if ($('.invalid-feedback').length > 0) {
	    // il y a des erreurs, on n'envoie pas le formulaire
	    return event.preventDefault();
	}
	// pas d'erreur, on envoie le formulaire
    });
});
