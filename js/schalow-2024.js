$(document).ready(function() {
    $('#inscription').change(function() {
        switch ($(this).val()) {
        case "en instruction":
        case "instructeur":
            $('#typePlaneur').parents('.row').addClass('d-none');
            $('#typePlaneur').val('');
            $('#equipe').parents('.row').addClass('d-none');
            $('#equipe').val('');
            break;
        case "en equipe":
            $('#typePlaneur').parents('.row').removeClass('d-none');
            $('#equipe').parents('.row').removeClass('d-none');
            break;
        case "en individuel":
            $('#typePlaneur').parents('.row').removeClass('d-none');
            $('#equipe').parents('.row').addClass('d-none');
            $('#equipe').val('');
            break;
        }
    });
    $('#presence').change(function() {
        if ($(this).val() === "c'est compliqué") {
            $('#presenceDetails').parents('.row').removeClass('d-none');
            $('#presenceDetails').val('');
        } else {
            $('#presenceDetails').parents('.row').addClass('d-none');
            $('#presenceDetails').val('');
        }
    });
});
