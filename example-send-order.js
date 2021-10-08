var $orderForm = $('[name="os-order-form"]');

    /**
     * Serialize form data with files
     *
     * @param  [jQueryObject] $form form for serialize.
     * @return [FormData]
     */
var serializeForm = function ($form) {
    var formData = new FormData();

    // Append form data.
    var params = $form.serializeArray();
    $.each(params, function (i, val) {
        formData.append(val.name, val.value);
    });

    // Append files.
    $.each($form.find("input[type='file']"), function (i, tag) {
        $.each($(tag)[0].files, function (i, file) {
            formData.append(tag.name, file);
        });
    });

    return formData;
}

$orderForm.on('submit', function(e) {
    e.preventDefault();

    var query = {
        c: 'opensource:order',
        action: 'saveOrder',
        mode: 'ajax'
    };

    $.ajax({
        url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
        type: 'POST',
        dataType: 'json',
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        data: $orderForm.serialize(),
        success: function (res) {
            console(res);
        }
    });
});
