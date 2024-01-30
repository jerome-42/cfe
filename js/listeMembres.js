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

var changeAdmin = function(num, status) {
    $.ajax({
        url: '/changeAdmin',
        data: { num: num, status: status },
        type: 'POST',
        error: function() {
	    alert("Impossible");
        },
        success: function(res) {
	    // ok
        }
    });
};

var displayIsAdmin = function(elem) {
    if (elem.parents('tr').attr('x-isAdmin') === '1')
	elem.html($('<button type="button" class="btn btn-danger"><i class="bi bi-check2-circle"></i><span class="d-none d-sm-block2">&nbsp;Révoquer les droits administrateur</span></button>'));
    else
	elem.html($('<button type="button" class="btn btn-success"><i class="bi bi-circle"></i><span class="d-none d-sm-block2">&nbsp;Passer administrateur</span></button>'));
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
	switch ($('#filter').val()) {
	case '0': // CFE = 0
	    if ($(this).attr('x-cfeTODO') !== '0')
		displayLine = false;
	    break;
	case 'admin':
	    if ($(this).attr('x-isAdmin') === '0')
		displayLine = false;
	    break;
	case 'not0notDefault':
	    if (parseFloat($(this).attr('x-cfeTODO')) === '0' || parseFloat($(this).attr('x-cfeTODO')) === defaultCFE_TODO)
		displayLine = false;
	    break;
	case 'validated':
	    if ($(this).attr('x-cfeCompleted') === '0')
		displayLine = false;
	    break;
	}
	if (displayLine === false)
	    $(this).hide();
	else
	    $(this).show();
    });
};

$(document).ready(function() {
    $('#list').find('td.isAdmin')
	.each(function() {
	    displayIsAdmin($(this));
	})
	.click(function() {
	    if ($(this).parents('tr').attr('x-isAdmin') === '1') {
		$(this).parents('tr').attr('x-isAdmin', 0);
		changeAdmin($(this).parents('tr').attr('x-num'), false);
	    }
	    else {
		$(this).parents('tr').attr('x-isAdmin', 1);
		changeAdmin($(this).parents('tr').attr('x-num'), true);
	    }
	    displayIsAdmin($(this));
	});
    $('#list > tbody > tr').each(function() {
	if ($(this).attr('x-cfeCompleted') === '1') {
	    $(this).addClass('table-success');
	}
    });

    $('.sudo').click(function() {
	var numGivav = $(this).parents('tr').attr('x-num');
	window.location = '/sudo?numero='+numGivav;
    });

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
});
