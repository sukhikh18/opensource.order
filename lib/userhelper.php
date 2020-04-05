<?php

namespace OpenSource\Order;

use Bitrix\Main\Event;
use CSalePersonType;
use CUser;
use Exception;
use RuntimeException;

class UserHelper
{
    /** @var static The stored singleton instance */
    protected static $instance;

    protected $personTypes = [];

    /**
     * Creates the original or retrieves the stored singleton instance
     * @return static
     */
    public static function getInstance()
    {
        if ( ! static::$instance) {
            static::$instance = (new \ReflectionClass(get_called_class()))
                ->newInstanceWithoutConstructor();
        }

        return static::$instance;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString(int $length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @return array
     */
    public function getPersonTypes(): array
    {
        if (empty($this->personTypes)) {
            $personType = new CSalePersonType();
            $rsPersonTypes = $personType->GetList(['SORT' => 'ASC']);
            while ($arPersonType = $rsPersonTypes->Fetch()) {
                $arPersonType['ID'] = (int)$arPersonType['ID'];
                $this->personTypes[$arPersonType['ID']] = $arPersonType;
            }
        }

        return $this->personTypes;
    }

    public static function getUserProperties($propertyCollection): array
    {
        $newUserPassword = UserHelper::generateRandomString();

        return [
            "LID" => 'ru',
            "PASSWORD" => $newUserPassword,
            "CONFIRM_PASSWORD" => $newUserPassword,
            "NAME" => $propertyCollection->getPayerName()->getValue(),
            "EMAIL" => $propertyCollection->getUserEmail()->getValue(),
            "PERSONAL_PHONE" => $propertyCollection->getPhone()->getValue(),
        ];
    }

    public static function updateUserAccount(int $userID = 0, array $arUserFields = [])
    {
        $obUser = new CUser;

        if($userID) {
            $event = new Event("opensource.order", "OnBeforeUserUpdate", $arUserFields);
            $event->send();

            if ($event->getResults()) {
                /** @var \Bitrix\Main\EventResult $eventResult */
                $eventResult = $event->getResults()[0];
                $arUserFields = $eventResult->getParameters();
            }

            if( ! empty($arUserFields)) {
                $obUser->Update($userID, $arUserFields);
            }
        } else {
            if(empty($arUserFields['LOGIN'])) {
                list($arUserFields['LOGIN']) = explode('@', $arUserFields['EMAIL'] ?? static::generateRandomString() );
            }

            $event = new Event("opensource.order", "OnBeforeUserRegister", $arUserFields);
            $event->send();

            if ($event->getResults()) {
                /** @var \Bitrix\Main\EventResult $eventResult */
                $eventResult = $event->getResults()[0];
                $arUserFields = $eventResult->getParameters();
            }

            $userID = $obUser->Add($arUserFields);
        }

        if(intval($userID) <= 0) {
            throw new RuntimeException($obUser->LAST_ERROR);
        }
    }

}