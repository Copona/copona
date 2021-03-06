<?php

class ControllerExtensionPaymentBankTransfer extends Controller {
    public function index() {
        $this->load->language('extension/payment/bank_transfer');

        $data['text_instruction'] = $this->language->get('text_instruction');
        $data['text_description'] = $this->language->get('text_description');
        $data['text_payment'] = $this->language->get('text_payment');
        $data['text_loading'] = $this->language->get('text_loading');

        $data['button_confirm'] = $this->language->get('button_confirm');

        // $data['bank'] = nl2br($this->config->get('bank_transfer_bank' . $this->config->get('config_language_id')));
        $data['bank'] = html_entity_decode($this->config->get('bank_transfer_bank' . $this->config->get('config_language_id')), ENT_QUOTES, 'UTF-8');

        $data['continue'] = $this->url->link('checkout/success');

        return $this->load->view('extension/payment/bank_transfer', $data);
    }

    public function confirm() {

        $json = [];

        if ($this->session->data['payment_method']['code'] == 'bank_transfer') {
            $this->load->language('extension/payment/bank_transfer');

            $this->load->model('checkout/order');

            $instruction = $this->config->get('bank_transfer_bank' . $this->config->get('config_language_id'));
            $instruction = html_entity_decode($instruction, ENT_QUOTES, 'UTF-8');
            $instruction = html_to_plaintext($instruction, true);

            $comment = $this->language->get('text_instruction') . ":";
            $comment .= $instruction;
            $comment .= $this->language->get('text_payment');

            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'],
                $this->config->get('bank_transfer_order_status_id'), $comment, true);

            $json['message'] = 'Order created!';
            $json['order_id'] = $this->session->data['order_id'];

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));

        }
    }
}