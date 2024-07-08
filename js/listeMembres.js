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

var changeNoRevealWhenInDebt = function(num, status) {
    $.ajax({
        url: '/changeNoRevealWhenInDebt',
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

var displayNoRevealWhenInDebt = function(elem) {
    $('.tooltip').hide();
    if (elem.parents('tr').attr('x-noRevealWhenInDebt') === '1')
	elem.html($('<button type="button" class="btn btn-danger"><i class="bi bi-circle"></i><span class="d-none d-sm-block2" data-bs-toggle="tooltip" data-bs-title="actuellement le pilote n\'est pas listé si son compte est négatif, cliquez pour que le pilote fasse à nouveau parti de la liste des pilotes en négatif">&nbsp;n\'est pas affiché</span></button>'));
    else
	elem.html($('<button type="button" class="btn btn-success"><i class="bi bi-check2-circle"></i><span class="d-none d-sm-block2" data-bs-toggle="tooltip" data-bs-title="actuellement le pilote est listé si son compte est négatif, cliquez pour que le pilote n\'en fasse plus parti">&nbsp;est affiché</span></button>'));
    elem.find('[data-bs-toggle="tooltip"]').tooltip();
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

var updateList = function() {
    var sumValidated = 0;
    var sumTODO = 0;
    var sumPeople = 0;
    var search = $('#search').val().toLowerCase().replaceSpecialChars();
    $('#list > tbody > tr').each(function() {
	var displayLine = false;
	if ($(this).hasClass('sum')) // on ne filtre pas la dernière ligne qui affiche le total
	    return;
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
        case 'noRevealWhenInDebt':
	    if ($(this).attr('x-noRevealWhenInDebt') === '0')
		displayLine = false;
            break;
	case 'realizedMoreThan0':
	    if (parseFloat($(this).attr('x-cfeValidated')) === 0)
		displayLine = false;
	    break;
	case 'validated':
	    if ($(this).attr('x-cfeCompleted') === '0')
		displayLine = false;
	    break;
	}
	if (displayLine === false)
	    $(this).hide();
	else {
	    sumValidated += parseInt($(this).attr('x-cfeValidated'));
	    sumTODO += parseInt($(this).attr('x-cfeTODO'));
            sumPeople += 1;
	    $(this).show();
	}
    });
    if (sumValidated > 0) {
	if (sumTODO > 0) {
	    $('.sumValidated').find('.progress')
		.attr('aria-valuenow', sumValidated)
		.attr('aria-valueMax', sumTODO);
	    $('.sumValidated').find('.progress-bar').css('width', sumValidated/sumTODO+"%");
	    $('.sumValidated').find('.progress').show();
	    $('.sumValidated').find('.progrss-bar').show();
	}
	$('.sumValidatedLabel').text(durationToHuman(sumValidated));
    }
    else {
	$('.sumValidated').find('.progress').hide();
	$('.sumValidated').find('.progrss-bar').hide();
	$('.sumValidated').text("0");
    }
    if (sumTODO > 0)
	$('.sumTODO').text(durationToHuman(sumTODO));
    else
	$('.sumTODO').text("0");
    $('.sumPeople').text(sumPeople+' personnes');
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
    $('#list').find('td.noRevealWhenInDebt')
	.each(function() {
	    displayNoRevealWhenInDebt($(this));
	})
	.click(function() {
	    if ($(this).parents('tr').attr('x-noRevealWhenInDebt') === '1') {
		$(this).parents('tr').attr('x-noRevealWhenInDebt', 0);
		changeNoRevealWhenInDebt($(this).parents('tr').attr('x-num'), false);
	    }
	    else {
		$(this).parents('tr').attr('x-noRevealWhenInDebt', 1);
		changeNoRevealWhenInDebt($(this).parents('tr').attr('x-num'), true);
	    }
	    displayNoRevealWhenInDebt($(this));
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
    updateList();
});
