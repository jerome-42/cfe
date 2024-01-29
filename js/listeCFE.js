String.prototype.replaceSpecialChars = function() {
    var newString = this;
    newString = newString
	.replace(/[âäà]/gm, 'a')
	.replace(/[êëéèê]/gm, 'e')
	.replace(/[îï]/gm, 'o')
	.replace(/[ôö]/gm, 'o')
	.replace(/[ù]/gm, 'u');
    return newString;
};

$(document).ready(function() {
    $('#list > tbody > tr').each(function() {
	switch ($(this).attr('x-validation')) {
	case 'Validé':
	    $(this).addClass('table-primary');
	    break;
	case 'Rejeté':
	    $(this).addClass('table-danger');
	    break;
	case 'Soumis':
	default:
	    $(this).addClass('table-secondary');
	    break;
	}
    });

    $('.back').click(function() {
	window.location = '/';
    });

    $('#search').on('keyup', function() {
	var search = $(this).val().toLowerCase().replaceSpecialChars();
	$('#list > tbody > tr').each(function() {
	    if (search === '')
		$(this).show();
	    else {
		var match = false;
		$(this).find('td').each(function() {
		    if ($(this).text().toLowerCase().replaceSpecialChars().indexOf(search) !== -1)
			match = true;
		});

		if (match === false)
		    $(this).hide();
		else
		    $(this).show();
	    }
	});
    });
    $('#search').focus();
});
