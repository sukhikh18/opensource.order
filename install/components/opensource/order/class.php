<?php

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PropertyValue;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentCollection;
use Bitrix\Sale\ShipmentItem;
use Bitrix\Sale\ShipmentItemCollection;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\PaySystem;
use OpenSource\Order\ErrorCollection;
use OpenSource\Order\OrderHelper;
use OpenSource\Order\User;

class OpenSourceOrderComponent extends CBitrixComponent
{
    /**
     * @var Order
     */
    public $order;

    /**
     * @var User;
     */
    public $user;

    /**
     * @var ErrorCollection
     */
    public $errorCollection;

    /**
     * CustomOrder constructor.
     * @param CBitrixComponent|null $component
     * @throws Bitrix\Main\LoaderException
     */
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        Loader::includeModule('opensource.order');

        $this->errorCollection = new ErrorCollection();
    }

    public function onIncludeComponentLang()
    {
        Loc::loadLanguageFile(__FILE__);
    }

    public function onPrepareComponentParams($arParams = []): array
    {
        if($personType = $this->request->get('person_type_id')) {
            $arParams['PERSON_TYPE_ID'] = (int)$personType;
        } elseif (isset($arParams['PERSON_TYPE_ID']) && (int)$arParams['PERSON_TYPE_ID'] > 0) {
            $arParams['PERSON_TYPE_ID'] = (int)$arParams['PERSON_TYPE_ID'];
        } elseif ($arPersonType = User::getFirstPersonType()) {
            $arParams['PERSON_TYPE_ID'] = (int)$arPersonType['ID'];
        }

        if (isset($arParams['SAVE'])) {
            $arParams['SAVE'] = $arParams['SAVE'] === 'Y';
        } elseif (isset($this->request['save'])) {
            $arParams['SAVE'] = $this->request['save'] === 'y';
        } else {
            $arParams['SAVE'] = false;
        }

        return $arParams;
    }

    /**

    /**
     * @param int $personTypeId
     * @return Order
     * @throws Exception
     */
    public function createVirtualOrder(int $personTypeId)
    {
        global $USER;

        if (!isset(User::getPersonTypes()[$personTypeId])) {
            throw new RuntimeException(Loc::getMessage('OPEN_SOURCE_ORDER_UNKNOWN_PERSON_TYPE'));
        }

        if(!$USER->IsAuthorized() && 'Y' !== $this->arParams['ALLOW_UNAUTH_ORDER']) {
            throw new RuntimeException(Loc::getMessage('OPEN_SOURCE_ORDER_USER_NOT_AUTH'));
        }

        $siteId = Context::getCurrent()
            ->getSite();

        $basketItems = Basket::loadItemsForFUser(Fuser::getId(), $siteId)
            ->getOrderableItems();

        if (count($basketItems) === 0) {
            throw new LengthException(Loc::getMessage('OPEN_SOURCE_ORDER_EMPTY_BASKET'));
        }

        $this->order = Order::create($siteId, $userID ?: CSaleUser::GetAnonymousUserID());
        $this->order->setPersonTypeId($personTypeId);
        $this->order->setBasket($basketItems);

        return $this->order;
    }

    public function getPropertiesFromRequest()
    {
        $properties = $this->request['properties'] ?? [];
        $arFileProperties = $this->request->getFileList()->get('properties');

        if (is_array($arFileProperties)) {
            foreach ($arFileProperties  as $fileKey => $arFileField) {
                foreach ($arFileField as $fieldCode => $arFileFieldValue) {
                    if( ! isset($properties[$fieldCode])) {
                        $properties[$fieldCode] = array("ID" => '');
                    }
                    // @todo Multiple property
                    $properties[$fieldCode][$fileKey] = current($arFileFieldValue);
                }
            }
        }

        return $properties;
    }

    /**
     * @param array $propertyValues
     * @throws Exception
     */
    public function setOrderProperties(array $propertyValues)
    {
        foreach ($this->order->getPropertyCollection() as $prop) {
            /**
             * @var PropertyValue $prop
             */
            if ($prop->isUtil()) {
                continue;
            }

            $value = $propertyValues[$prop->getField('CODE')] ?? null;

            if (empty($value)) {
                $value = $prop->getProperty()['DEFAULT_VALUE'];
            }

            if (!empty($value)) {
                $prop->setValue($value);
            }
        }
    }

    /**
     * @param int $deliveryId
     * @return Shipment
     * @throws Exception
     */
    public function createOrderShipment(int $deliveryId = 0)
    {
        /* @var $shipmentCollection ShipmentCollection */
        $shipmentCollection = $this->order->getShipmentCollection();

        if ($deliveryId > 0) {
            $shipment = $shipmentCollection->createItem(
                Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId)
            );
        } else {
            $shipment = $shipmentCollection->createItem();
        }

        /** @var $shipmentItemCollection ShipmentItemCollection */
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipment->setField('CURRENCY', $this->order->getCurrency());

        foreach ($this->order->getBasket()->getOrderableItems() as $basketItem) {
            /**
             * @var $basketItem BasketItem
             * @var $shipmentItem ShipmentItem
             */
            $shipmentItem = $shipmentItemCollection->createItem($basketItem);
            $shipmentItem->setQuantity($basketItem->getQuantity());
        }

        return $shipment;
    }

    /**
     * @param int $paySystemId
     * @return Payment
     * @throws Exception
     */
    public function createOrderPayment(int $paySystemId)
    {
        $paymentCollection = $this->order->getPaymentCollection();
        $payment = $paymentCollection->createItem(
            Bitrix\Sale\PaySystem\Manager::getObjectById($paySystemId)
        );
        $payment->setField('SUM', $this->order->getPrice());
        $payment->setField('CURRENCY', $this->order->getCurrency());

        return $payment;
    }

    /**
     * @return Result
     *
     * @throws Exception
     */
    public function validateProperties()
    {
        $result = new Result();

        foreach ($this->order->getPropertyCollection() as $prop) {
            /**
             * @var PropertyValue $prop
             */
            if ($prop->isUtil()) {
                continue;
            }

            $r = $prop->checkRequiredValue($prop->getField('CODE'), $prop->getValue());
            if ($r->isSuccess()) {
                $r = $prop->checkValue($prop->getField('CODE'), $prop->getValue());
                if (!$r->isSuccess()) {
                    $result->addErrors($r->getErrors());
                }
            } else {
                $result->addErrors($r->getErrors());
            }
        }

        return $result;
    }

    /**
     * @return Result
     * @throws Exception
     */
    public function validateDelivery()
    {
        $result = new Result();

        $shipment = OrderHelper::getFirstNonSystemShipment($this->order);

        if ($shipment !== null) {
            if ($shipment->getDelivery() instanceof Delivery\Services\Base) {
                $obDelivery = $shipment->getDelivery();
                $availableDeliveries = Delivery\Services\Manager::getRestrictedObjectsList($shipment);
                if (!isset($availableDeliveries[$obDelivery->getId()])) {
                    $result->addError(new Error(
                        Loc::getMessage(
                            'OPEN_SOURCE_ORDER_DELIVERY_UNAVAILABLE',
                            [
                                '#DELIVERY_NAME#' => $obDelivery->getNameWithParent()
                            ]
                        ),
                        'delivery',
                        [
                            'type' => 'unavailable'
                        ]
                    ));
                }
            } else {
                $result->addError(new Error(
                    Loc::getMessage('OPEN_SOURCE_ORDER_NO_DELIVERY_SELECTED'),
                    'delivery',
                    [
                        'type' => 'undefined'
                    ]
                ));
            }
        } else {
            $result->addError(new Error(
                Loc::getMessage('OPEN_SOURCE_ORDER_SHIPMENT_NOT_FOUND'),
                'delivery',
                [
                    'type' => 'undefined'
                ]
            ));
        }

        return $result;
    }

    /**
     * @return Result
     * @throws Exception
     */
    public function validatePayment()
    {
        $result = new Result();

        if (!$this->order->getPaymentCollection()->isEmpty()) {
            $payment = $this->order->getPaymentCollection()->current();
            /**
             * @var Payment $payment
             */
            $obPaySystem = $payment->getPaySystem();
            if ($obPaySystem instanceof PaySystem\Service) {
                $availablePaySystems = PaySystem\Manager::getListWithRestrictions($payment);
                if (!isset($availablePaySystems[$payment->getPaymentSystemId()])) {
                    $result->addError(new Error(
                        Loc::getMessage(
                            'OPEN_SOURCE_ORDER_PAYMENT_UNAVAILABLE',
                            [
                                '#PAYMENT_NAME#' => $payment->getPaymentSystemName()
                            ]
                        ),
                        'payment',
                        [
                            'type' => 'unavailable'
                        ]
                    ));
                }
            } else {
                $result->addError(new Error(
                    Loc::getMessage('OPEN_SOURCE_ORDER_NO_PAY_SYSTEM_SELECTED'),
                    'payment',
                    [
                        'type' => 'undefined'
                    ]
                ));
            }
        } else {
            $result->addError(new Error(
                Loc::getMessage('OPEN_SOURCE_ORDER_NO_PAY_SYSTEM_SELECTED'),
                'payment',
                [
                    'type' => 'undefined'
                ]
            ));
        }

        return $result;
    }

    /**
     * @return Result
     * @throws Exception
     */
    public function validateOrder()
    {
        $result = new Result();

        $propValidationResult = $this->validateProperties();
        if (!$propValidationResult->isSuccess()) {
            $result->addErrors($propValidationResult->getErrors());
        }

        $deliveryValidationResult = $this->validateDelivery();
        if (!$deliveryValidationResult->isSuccess()) {
            $result->addErrors($deliveryValidationResult->getErrors());
        }

        $paymentValidationResult = $this->validatePayment();
        if (!$paymentValidationResult->isSuccess()) {
            $result->addErrors($paymentValidationResult->getErrors());
        }

        return $result;
    }

    public function executeComponent()
    {
        try {
            $this->createVirtualOrder($this->arParams['PERSON_TYPE_ID']);

            $deliveryId = $this->request['delivery_id'] ?? $this->arParams['DEFAULT_DELIVERY_ID'] ?? 0;
            $this->createOrderShipment($deliveryId);

            $paySystemId = $this->request['pay_system_id'] ?? $this->arParams['DEFAULT_PAY_SYSTEM_ID'] ?? 0;
            if ($paySystemId > 0) {
                $this->createOrderPayment($paySystemId);
            }

            $propertiesList = $this->getPropertiesFromRequest() ?: $this->arParams['DEFAULT_PROPERTIES'] ?? [];
            if (!empty($propertiesList)) {
                $this->setOrderProperties($propertiesList);
            }

            if ($this->arParams['SAVE']) {
                $validationResult = $this->validateOrder();

                if ($validationResult->isSuccess()) {
                    $this->user = new User($this->arParams);
                    $this->user->setByPropertyCollection($this->order->getPropertyCollection());
                    $userSaveResult = $this->user->save();
                    if ($userSaveResult->isSuccess()) {
                        $this->order->setFieldNoDemand('USER_ID', $this->user->getId());
                        $saveResult = $this->order->save();
                        if (!$saveResult->isSuccess()) {
                            $this->errorCollection->add($saveResult->getErrors());
                        }
                    } else {
                        $this->errorCollection->add($userSaveResult->getErrors());
                    }
                } else {
                    $this->errorCollection->add($validationResult->getErrors());
                }
            }
        } catch (Exception $exception) {
            $this->errorCollection->setError(new Error($exception->getMessage()));
        }

        $this->includeComponentTemplate();
    }

}
