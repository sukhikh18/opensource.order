<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Request;
use Bitrix\Sale\Location\Search\Finder;
use Bitrix\Sale\Location\TypeTable;
use OpenSource\Order\LocationHelper;
use Bitrix\Sale\Delivery;
use OpenSource\Order\OrderHelper;

class OpenSourceOrderOneClickAjaxController extends Controller
{
    /** @var ErrorCollection redefine public for use on component saveOrder method */
    public $errorCollection;

    /**
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'saveOrderOneClick' => [
                'prefilters' => []
            ],
        ];
    }

    public function saveOrderOneClickAction(
        int $person_type_id,
        int $productId,
        int $delivery_id,
        int $pay_system_id
    ): array {
        CBitrixComponent::includeComponentClass('opensource:order.oneclick');
        $siteId = Context::getCurrent()->getSite();

        $componentClass = new OpenSourceOrderOneClickComponent();
        $basket = $componentClass->createVirtualEmptyBasket($siteId);
        $addResult = $componentClass->addProduct($siteId, $basket, $productId);
        if(!$addResult->isSuccess()) {
            $this->errorCollection->add($addResult->getErrors());
        }

        $properties = $componentClass->getPropertiesFromRequest() ?: $this->arParams['DEFAULT_PROPERTIES'];

        $componentClass->createVirtualEasyOrder($person_type_id, $basket);
        $componentClass->setOrderProperties($properties);
        $componentClass->createOrderShipment($delivery_id);
        $componentClass->createOrderPayment($pay_system_id);

        return $componentClass->saveOrder($this);
    }
}