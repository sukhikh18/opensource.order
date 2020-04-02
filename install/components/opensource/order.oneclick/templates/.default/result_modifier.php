<?php
/**
 * MAKING $arResult FROM SCRATCHES
 *
 * @var OpenSourceOrderComponent $component
 */

use Bitrix\Sale\BasketItem;
use Bitrix\Sale\BasketPropertyItem;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Order;
use Bitrix\Sale\PropertyValue;
use OpenSource\Order\LocationHelper;
use OpenSource\Order\OrderHelper;

$component = &$this->__component;
$order = $component->order;

if (!$order instanceof Order) {
    return;
}

/**
 * ORDER FIELDS
 */
$arResult = $order->getFieldValues();

/**
 * ORDER PROPERTIES
 */
$arResult['PROPERTIES'] = [];
foreach ($order->getPropertyCollection() as $prop) {
    /**
     * @var PropertyValue $prop
     */
    if ($prop->isUtil()) {
        continue;
    }

    $arProp['FORM_NAME'] = 'properties[' . $prop->getField('CODE') . ']';
    $arProp['FORM_LABEL'] = 'property_' . $prop->getField('CODE');

    $arProp['TYPE'] = $prop->getType();
    $arProp['NAME'] = $prop->getName();
    $arProp['VALUE'] = $prop->getValue();
    $arProp['IS_REQUIRED'] = $prop->isRequired();
    $arProp['ERRORS'] = $component->errorCollection->getAllErrorsByCode('PROPERTIES[' . $prop->getField('CODE') . ']');

    switch ($prop->getType()) {
        case 'LOCATION':
            if (!empty($arProp['VALUE'])) {
                $arProp['LOCATION_DATA'] = LocationHelper::getDisplayByCode($arProp['VALUE']);
            }
            break;

        case 'ENUM':
            $obProperty = $prop->getPropertyObject();
            $arProp['MULTIELEMENT'] = $obProperty->getField('MULTIELEMENT');
            $arProp['OPTIONS'] = $obProperty->getOptions();
            break;
    }

    $arResult['PROPERTIES'][$prop->getField('CODE')] = $arProp;
}
