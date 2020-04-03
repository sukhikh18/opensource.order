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
                if(!typeof(res.status)) return;

                if('success' != res.status) {
                } else {
                    $.ajax({
                        url: '/user/order/payment/',
                        type: 'GET',
                        dataType: 'HTML',
                        data: {
                            'ORDER_ID': res.data.order_id,
                        },
                    }).done(function (payForm) {

                        var $payment = $('<div id="payment-content">').append(payForm);
                        if(typeof($.fancybox)) {
                            $.fancybox.open($payment);
                        } else {
                            // Force payment crunch example
                            $payment.find('form').removeAttr('target').submit();
                            var href = $payment.find('a').attr('href');
                            if (href) window.location.href = href;

                            // When some go wrong...
                            setTimeout(function () {
                                // Its no bug, its future.
                                if(typeof($.fancybox)) {
                                    $.fancybox.open($payment);
                                }
                                else {
                                    $orderForm.prepend($payment);
                                }
                            }, 5000);
                        }
                    });
                }
            }
        });
    });
});
