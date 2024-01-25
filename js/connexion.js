$(document).ready(function() {
    $('input[name="login"]').focus();
    $('form').on('submit', function(event) {
	$('.alert').remove();
	$('button[type="submit"]').remove();
	$('#spinner').removeClass('visually-hidden');
    });
});
