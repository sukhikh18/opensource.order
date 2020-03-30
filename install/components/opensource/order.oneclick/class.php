<?php

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;

CBitrixComponent::includeComponentClass("opensource:order");

class OpenSourceOrderOneClickComponent extends OpenSourceOrderComponent
{
    /**
     * @return array
     */
    public function getPropertiesFromRequest(): array
    {
        $properties = $this->request['properties'] ?? $this->arParams['DEFAULT_PROPERTIES'] ?? [];
        $arFileProperties = $this->request->getFileList()->get('properties');

        if (is_array($arFileProperties)) {
            foreach ($arFileProperties  as $fileKey => $arFileField) {
                foreach ($arFileField as $fieldCode => $arFileFieldValue) {
                    if( ! isset($properties[$fieldCode])) {
                        $properties[$fieldCode] = ["ID" => ''];
                    }

                    $properties[$fieldCode][$fileKey] = $arFileFieldValue;
                }
            }
        }

        return $properties;
    }

    /**
     * @param string $siteId
     * @return Basket
     */
    public function createVirtualEmptyBasket($siteId)
    {
        $basket = Basket::create($siteId);
        $basket->setFUserId(Fuser::getId());

        return $basket;
    }

    /**
     * @param string $siteId
     * @param Basket $basket
     * @param int $productId
     * @return Result
     *
     * @throws Exception
     */
    public function addProduct($siteId, $basket, int $productId)
    {
        return $basket
            ->createItem('catalog', $productId)
            ->setFields([
                'QUANTITY' => 1,
                'LID' => $siteId,
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
            ]);
    }

    /**
     * @param int $personTypeId
     * @return Order
     * @throws Exception
     */
    public function createVirtualEasyOrder(int $personTypeId, $basket)
    {
        global $USER;

        if (!isset($this->getPersonTypes()[$personTypeId])) {
            throw new RuntimeException(Loc::getMessage('OPEN_SOURCE_ORDER_UNKNOWN_PERSON_TYPE'));
        }

        $siteId = Context::getCurrent()->getSite();
        $basketItems = $basket->getOrderableItems();

        $this->order = Order::create($siteId, $USER->GetID());
        $this->order->setPersonTypeId($personTypeId);
        $this->order->setBasket($basketItems);

        return $this->order;
    }

    /**
     * @param int $personTypeId
     * @return Order
     * @throws Exception
     */
    public function createVirtualOrder(int $personTypeId)
    {
        global $USER;

        if (!isset($this->getPersonTypes()[$personTypeId])) {
            throw new RuntimeException(Loc::getMessage('OPEN_SOURCE_ORDER_UNKNOWN_PERSON_TYPE'));
        }

        $siteId = Context::getCurrent()
            ->getSite();

        $basketItems = Basket::loadItemsForFUser(Fuser::getId(), $siteId)
            ->getOrderableItems();

        $this->order = Order::create($siteId, $USER->GetID());
        $this->order->setPersonTypeId($personTypeId);
        $this->order->setBasket($basketItems);

        return $this->order;
    }

    /**
     * @param  OpenSourceOrderOneClickComponent|OpenSourceOrderOneClickAjaxController $collectionClass
     * @return array
     */
    public function saveOrder($collectionClass): array
    {
        $data = [];

        $validationResult = $this->validateOrder();

        if ($validationResult->isSuccess()) {
            $saveResult = $this->order->save();
            if ($saveResult->isSuccess()) {
                $data['order_id'] = $saveResult->getId();
            } else {
                $collectionClass->errorCollection->add($saveResult->getErrors());
            }
        } else {
            $collectionClass->errorCollection->add($validationResult->getErrors());
        }

        return $data;
    }

    public function executeComponent()
    {
        try {
            $this->createVirtualOrder($this->arParams['PERSON_TYPE_ID']);

            if ($this->arParams['SAVE']) {
                $siteId = Context::getCurrent()->getSite();

                $basket = $this->createVirtualEmptyBasket($siteId);
                $productId = $this->request['productId'] ?? $this->arParams['DEFAULT_PRODUCT_ID'] ?? 0;
                $addResult = $this->addProduct($siteId, $basket, $productId);
                if(!$addResult->isSuccess()) {
                    $this->errorCollection->add($addResult->getErrors());
                }

                $this->createVirtualEasyOrder($this->arParams['PERSON_TYPE_ID'], $basket);

                $propertiesList = $this->getPropertiesFromRequest();
                if (!empty($propertiesList)) {
                    $this->setOrderProperties($propertiesList);
                }

                $deliveryId = $this->request['delivery_id'] ?? $this->arParams['DEFAULT_DELIVERY_ID'] ?? 0;
                $this->createOrderShipment($deliveryId);

                $paySystemId = $this->request['pay_system_id'] ?? $this->arParams['DEFAULT_PAY_SYSTEM_ID'] ?? 0;
                if ($paySystemId > 0) {
                    $this->createOrderPayment($paySystemId);
                }

                $this->saveOrder($this);
            }
        } catch (Exception $exception) {
            $this->errorCollection->setError(new Error($exception->getMessage()));
        }

        $this->includeComponentTemplate();
    }
}