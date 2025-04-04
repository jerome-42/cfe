var currentElem = null;

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

var changeStatus = function(elem, newStatus) {
    var elem = elem.parents('td');
    var id = elem.parents('tr').attr('x-id');
    updateLine(id, newStatus, function() {
	elem.parents('tr').attr('x-validation', newStatus);
	setLineColor(elem.parents('tr'));
	displayValidation(elem);
	updateDetails();
    });
};

var displayValidation = function(elem) {
    switch (elem.parents('tr').attr('x-validation')) {
    case 'validated':
	var by = elem.attr('x-validated-by');
	return elem.html($('<button class="btn btn-warning cancel"><i class="bi bi-x-circle"></i><span class="d-none d-sm-block2">&nbsp;Annuler</span></button><span>&nbsp;Validé ('+by+')</span>'));
    case 'rejected':
	var by = elem.attr('x-validated-by');
	return elem.html($('<button class="btn btn-warning cancel"><i class="bi bi-x-circle"></i><span class="d-none d-sm-block2">&nbsp;Annuler</span></button><span>&nbsp;Rejeté ('+by+')</span>'));
    case 'submitted':
    default:
	return elem.html($('<button class="btn btn-success me-2 validate"><i class="bi bi-check-circle"></i><span class="d-none d-sm-block2">&nbsp;Valider</span></button><button class="btn btn-danger me-2 refuse"><i class="bi bi-x-circle"></i><span class="d-none d-sm-block2">&nbsp;Rejeter</span></button><button class="btn btn-primary me-2 edit"><i class="bi bi-pencil"></i><span class="d-none d-sm-block2">&nbsp;Editer</span></button><span>&nbsp;Soumis</span>'));
    }
};

var setLineColor = function(tr) {
    tr.removeClass([ 'table-success', 'table-danger', 'table-secondary' ]);
    switch (tr.attr('x-validation')) {
    case 'validated':
	return tr.addClass('table-success');
    case 'rejected':
	return tr.addClass('table-danger');
    case 'submitted':
    default:
	return tr.addClass('table-secondary');
    }
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

var pluralize = function(v, noun) {
    if (v > 1)
	return v+" "+noun+"s";
    return v+" "+noun;
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
		data.push(durationToHuman(res.thecfetodo)+" à réaliser");
	    else
		data.push("pas de CFE à réaliser");
	    if (res.validated > 0)
		data.push(durationToHuman(res.validated) + " validées");
            if (res.private > 0)
                data.push(durationToHuman(res.private)+ " sections/privés qui ne sont pas prises en compte (car au delà des 16 heures privés comptant pour la CFE) soit au total "+durationToHuman(res.validated+res.private)+" réalisées");
	    if (res.submited === 0)
		data.push("pas de CFE en attente de validation");
	    else
		data.push(pluralize(durationToHuman(res.submited), "heure")+" en attente de validation");
	    $('#details').html(data.join(", "));
        }
    });
};

$(document).ready(function() {
    $('#list > tbody > tr').each(function() {
	setLineColor($(this));
    });
    $('.validation').each(function() {
	displayValidation($(this));
    });
    $('.sudo').click(function() {
	var numGivav = $(this).attr('x-num');
	window.location = '/sudo?numero='+numGivav;
    });

    $('.back').click(function() {
	history.back();
    });

    $('#updateParams').click(function() {
        let data = {
	    num: $('#list').attr('x-numero'),
	    cfeTODO: $('#cfeTODO').val(),
	    enableMultiDateDeclaration: 0,
            va: '',
	}
	if ($('#enableMultiDateDeclaration').is(':checked'))
	    data.enableMultiDateDeclaration = 1;
        if ($('#va').length > 0)
            data.va = $('#va').val();
	$.ajax({
            url: '/updateMembreParams',
            data: data,
            type: 'POST',
            error: function() {
		alert("Impossible");
            },
            success: function(res) {
		updateDetails();
            }
	});
    });

    $(document.body).on('click', '.cancel', function() {
	changeStatus($(this), 'submitted');
    });

    $(document.body).on('click', '.edit', function() {
	window.location = '/declaration?id=' + $(this).parents('tr').attr('x-id');
    });

    $(document.body).on('click', '.validate', function() {
	changeStatus($(this), 'validated');
    });

    $(document.body).on('click', '.refuse', function() {
	$('#rejectedCause').val('');
	$('.invalid-feedback').remove();
	currentElem = $(this);
	$('#modalRejected').modal('show');
    });

    initRejectedModal();

    $('.download').click(function() {
	var csvContent = "data:text/csv;charset=utf-8,";
	var row = $('#list > thead > tr > th').map(function() {
	    return $(this).text();
	});
	csvContent += row.get().toString() + "\r\n";
	$('#list > tbody > tr').each(function() {
	    var row = $(this).find('td').map(function() {
		if ($(this).attr('x-value') !== undefined)
		    return $(this).attr('x-value');
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

    $('.switchToVA').click(function() {
        var id = $(this).parents('tr').attr('x-id');
        $.ajax({
            url: '/switchToVA',
            data: { id },
	    dataType: 'json',
            type: 'POST',
            error: function() {
	        alert("Impossible");
            },
            success: function(res) {
            }
        });
    });
});
