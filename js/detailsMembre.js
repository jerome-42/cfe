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
	history.back();
    });

    $('.download').click(function() {
	var csvContent = "data:text/csv;charset=utf-8,\r\n";
	var row = $('#list > thead > tr > th').map(function() {
	    return $(this).text();
	});
	csvContent += row.get().toString() + "\r\n";
	$('#list > tbody > tr').each(function() {
	    var row = $(this).find('td').map(function() {
		return $(this).text();
	    });
	    csvContent += row.get().toString() + "\r\n";
	});
	var encodedUri = encodeURI(csvContent);
	var link = document.createElement("a");
	link.setAttribute("href", encodedUri);
	link.setAttribute("download", "cfe - "+$(this).attr('x-name')+".csv");
	document.body.appendChild(link); // Required for FF
	link.click();
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
