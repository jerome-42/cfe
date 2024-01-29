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

var changeAdmin = function(num, statut) {
    $.ajax({
        url: '/changeAdmin',
        data: { num: num, statut: statut },
        type: 'POST',
        error: function() {
	    alert("Impossible");
        },
        success: function(res) {
	    // ok
        }
    });
};

var displayEstAdmin = function(elem) {
    if (elem.attr('value') === '1')
	elem.html($('<button type="button" class="btn btn-danger"><i class="bi bi-check2-circle"></i>&nbsp;Révoquer les droits administrateur</button>'));
    else
	elem.html($('<button type="button" class="btn btn-success"><i class="bi bi-circle"></i>&nbsp;Passer administrateur</button>'));
};

$(document).ready(function() {
    $('#list').find('td.estAdmin')
	.each(function() {
	    displayEstAdmin($(this));
	})
	.click(function() {
	    if ($(this).attr('value') === '1') {
		$(this).attr('value', 0);
		changeAdmin($(this).parent('tr').attrs('x-num'), false);
	    }
	    else {
		$(this).attr('value', 1);
		changeAdmin($(this).parent('tr').attrs('x-num'), true);
	    }
	    displayEstAdmin($(this));
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
