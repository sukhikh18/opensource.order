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
<form action="<?= $APPLICATION->GetCurPage() ?>"
    method="post"
    name="os-order-form"
    id="os-order-form"
    class="order-form"
    enctype="multipart/form-data">

    <input type="hidden" name="person_type_id" value="<?=$arParams['PERSON_TYPE_ID']?>">
    <input type="hidden" name="delivery_id" value="<?= $arParams['DEFAULT_DELIVERY_ID'] ?>">
    <input type="hidden" name="pay_system_id" value="<?= $arParams['DEFAULT_PAY_SYSTEM_ID'] ?>">
    <input type="hidden" name="productId" value="<?= $arParams['DEFAULT_PRODUCT_ID'] ?>">

    <h2><?= Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_PROPERTIES_TITLE')?>:</h2>

    <?php

    foreach ($arResult['PROPERTIES'] as $propCode => $arProp) {
        array_walk($arProp['ERRORS'], function(&$error, $k) {
            $error = '<span class="error">' . $error->getMessage() . '</span>';
        });

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
                foreach ($arProp['OPTIONS'] as $code => $name):?>
                    <label class="enum-option">
                        <input type="radio" name="<?= $arProp['FORM_NAME'] ?>" value="<?= $code ?>">
                        <?= $name ?>
                    </label>
                <?endforeach;
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

    <input type="hidden" name="save" value="y">
    <button type="submit" class="btn btn-primary"><?= Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_MAKE_ORDER_BUTTON')?></button>

</form>
