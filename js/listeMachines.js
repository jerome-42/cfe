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

    $('.displayDetails').click(function() {
	var numGivav = $(this).parents('tr').attr('x-num');
	window.location = '/detailsMembre?numero='+numGivav;
    });

    $('#filter').on('change', function() {
	updateList();
    });

    $('#search').on('keyup', function() {
	updateList();
    });
    $('#search').focus();
    updateList();
});
