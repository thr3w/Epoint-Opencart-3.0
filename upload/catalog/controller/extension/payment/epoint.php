<?php
class ControllerExtensionPaymentEpoint extends Controller {

	public function index() {
		require_once(DIR_APPLICATION.'model/extension/payment/epoint/epoint.class.php');

		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		if(!isset($this->session->data['order_id'])) {
			return false;
		}

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$out_trade_no = str_pad($order_info['order_id'], 7, "0",STR_PAD_LEFT); // Length must be greater than 7
		$subject = trim($this->config->get('config_name'));
		$currency = $this->config->get('payment_epoint_currency');
		$total_fee = trim($this->currency->format($order_info['total'], $currency, '', false));
		$body = trim($this->config->get('config_name'));
		$values = 
		array(
			"public_key" => $this->config->get('payment_epoint_app_id'),
			"amount" => $order_info['total'],
			"currency" => 'AZN',
			"language" => "az", 
			"order_id" => $order_info['order_id'],
			"description" => "Order ID: ".$out_trade_no,
			"success_redirect_url" => $this->url->link('extension/payment/epoint/callback')."&order_id=".$order_info['order_id'],
			"error_redirect_url" => $this->url->link('extension/payment/epoint/callback')."&order_id=".$order_info['order_id']
		);
		$_epoint = new EpointGateway($this->config->get('payment_epoint_app_id'), $this->config->get('payment_epoint_merchant_private_key'));
		$_epoint->setConfig($values);
		if ($_epoint->execute()) {

			$data['action'] = $_epoint->return_url;
			return $this->load->view('extension/payment/epoint', $data);
		}
		else {
			echo $_epoint->error_message;
		}

		
	}
	public function callback() {
		require_once(DIR_APPLICATION.'model/extension/payment/epoint/epoint.class.php');
		if (@$_GET['order_id']):
			$order_id = @$_GET['order_id'];
			$_epoint = new EpointGateway($this->config->get('payment_epoint_app_id'), $this->config->get('payment_epoint_merchant_private_key'));
			$_status = $_epoint->getStatus(@$_GET['order_id']); 
			var_dump($_status);
			if (($_status != null) && ($_status['status'] == 'success')):
				
				$this->load->model('checkout/order');
				$order_info = $this->model_checkout_order->getOrder($order_id);
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_epoint_order_status_id'), '', true);
				$this->response->redirect($this->url->link('checkout/success'));
			else: 
				$this->response->redirect($this->url->link('checkout/failure'));
			endif;
		else:
			$this->response->redirect($this->url->link('checkout/failure'));
		endif;
	}
}
