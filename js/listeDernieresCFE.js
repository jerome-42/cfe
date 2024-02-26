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

var durationToHuman = function(d) {
    var hours = Math.round(parseInt(d) / 60);
    var minutes = parseInt(d) % 60;
    var ret = [];
    if (hours >= 2)
        ret.push(hours+" heures");
    else if (hours == 1)
        ret.push("1 heure");
    if (minutes > 1)
        ret.push(minutes+" minutes");
    else if (minutes == 1)
        ret.push("1 minute");
    return ret.join(' ');
};

$(document).ready(function() {
    $('#list > tbody > tr').each(function() {
	switch ($(this).attr('x-validation')) {
	case 'validated':
	    var by = $(this).find('.status').attr('x-validated-by');
	    $(this).find('.status').text('Validé ('+by+')');
	    $(this).addClass('table-success');
	    break;
	case 'rejected':
	    var by = $(this).find('.status').attr('x-validated-by');
	    $(this).find('.status').text('Rejeté ('+by+')');
	    $(this).addClass('table-danger');
	    break;
	case 'submitted':
	default:
	    $(this).find('.status').text('Soumis');
	    $(this).addClass('table-secondary');
	    break;
	}
    });
    $('.date').each(function() {
	var d = new Date($(this).text());
	var date = ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+('0'+d.getFullYear()).slice(-4);
	$(this).text(date);
    });

    $('.back').click(function() {
	window.location = '/';
    });

    $('.download').click(function() {
	var csvContent = "data:text/csv;charset=utf-8,";
	var row = $('#list > thead > tr > th').map(function() {
	    return $(this).text();
	});
	csvContent += row.get().toString() + "\r\n";
	$('#list > tbody > tr').each(function() {
	    console.log($(this).attr('x-status'));
	    if ($(this).attr('x-type') !== undefined)
		return;
	    var row = $(this).find('td').map(function(id) {
		if ($(this).attr('x-value') !== undefined)
		    return $(this).attr('x-value');
		return $(this).text();
	    });
	    csvContent += row.get().toString() + "\r\n";
	});
	var encodedUri = encodeURI(csvContent);
	var link = document.createElement("a");
	link.setAttribute("href", encodedUri);
	link.setAttribute("download", "cfe.csv");
	document.body.appendChild(link); // Required for FF
	link.click();
    });

    $('#search').on('keyup', function() {
	var sum = 0;
	var search = $(this).val().toLowerCase().replaceSpecialChars();
	$('#list > tbody > tr').each(function() {
	    if ($(this).hasClass('sum'))
		return;
	    var toDisplay = false;
	    if (search === '')
		toDisplay = true;
	    else {
		$(this).find('td').each(function() {
		    if ($(this).text().toLowerCase().replaceSpecialChars().indexOf(search) !== -1)
			toDisplay = true;
		});
	    }

	    if (toDisplay === false)
		$(this).hide();
	    else {
		sum += parseInt($(this).attr('x-duration'));
		$(this).show();
	    }
	});
	$('.durationSum').text(durationToHuman(sum));
    });
    $('#search').focus().trigger('keyup');
});
