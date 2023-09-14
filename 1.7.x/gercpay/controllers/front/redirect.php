<?php

require_once __DIR__ . '../../../gercpay.php';
require_once __DIR__ . '../../../gercpay.cls.php';

/**
 * @class GercpayRedirectModuleFrontController
 *
 * Generates a payment form and redirects to the payment system page.
 */
class GercpayRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @throws PrestaShopException
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $cart = $this->context->cart;

        $currency    = new CurrencyCore($cart->id_currency);
        $payCurrency = $currency->iso_code;
        $gercpay  = new Gercpay();
        $cpcCls      = new GercPayCls();
        $total       = number_format($cart->getOrderTotal(), 2, '.', '');

        $gercpay->validateOrder((int) $cart->id, _PS_OS_PREPARATION_, $total, $gercpay->displayName);
        $order   = new OrderCore((int) $gercpay->currentOrder);
        $address = new Address((int) $order->id_address_delivery);

        $description = $this->l('Payment by card on the site') . ' ' . htmlspecialchars($_SERVER['HTTP_HOST']) . ', ' .
            $address->firstname . ' ' . $address->lastname . ', ' . ($address->phone ?? '') . '.';

        $customer = new Customer((int) $order->id_customer);

        $option = [];

        $option['operation']    = 'Purchase';
        $option['merchant_id']  = $gercpay->getOption('merchant');
        $option['order_id']     = $gercpay->currentOrder;
        $option['amount']       = $total;
        $option['currency_iso'] = $payCurrency;
        $option['description']  = $description;
        $option['add_params']   = [];
        $option['signature']    = $cpcCls->getRequestSignature($option);
        $option['language']     = $gercpay->getOption('language') ?? 'en';

        $url = GercPayCls::URL;
        $option['approve_url'] = $this->context->link->getModuleLink(
            'gercpay',
            'result?&orderReference=' . urlencode($gercpay->currentOrder) .
            '&sessionId=' . urlencode($order->secure_key) .
            '&cId=' . $order->id_customer
        );
        $option['decline_url']  = $this->context->link->getModuleLink('gercpay', 'result');
        $option['cancel_url']   = $this->context->link->getModuleLink('gercpay', 'result');
        $option['callback_url'] = $this->context->link->getModuleLink('gercpay', 'callback');
        // Statistics.
        $option['client_first_name'] = $customer->firstname;
        $option['client_last_name']  = $customer->lastname;
        $option['email']             = $customer->email;
        $option['phone']             = $address->phone;

        $this->context->smarty->assign(['fields' => $option, 'url' => $url]);
        $this->setTemplate('module:gercpay/views/templates/front/redirect.tpl');
    }
}
