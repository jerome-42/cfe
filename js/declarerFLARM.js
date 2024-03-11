$(document).ready(function() {
    $('.back').click(function() {
	window.location = '/';
    });
    $('#upload').change(function() {
	$('#form').hide();
	$('#dropZone').parents('.row').hide();
	$('.visually-hidden').removeClass('visually-hidden');
	$('#form').submit();
    });
});
