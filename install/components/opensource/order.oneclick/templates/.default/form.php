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
?>
<form action="" method="post" name="os-order-form" id="os-order-form" enctype="multipart/form-data">

    <input type="hidden" name="person_type_id" value="<?=$arParams['PERSON_TYPE_ID']?>">
    <input type="hidden" name="delivery_id" value="<?= $arParams['DEFAULT_DELIVERY_ID'] ?>">
    <input type="hidden" name="pay_system_id" value="<?= $arParams['DEFAULT_PAY_SYSTEM_ID'] ?>">
    <input type="hidden" name="productId" value="<?= $arParams['DEFAULT_PRODUCT_ID'] ?>">

    <h2><?= Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_PROPERTIES_TITLE')?>:</h2>
    <table>
        <?php foreach ($arResult['PROPERTIES'] as $propCode => $arProp): ?>
            <tr>
                <td>
                    <label for="<?= $arProp['FORM_LABEL'] ?>">
                        <?= $arProp['NAME'] ?>
                        <?= ! $arProp['IS_REQUIRED'] ?: REQUIRED_SIGN; ?>
                    </label>
                    <? foreach ($arProp['ERRORS'] as $error):
                        /** @var Error $error */
                        ?>
                        <div class="error"><?= $error->getMessage() ?></div>
                    <? endforeach; ?>
                </td>
                <td>
                    <?php
                    switch ($arProp['TYPE']):
                        case 'LOCATION':
                            ?>
                            <div class="location">
                                <select class="location-search" name="<?= $arProp['FORM_NAME'] ?>"
                                        id="<?= $arProp['FORM_LABEL'] ?>">
                                    <option
                                            data-data='<?echo Json::encode($arProp['LOCATION_DATA'])?>'
                                            value="<?= $arProp['VALUE'] ?>"><?=$arProp['LOCATION_DATA']['label']?></option>
                                </select>
                            </div>
                            <?
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
                            ?>
                            <input id="<?= $arProp['FORM_LABEL'] ?>"
                                   type="<?= 'FILE' === $arProp['TYPE'] ? 'file' : 'text' ?>"
                                   name="<?= $arProp['FORM_NAME'] ?>"
                                   value="<?= $arProp['VALUE'] ?>">
                        <? endswitch; ?>
                </td>
            </tr>
        <? endforeach; ?>
    </table>

    <input type="hidden" name="save" value="y">
    <button type="submit"><?= Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_MAKE_ORDER_BUTTON')?></button>

</form>
