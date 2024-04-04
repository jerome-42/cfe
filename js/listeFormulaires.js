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

var initTooltips = function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
};

var updateList = function() {
    var search = $('#search').val().toLowerCase().replaceSpecialChars();
    $('#list > tbody > tr').each(function() {
	var displayLine = false;
	if (search === '')
	    displayLine = true;
	else {
	    $(this).find('td').each(function() {
		if ($(this).text().toLowerCase().replaceSpecialChars().indexOf(search) !== -1)
		    displayLine = true;
	    });
	}
	if (displayLine === false)
	    $(this).hide();
	else
	    $(this).show();
    });
};

$(document).ready(function() {
    $('.back').click(function() {
	window.location = '/';
    });
    $('.editComment').click(function() {
	var id = $(this).parents('tr').attr('x-num');
        $('#gliderId').val(id);
	var comment = $(this).parents('tr').attr('x-comment');
	$('#comment').val(comment);
	$('#modalEditComment').modal('show');
    });

    $('.clearComment').click(function() {
	$('#comment').val('');
        $('#addComment').click();
    });

    $('#modalEditComment').on('shown.bs.modal', function() {
        $('#comment').focus();
    });

    $('#addComment').click(function() {
        $('#formEditComment').trigger('submit');
    });

    $('#filter').on('change', function() {
	updateList();
    });

    $('#search').on('keyup', function() {
	updateList();
    });
    initTooltips();
    $('#search').focus();
    updateList();
});
