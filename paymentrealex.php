<?php
/**
 * @Copyright Copyright (C) 2012 www.profinvent.com. All rights reserved.
 * @website http://www.profinvent.com
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 **/
// No direct access
defined('_JEXEC') or die;

jimport( 'joomla.environment.request' );
jimport('joomla.plugin.plugin');
jimport('joomla.error.log');

class plgCoursemanPaymentRealex extends JPlugin {
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;


    public function onCoursemanRealexPayment($order, $order_items) {
        $lang = JFactory::getLanguage();
        $lang->load($order->sysname,JPATH_ADMINISTRATOR);
        $result = $this->params->get('message');
        $url = "https://epage.payandshop.com/epage.cgi";
        
        $result .= '<br /><div class="row-fluid">';
        $result .= '<div class="span5 ">';

        $result .= '<form method="POST" action="'.$url.'">
                    <input type="hidden" name="MERCHANT_ID" value="'.$this->params->get('merchant_id').'">
                    <input type="hidden" name="ORDER_ID" value="'.$order->id.'">
                    <input type="hidden" name="ACCOUNT" value="'.$this->params->get('account').'">';
        
        $totalWitoutVat = 0.0;
        $totalVatAmount = 0.0;
        for($i=0; $i < count($order_items); $i++){
            $totalWitoutVat += ($order_items[$i]->price_without_vat * $order_items[$i]->quantity);
            $totalVatAmount += ($order_items[$i]->vat_amount * $order_items[$i]->quantity);
        }

        $timestamp = date('Ymdhis');
        $totalAmount = $totalWitoutVat + $totalVatAmount;
        $sha1hash = $this->_createSha1Hash($timestamp, 
                                           $this->params->get('merchant_id'),
                                           $order->id,
                                           $totalAmount,
                                           $order->currency,
                                           $this->params->get('secret_hash'));
        $result .= '<input type="hidden" name="AMOUNT" value="'.$totalAmount.'">
                    <input type="hidden" name="CURRENCY" value="'.strtoupper($order->currency).'">
                    <input type="hidden" name="TIMESTAMP" value="'.$timestamp.'">
                    <input type="hidden" name="SHA1HASH" value="'.$sha1hash.'">
                    <input type="hidden" name="AUTO_SETTLE_FLAG" value="'.$this->params->get('auto_settle_flag').'">
                    <input type="submit" class="btn btn-success btn-large" value="'.JText::_('PLG_COURSEMAN_PAYMENT_BUTTON_PAY_NOW').'">
                    </form> ';

        $result .= '</div>';

        $result .= '<div class="span7" style="text-align:right;"><img class="realex_payment_logo" src="plugins/courseman/paymentrealex/assets/realex_payment_logo.png"></div>';
        $result .= '</div>';
        
        
    }

    public function onCoursemanRealexPaymentCompleted() {

        $jinput = JFactory::getApplication()->input;
        jimport('joomla.log.log');

        JLog::addLogger(
            array(
                //Sets file name
                'text_file' => 'plg_courseman_paymentrealex.php'
            ),
            //Sets all JLog messages to be set to the file
            JLog::ALL,
            //Chooses a category name
            'plg_courseman_paymentrealex'
        );

        if($this->params->get('enable_logging')){
            JLog::add('Payment to be completed requested by Realex. Status '.$_POST['payment_status'] . ' order '.$jinput->get('oid'), JLog::INFO, 'plg_courseman_paymentrealex');

            foreach ($_POST as $key => $value) {
                JLog::add($key .': '. $value, JLog::INFO, 'plg_courseman_paymentrealex');
            }
        }

        $hostname = gethostbyaddr ( $_SERVER ['REMOTE_ADDR'] );
        if (! preg_match ( '/payandshop\.com$/', $hostname )) {
            if($this->params->get('enable_logging')) JLog::add('Validation post isn\'t from Realex', JLog::ERROR, 'plg_courseman_paymentrealex');
            return false;
        }

        if($_POST['payment_status'] == 'Pending') {

            $result =  array('order_id' => $jinput->get('oid'), 'status' => 2, 'transaction_id' => $_POST['txn_id']);
        } else if($_POST['payment_status'] == 'Completed') {
            $result =  array('order_id' => $jinput->get('oid'), 'status' => 3, 'transaction_id' => $_POST['txn_id']);
            JLog::add('STATUS CHANGED TO '.$_POST['payment_status'].implode(",", $result), JLog::INFO, 'plg_courseman_paymentrealex');
        } else if($_POST['payment_status'] == 'Canceled_Reversal') {
            $result =  array('order_id' => $jinput->get('oid'), 'status' => 4, 'transaction_id' => $_POST['txn_id']);
        } else {

            JLog::add('UNKNOWN STATUS', JLog::INFO, 'plg_courseman_paymentrealex');
        }
        JLog::add('STATUS CHANGED TO '.$_POST['payment_status'].implode(",", $result), JLog::INFO, 'plg_courseman_paymentrealex');
        return $result;
        

    }

    /**
     * Calculates valudation hash from these values in proper order
     * TIMESTAMP.MERCHANT_ID.ORDER_ID.AMOUNT.CURRENCY
     * 
     * @param string $timestamp timestamp in yyyymmddhhmmss format
     * @param string $merchantId merchant ID
     * @param string $orderId ID of precessed order
     * @param string $amount sum price to pay
     * @param string $currency 3 digits currency code
     * @param string $secretHash realex generated secret hash
     * @return sha1 encoded validation hash
     */
    private function _createSha1Hash($timestamp, $merchantId, $orderId, $amount, $currency, $secretHash) 
    {
        $pieces = array($timestamp, $merchantId, $orderId, $amount, $currency);
        $secretHashData = strtolower(sha1(implode('.', $pieces)));
        $finalPreHash = $secretHashData.'.'.$secretHash;
        return sha1($finalPreHash);
    }
}
