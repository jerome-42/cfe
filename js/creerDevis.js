var files = {};

let fileExists = function(fileName) {
    let exists = false;
    Object.keys(files).forEach(function(name) {
        if (name === fileName)
            exists = true;
    });
    return exists;
};

$(document).ready(function() {
    $('.abandon').click(function() {
        window.location = '/';
    });
    $('#details').focus();
    $('#fileUpload').fileupload({
        add: function (e, data) {
            data.files.forEach(function(file) {
                if (fileExists(file.name) === true)
                    return;
                let reader = new FileReader();
	        reader.onload = function(e) {
		    file.data = e.target.result;
	        };
	        reader.onerror = function(e) {
		    console.log('Error : ' + e.type);
	        };
	        reader.readAsBinaryString(file);
                files[file.name] = file;
                $('#list > tbody').append($('<tr>', { 'x-filename': file.name })
                                          .append($('<td>').text(file.name))
                                          .append($('<td>').text(file.type))
                                          .append($('<td>').text(file.size))
                                          .append($('<td>').append($('<i>', { class: 'bi bi-trash-fill deleteFile' })))
                                         );
            });
        }
    });

    $('#quote').submit(function(e) {
        let data = { details: $('#details').val(), files: [] };
        Object.keys(files).forEach(function(name) {
            let file = files[name];
            let f = btoa(file.data);
            data.files.push({
                name: name,
                size: file.size,
                type: file.type,
                data: f,
            });
        });
        $.ajax({
            url: '/creerDevis',
            data: data,
            type: 'POST',
            error: function() {
	        alert("Impossible");
            },
            success: function(res) {
                alert('Devis envoyé');
                window.location = '/';
            }
        });
        return e.preventDefault();
    });

    $(document).on('click', '.deleteFile', function() {
        let fileName = $(this).parents('tr').attr('x-filename');
        delete(files[fileName]);
        $(this).parents('tr').remove();
    });

});
