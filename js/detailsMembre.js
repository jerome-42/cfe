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

var displayValidation = function(elem) {
    switch (elem.parents('tr').attr('x-validation')) {
    case 'Validé':
    case 'Rejeté':
	return elem.html($('<button class="btn btn-warning cancel"><i class="bi bi-x-circle"></i>&nbsp;Annuler</button>'));
    case 'Soumis':
    default:
	return elem.html($('<button class="btn btn-success me-2 validate"><i class="bi bi-check-circle"></i>&nbsp;Valider</button><button class="btn btn-danger refuse"><i class="bi bi-x-circle"></i>&nbsp;Rejeter</button>'));
    }
};

var updateDetails = function() {
    $.ajax({
        url: '/detailsMembreStats',
        data: { num: $('#list').attr('x-numero') },
	dataType: 'json',
        type: 'POST',
        error: function() {
	    alert("Impossible");
        },
        success: function(res) {
	    var data = [];
	    if (res.thecfetodo > 0)
		data.push(res.thecfetodo+" heures à réaliser");
	    else
		data.push("pas de CFE à réaliser");
	    if (res.validated > 0)
		data.push(res.validated+" heure(s) validée(s)");
	    if (res.submited === 0)
		data.push("pas de CFE en attente de validation");
	    else
		data.push(res.submited+" heure(s) en attente de validation");
	    $('#details').html(data.toString());
        }
    });
};

var updateLine = function(id, statut, cb) {
    $.ajax({
        url: '/updateCFELine',
        data: { num: $('#list').attr('x-numero'), id: id, statut: statut },
        type: 'POST',
        error: function() {
	    alert("Impossible");
        },
        success: function(res) {
	    cb();
        }
    });
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
    $('.validation').each(function() {
	displayValidation($(this));
    });

    $('.back').click(function() {
	history.back();
    });

    $(document.body).on('click', '.cancel', function() {
	var elem = $(this).parents('td');
	var id = $(this).parents('tr').attr('x-id');
	updateLine(id, 'Soumis', function() {
	    $(this).parents('tr').attr('x-validation', 'Soumis');
	    displayValidation(elem);
	});
    });

    $(document.body).on('click', '.validate', function() {
	var elem = $(this).parents('td');
	var id = $(this).parents('tr').attr('x-id');
	updateLine(id, 'Validé', function() {
	    $(this).parents('tr').attr('x-validation', 'Validé');
	    displayValidation(elem);
	});
    });

    $(document.body).on('click', '.refuse', function() {
	var elem = $(this).parents('td');
	var id = $(this).parents('tr').attr('x-id');
	updateLine(id, 'Refusé', function() {
	    $(this).parents('tr').attr('x-validation', 'Refusé');
	    displayValidation(elem);
	});
    });

    $('.download').click(function() {
	var csvContent = "data:text/csv;charset=utf-8,";
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
    updateDetails();
    $('#search').focus();
});
