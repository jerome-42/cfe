$(document).ready(function() {
    $('td.estAdmin').each(function() {
	if ($(this).attr('value') === '1')
	    $(this).append($('<i class="bi bi-check">'));
    });

    $('.sudo').click(function() {
	window.location = '/sudo?numero='+$(this).attr('x-num');
    });

    $('.back').click(function() {
	window.location = '/';
    });
});
