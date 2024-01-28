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
		changeAdmin($(this).attr('x-num'), false);
	    }
	    else {
		$(this).attr('value', 1);
		changeAdmin($(this).attr('x-num'), true);
	    }
	    displayEstAdmin($(this));
	});

    $('.sudo').click(function() {
	window.location = '/sudo?numero='+$(this).attr('x-num');
    });

    $('.back').click(function() {
	window.location = '/';
    });

    $('#search').on('keyup', function() {
	var search = $(this).val().toLowerCase();
	$('#list > tbody > tr').each(function() {
	    if (search === '')
		$(this).show();
	    else {
		if ($(this).find('td:nth-child(3)').text().toLowerCase().indexOf(search) === -1)
		    $(this).hide();
		else
		    $(this).show();
	    }
	});
    });
    $('#search').focus();
});
