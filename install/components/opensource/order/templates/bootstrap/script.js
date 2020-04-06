jQuery(document).ready(function($) {
    var $orderForm = $('[name="os-order-form"]'),
        $location = $('.location-search', $orderForm);

    $orderForm.on('change', '[name="delivery_id"]', function(event) {
        var formData = $orderForm.serialize();
        var query = {
            c: 'opensource:order',
            action: 'calculateSummary',
            mode: 'ajax'
        };

        var request = $.ajax({
            url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
            type: 'POST',
            data: formData
        });

        request.done(function (result) {
            if('success' == result.status) {

                $('[data-products-base-price]')
                    .attr('data-products-base-price', result.data['PRODUCTS_BASE_PRICE'])
                    .html(result.data['PRODUCTS_BASE_PRICE_DISPLAY']);

                $('[data-products-price]')
                    .attr('data-products-price', result.data['PRODUCTS_PRICE'])
                    .html(result.data['PRODUCTS_PRICE_DISPLAY']);

                $('[data-delivery-price]')
                    .attr('data-delivery-price', result.data['DELIVERY_PRICE'])
                    .html(result.data['DELIVERY_PRICE_DISPLAY']);

                $('[data-sum]')
                    .attr('data-sum', result.data['SUM'])
                    .html(result.data['SUM_DISPLAY']);
            }
        });
    });
});
