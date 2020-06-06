<?php

namespace OpenSource\Order;

use CSalePersonType;
use RuntimeException;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\UserTable;
use Bitrix\Main\Security\Random;
use Bitrix\Sale\PropertyValueCollection;

class User
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var array
     */
    public $arParams;

    /**
     * @return array
     */
    public static function getPersonTypes(): array
    {
        $personTypeList = [];
        $rsPersonTypes = CSalePersonType::GetList();
        while ($arPersonType = $rsPersonTypes->Fetch()) {
            $personTypeList[(int)$arPersonType['ID']] = $arPersonType;
        }

        return $personTypeList;
    }

    /**
     * @return array|null
     */
    public static function getFirstPersonType()
    {
        return CSalePersonType::GetList()->Fetch();
    }

    public static function getUserProperties(PropertyValueCollection $propertyCollection)
    {
    }

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        global $USER;

        $this->arParams = $params;

        $this->data = [
            'LID' => Context::getCurrent()->getSite(),
            'ID' => intval($USER->GetID()),
        ];

        if ($this->getId() <= 0) {
            $newUserPassword = randString(2);

            $this->data += [
                // 'GROUP_ID' => $this->arParams['GROUP_ID'],
                'ACTIVE' => 'Y' === $this->arParams['NEW_USER_ACTIVATE'] ? 'Y' : 'N',
                "PASSWORD" => $newUserPassword,
                "CONFIRM_PASSWORD" => $newUserPassword,
                "EXTERNAL_AUTH_ID" => 'opensource.order',
            ];
        }
    }

    public function getId()
    {
        return isset($this->data['ID']) ? intval($this->data['ID']) : 0;
    }

    public function getEmail()
    {
        return $this->data['EMAIL'] ?? false;
    }

    public function getPhone()
    {
        return $this->data['PERSONAL_PHONE'] ?? false;
    }

    public function setByPropertyCollection(PropertyValueCollection $propertyCollection)
    {
        $data = [
            "NAME" => $propertyCollection->getPayerName(),
            "EMAIL" => $propertyCollection->getUserEmail(),
            "PERSONAL_PHONE" => $propertyCollection->getPhone(),
            "PERSONAL_ADDRESS" => $propertyCollection->getAddress(),
        ];

        /**
         * @param PropertyValue $property
         */
        $getPropertyValue = function($property) {
            return method_exists($property, 'getValue') ? $property->getValue() : '';
        };

        $newProperties = array_map($getPropertyValue, $data);
        $this->data += $newProperties;
    }

    public function save()
    {
        $result = new Result();
        $obUser = new \CUser;

        // Is not authorized
        if ($this->getId() <= 0) {
            if ('Y' == $this->arParams['REGISTER_NEW_USER']) {
                if (!$this->getEmail() && !empty($this->data['PERSONAL_PHONE'])) {
                    $this->data['EMAIL'] = $this->data['PERSONAL_PHONE'] . '@yandex.ru';
                }

                list($this->data['LOGIN']) = explode('@', $this->data['EMAIL'], 2);

                // Check unique.
                $userRowsList = UserTable::getList(array(
                    'filter' => array('=LOGIN' => $this->data['LOGIN']),
                    'limit' => 1,
                ));

                // Add random string when login exists.
                if($userRowsList->getSelectedRowsCount() > 0) {
                    $this->data['LOGIN'] .= '_' . Random::getString(4);
                }

                $this->data['ID'] = $obUser->Add($this->data);

                if ($this->getId() <= 0) {
                    $result->addError(new Error($obUser->LAST_ERROR, 'USER_ADD_FAIL'));
                }
            } else {
                // try create/get anonymous user
                $this->data['ID'] = CSaleUser::GetAnonymousUserID();
            }
        }
        elseif ('Y' == $this->arParams['UPDATE_USER_PROPERTIES']) {
            $obUser->Update($this->getId(), $this->data);
        }

        return $result;
    }
}
