<?php

use Bitrix\Main\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::includeModule('sale');

$arPersonTypesList = [];
$rsPersonTypes = \CSalePersonType::GetList(['SORT' => 'ASC']);
while ($arPersonType = $rsPersonTypes->Fetch()) {
    $arPersonTypesList[$arPersonType['ID']] = '[' . $arPersonType['ID'] . '] ' . $arPersonType['NAME'];
}


$arDeliveriesList = [
    0 => Loc::getMessage('OPEN_SOURCE_DEFAULT_VALUE_EMPTY')
];
$arActiveDeliveries = Bitrix\Sale\Delivery\Services\Manager::getActiveList();
foreach ($arActiveDeliveries as $arDelivery) {
    $arDeliveriesList[$arDelivery['ID']] = '[' . $arDelivery['ID'] . '] ' . $arDelivery['NAME'];
}


$arPaySystemsList = [
    0 => Loc::getMessage('OPEN_SOURCE_DEFAULT_VALUE_EMPTY')
];
$rsPaySystems = Bitrix\Sale\PaySystem\Manager::getList();
while ($arPaySystem = $rsPaySystems->fetch()) {
    $arPaySystemsList[$arPaySystem['ID']] = '[' . $arPaySystem['ID'] . '] ' . $arPaySystem['NAME'];
}

$arGroups = [];
array_walk(GroupTable::getList(['select' => ['ID', 'NAME']])->fetchAll(), function($item, $i) use (&$arGroups) {
    $arGroups[$item['ID']] = $item['NAME'];
});

$arComponentParameters = [
    'GROUPS' => [
    ],
    'PARAMETERS' => [
        'DEFAULT_PERSON_TYPE_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_DEFAULT_PERSON_TYPE_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arPersonTypesList
        ],
        'DEFAULT_DELIVERY_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_DEFAULT_DELIVERY_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arDeliveriesList
        ],
        'DEFAULT_PAY_SYSTEM_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_DEFAULT_PAY_SYSTEM_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arPaySystemsList
        ],
        'PATH_TO_BASKET' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_PATH_TO_BASKET'),
            'TYPE' => 'STRING',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ],
        'ALLOW_UNAUTH_ORDER' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_ALLOW_UNAUTH_ORDER'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ],
        'REGISTER_NEW_USER' => [
            'NAME' => 'Регистрировать нового пользователя',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ],
        'NEW_USER_ACTIVATE' => [
            'NAME' => 'Активировать нового пользователя',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ],
        'GROUP_ID' => [
            'NAME' => 'Группа пользователя',
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'DEFAULT' => '5',
            'PARENT' => 'ADDITIONAL_SETTINGS',
            'VALUES' => $arGroups
        ],
        'UPDATE_USER_PROPERTIES' => [
            'NAME' => 'Обновлять профиль пользователя',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ],
    ]
];