let emailIsValid = function(email) {
    var validRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;

    return email.match(validRegex);
};

$(document).ready(function() {
    $('form').on('submit', function(e) {
        $('.invalid-feedback').remove();
        let email = $('#email').val();
        if (email === '' || emailIsValid(email) === false)
            $('#email').after($('<div class="invalid-feedback">').text("L'adresse email n'est pas valide"));
        $('input').each(function() {
            if ($(this).attr('id') === 'email')
                return;
            if ($(this).val() === '')
                $(this).after($('<div class="invalid-feedback">').text("La saisie est obligatoire"));
        });
        $('.invalid-feedback').css({ 'display': 'initial' });
        if ($('.invalid-feedback').length > 0)
            e.preventDefault();
    });
    $('#valide').change(function() {
        if ($(this).is(':checked'))
            $('#submit').prop('disabled', false);
        else
            $('#submit').prop('disabled', true);
    });
});
