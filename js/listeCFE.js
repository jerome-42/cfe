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
	case 'validated':
	    $(this).find('.status').text('Validé');
	    $(this).addClass('table-success');
	    break;
	case 'rejected':
	    $(this).find('.status').text('Rejeté');
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


    $('.delete').click(function() {
	$('#modalConfirm').attr('x-id', $(this).attr('x-id'));
	$('#modalConfirm').modal('show');
    });

    $('#deletionConfirm').click(function() {
	$.ajax({
            url: '/deleteCFELine',
            data: { id:
		    $('#modalConfirm').attr('x-id'),
		  },
            type: 'POST',
            error: function() {
		alert("Impossible");
            },
            success: function(res) {
		window.location.reload();
            }
	});
    });

    $('.edit').click(function() {
	var id = $(this).attr('x-id');
	window.location = '/declaration?id='+id;
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
