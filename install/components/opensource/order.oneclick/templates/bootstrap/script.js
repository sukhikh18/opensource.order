jQuery(document).ready(function($) {
    var $orderForm = $('[name="os-order-form"]');

    $orderForm.on('submit', function(e) {
        e.preventDefault();

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

        var query = {
            c: 'opensource:order.oneclick',
            action: 'saveOrderOneClick',
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
            data: serializeForm($orderForm),
            success: function (res) {
                console.log(res)
                alert('Check request results in console!');
            }
        });
    });
});
