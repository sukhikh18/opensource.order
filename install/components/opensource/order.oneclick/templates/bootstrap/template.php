<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Sale\Order;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var OpenSourceOrderComponent $component */
CJSCore::Init(['jquery']);

?>
<div class="os-order">
    <?php if (isset($arResult['ID']) && $arResult['ID'] > 0) {
        echo Loc::getMessage('OPEN_SOURCE_ORDER_TEMPLATE_ORDER_CREATED', [
            '#ORDER_ID#' => $arResult['ID']
        ]);
    } elseif ($component->order instanceof Order) {
        include 'form.php';
    } ?>
</div>

