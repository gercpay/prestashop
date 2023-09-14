<?php

require_once __DIR__ . '../../../gercpay.php';
require_once __DIR__ . '../../../gercpay.cls.php';

/**
 * @class GercpayCallbackModuleFrontController
 *
 * Processes the response of the payment system server.
 */
class GercpayCallbackModuleFrontController extends ModuleFrontController
{
    public $display_column_left  = false;
    public $display_column_right = false;

    public $display_header = false;
    public $display_footer = false;

    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        try {
            $data     = json_decode(Tools::file_get_contents('php://input'), true);
            $order_id = !empty($data['orderReference']) ? $data['orderReference'] : null;
            $order    = new OrderCore((int)$order_id);

            if (Validate::isLoadedObject($order) !== true) {
                die('Error: Order not found!');
            }

            if (!isset($data['type'])
                || !in_array($data['type'], [GercPayCls::RESPONSE_TYPE_PAYMENT, GercPayCls::RESPONSE_TYPE_REVERSE], true)) {
                die('Error: Unknown operation type!');
            }

            if (!isset($data['transactionStatus'])
                || !in_array($data['transactionStatus'], [GercPayCls::ORDER_APPROVED, GercPayCls::ORDER_DECLINED], true)) {
                die('Error: Unknown transaction status!');
            }

            $gercPayCls  = new GercPayCls();
            $isPaymentValid = $gercPayCls->isPaymentValid($data);

            if ($isPaymentValid !== true) {
                exit('Error: ' . $isPaymentValid);
            }

            $history = new OrderHistory();
            $history->id_order = $order_id;
            if ($data['transactionStatus'] === GercPayCls::ORDER_APPROVED) {
                if ($data['type'] === GercPayCls::RESPONSE_TYPE_PAYMENT) {
                    // Ordinary payment.
                    $history->changeIdOrderState((int)Configuration::get('GERCPAY_APPROVE_ORDER_STATUS'), $order_id);
                } elseif ($data['type'] === GercPayCls::RESPONSE_TYPE_REVERSE) {
                    // Refunded payment.
                    $history->changeIdOrderState((int)Configuration::get('GERCPAY_REFUNDED_ORDER_STATUS'), $order_id);
                }
            } else {
                // Declined payment.
                $history->changeIdOrderState((int)Configuration::get('GERCPAY_DECLINE_ORDER_STATUS'), $order_id);
            }
            $history->addWithemail(true, [
                'order_name' => $order_id,
            ]);

            exit('OK');
        } catch (Exception $e) {
            exit(get_class($e) . ': ' . $e->getMessage());
        }
    }
}
