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

var changeStatus = function(elem, newStatus, rejectedCause) {
    var elem = elem.parents('td');
    var id = elem.parents('tr').attr('x-id');
    updateLine(id, newStatus, rejectedCause, function() {
	elem.parents('tr').attr('x-validation', newStatus);
	setLineColor(elem.parents('tr'));
	displayValidation(elem);
    });
};

var displayValidation = function(elem) {
    switch (elem.parents('tr').attr('x-validation')) {
    case 'validated':
	return elem.html($('<button class="btn btn-warning cancel"><i class="bi bi-x-circle"></i><span class="d-none d-sm-block2">&nbsp;Annuler</span></button><span>&nbsp;Validé</span>'));
    case 'rejected':
	return elem.html($('<button class="btn btn-warning cancel"><i class="bi bi-x-circle"></i><span class="d-none d-sm-block2">&nbsp;Annuler</span></button><span>&nbsp;Rejeté</span>'));
    case 'submitted':
    default:
	return elem.html($('<button class="btn btn-success me-2 validate"><i class="bi bi-check-circle"></i><span class="d-none d-sm-block2">&nbsp;Valider</span></button><button class="btn btn-danger refuse"><i class="bi bi-x-circle"></i><span class="d-none d-sm-block2">&nbsp;Rejeter</span></button><span>&nbsp;Soumis</span>'));
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

$(document).ready(function() {
    $('#list > tbody > tr').each(function() {
	setLineColor($(this));
    });
    $('.validation').each(function() {
	displayValidation($(this));
    });

    $('.back').click(function() {
	history.back();
    });

    $(document.body).on('click', '.cancel', function() {
	changeStatus($(this), 'submitted');
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
