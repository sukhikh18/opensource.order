<?php

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var OpenSourceOrderComponent $component */

if( ! defined('REQUIRED_SIGN')) {
    define('REQUIRED_SIGN', '<span class="required" style="color: red;" title="' .
        Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_FIELD_REQUIRED') . '">*</span>');
}

if( ! function_exists('getInputAttributes')) {
    function getInputAttributes( $arProp ) {
        $attributes = [
            'type' => 'text',
            'class' => 'form-control',
            'id' => strtolower($arProp['FORM_LABEL']),
            'name' => $arProp['FORM_NAME'],
            'value' => $arProp['VALUE'],
        ];

        if('FILE' === strtoupper($arProp['TYPE'])) {
            $attributes['class'] .= '-file';
            $attributes['type'] = 'file';
        }

        array_walk($attributes, function(&$value, $key) {
            $value = $key . '="' . $value . '"';
        });

        return implode(' ', $attributes);
    }
}

?>
<style>
    .form-group--property_zip,
    .form-group--property_location,
    .form-group--property_city {
        display: none;
    }
</style>
<form action="<?= $APPLICATION->GetCurPage() ?>"
    method="post"
    name="os-order-form"
    id="os-order-form"
    class="order-form"
    enctype="multipart/form-data">
<div class="row">
    <div class="col-md-3">

    <input type="hidden" name="person_type_id" value="<?=$arParams['PERSON_TYPE_ID']?>">
    <input type="hidden" name="delivery_id" value="<?= $arParams['DEFAULT_DELIVERY_ID'] ?>">
    <input type="hidden" name="pay_system_id" value="<?= $arParams['DEFAULT_PAY_SYSTEM_ID'] ?>">
    <input type="hidden" name="productId" value="<?= $arParams['DEFAULT_PRODUCT_ID'] ?>">

    <?php

    foreach ($arResult['PROPERTIES'] as $propCode => $arProp) {
        // array_walk($arProp['ERRORS'], function(&$error, $k) {
        //     $error = '<span class="error">' . $error->getMessage() . '</span>';
        // });

        switch (strtoupper($arProp['TYPE'])) {
            case 'LOCATION':
                printf('
                    <div class="order-form__group form-group form-group--%1$s location">
                        <label for="order-%1$s">%2$s%3$s</label>
                        <select class="form-control location-search" name="%4$s" id="%1$s">
                            <option data-data="%5$s" value="%7$s">%6$s</option>
                        </select>
                        %8$s
                        %9$s
                    </div>
                    ',
                    strtolower($arProp['FORM_LABEL']),
                    $arProp['NAME'],
                    $arProp['IS_REQUIRED'] ? REQUIRED_SIGN : '',
                    $arProp['FORM_NAME'],
                    Json::encode($arProp['LOCATION_DATA']),
                    $arProp['LOCATION_DATA']['label'],
                    $arProp['VALUE'],
                    $arProp['FORM_DESC'] ? '<small class="form-text text-muted">' . $arProp['DESC'] . '</small>' : '',
                    implode('<br>', $arProp['ERRORS'])
                );
                break;

            case 'ENUM':
                if("Y" === $arProp['MULTIELEMENT']) {
                    array_walk($arProp['OPTIONS'], function(&$v, $k){
                        $v = '<option value="'.$k.'">'.$v.'</option>';
                    });

                    printf('
                        <div class="order-form__group form-group form-group--%1$s enum">
                            <label for="order-%1$s">%2$s%3$s</label>
                            <select class="form-control" name="%4$s" id="%1$s">
                                %5$s
                            </select>
                            %6$s
                            %7$s
                        </div>
                        ',
                        strtolower($arProp['FORM_LABEL']),
                        $arProp['NAME'],
                        $arProp['IS_REQUIRED'] ? REQUIRED_SIGN : '',
                        $arProp['FORM_NAME'],
                        implode("\n", $arProp['OPTIONS']),
                        $arProp['FORM_DESC'] ? '<small class="form-text text-muted">' . $arProp['DESC'] . '</small>' : '',
                        implode('<br>', $arProp['ERRORS'])
                    );
                }
                else {
                    foreach ($arProp['OPTIONS'] as $code => $name):?>
                    <label class="enum-option">
                        <input type="radio" name="<?= $arProp['FORM_NAME'] ?>" value="<?= $code ?>">
                        <?= $name ?>
                    </label>
                    <?endforeach;
                }
                break;

            case 'DATE':
                $APPLICATION->IncludeComponent(
                    'bitrix:main.calendar',
                    '',
                    [
                        'SHOW_INPUT' => 'Y',
                        'FORM_NAME' => 'os-order-form',
                        'INPUT_NAME' => $arProp['FORM_NAME'],
                        'INPUT_VALUE' => $arProp['VALUE'],
                        'SHOW_TIME' => 'Y',
                        //'HIDE_TIMEBAR' => 'Y',
                        'INPUT_ADDITIONAL_ATTR' => 'placeholder="выберите дату"'
                    ]
                );
                break;

            case 'Y/N':
                ?>
                <input id="<?= $arProp['FORM_LABEL'] ?>" type="checkbox"
                       name="<?= $arProp['FORM_NAME'] ?>"
                       value="Y">
                <?
                break;

            default:
                printf('
                    <div class="order-form__group form-group form-group--%1$s">
                        <label for="order-%1$s">%2$s%3$s</label>
                        <input %4$s>
                        %5$s
                        %6$s
                    </div>
                    ',
                    strtolower($arProp['FORM_LABEL']),
                    $arProp['NAME'],
                    $arProp['IS_REQUIRED'] ? REQUIRED_SIGN : '',
                    getInputAttributes($arProp),
                    $arProp['FORM_DESC'] ? '<small class="form-text text-muted">' . $arProp['DESC'] . '</small>' : '',
                    implode('<br>', $arProp['ERRORS'])
                );
                break;
        }
    }

    ?>

    </div>
    <div class="col-md-9">
        <table class="table mb-5">
            <tr>
                <th>Наименование</th>
                <th>Стоимость</th>
                <th>Кол-во</th>
                <!-- <th>Стоимость с учетом скидки</th> -->
                <th>Итого</th>
            </tr>
            <? foreach ($arResult['BASKET'] as $arBasketItem): ?>
                <tr>
                    <td>
                        <?= $arBasketItem['NAME'] ?>
                        <? if (!empty($arBasketItem['PROPERTIES'])): ?>
                            <div class="basket-properties">
                                <? foreach ($arBasketItem['PROPERTIES'] as $arProp): ?>
                                    <?= $arProp['NAME'] ?>
                                    <?= $arProp['VALUE'] ?>
                                    <br>
                                <? endforeach; ?>
                            </div>
                        <? endif; ?>
                    </td>
                    <td><?= $arBasketItem['BASE_PRICE_DISPLAY'] ?></td>
                    <td><?= $arBasketItem['QUANTITY_DISPLAY'] ?></td>
                    <!-- <td><?= $arBasketItem['PRICE_DISPLAY'] ?></td> -->
                    <td><?= $arBasketItem['SUM_DISPLAY'] ?></td>
                </tr>
            <? endforeach; ?>
        </table>

        <div class="row">
            <div class="col-6">
                <?php /* ?>
                <h4>Оплата</h4>
                <? foreach ($arResult['PAY_SYSTEM_LIST'] as $arPaySystem): ?>
                    <label>
                        <input type="radio" name="pay_system_id"
                               value="<?= $arPaySystem['ID'] ?>"
                            <?= $arPaySystem['CHECKED'] ? 'checked' : '' ?>
                        >
                        <?= $arPaySystem['NAME'] ?>
                    </label>
                    <br>
                <? endforeach; ?>
                <?php */ ?>
                <h4>Доставка</h4>
                <? foreach ($arResult['DELIVERY_LIST'] as $arDelivery):?>
                    <label>
                        <input type="radio" name="delivery_id"
                        value="<?= $arDelivery['ID'] ?>"
                        <?= $arDelivery['CHECKED'] ? 'checked' : '' ?>
                        >
                        <?= $arDelivery['NAME'] ?>,
                        <?= $arDelivery['PRICE_DISPLAY'] ?>
                    </label>
                    <br>
                <? endforeach; ?>
            </div>
            <div class="col-6">
                <table class="table summary">
                    <tr>
                        <td>Общая стоимость</td>
                        <td data-products-base-price="<?= $arResult['PRODUCTS_BASE_PRICE'] ?>">
                            <?= $arResult['PRODUCTS_BASE_PRICE_DISPLAY'] ?>
                        </td>
                    </tr>
                    <?php if( $arResult['PRODUCTS_BASE_PRICE_DISPLAY'] !== $arResult['PRODUCTS_PRICE_DISPLAY'] ): ?>
                    <tr>
                        <td>Стоимость со скидкой</td>
                        <td data-products-price="<?= $arResult['PRODUCTS_PRICE'] ?>">
                            <?= $arResult['PRODUCTS_PRICE_DISPLAY'] ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>Стоимость доставки</td>
                        <td data-delivery-price="<?= $arResult['DELIVERY_PRICE'] ?>">
                            <?= $arResult['DELIVERY_PRICE_DISPLAY'] ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Итого</th>
                        <th data-sum="<?= $arResult['SUM'] ?>">
                            <?= $arResult['SUM_DISPLAY'] ?>
                        </th>
                    </tr>
                </table>

                <input type="hidden" name="save" value="y">
                <button type="submit" class="btn btn-primary"><?= Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_MAKE_ORDER_BUTTON')?></button>

                <!-- <table class="table">
                    <tr>
                        <td></td>
                        <td></td>
                        <td><?= $arResult['PRODUCTS_DISCOUNT_DISPLAY'] ?></td>
                    </tr>

                    <tr>
                        <td><?= $arResult['DELIVERY_BASE_PRICE_DISPLAY'] ?></td>
                        <td><?= $arResult['DELIVERY_PRICE_DISPLAY'] ?></td>
                        <td></td>
                    </tr>

                    <tr>
                        <td><?= $arResult['SUM_BASE_DISPLAY'] ?></td>
                        <td><?= $arResult['DISCOUNT_VALUE_DISPLAY'] ?></td>
                        <td></td>
                    </tr>
                </table> -->
            </div>
        </div>
    </div>
</div>
</form>
