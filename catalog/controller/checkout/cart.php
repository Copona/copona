<?php

class ControllerCheckoutCart extends Controller {

    public function index() {
        $data = $this->load->language('checkout/cart');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
        ];

        $data['breadcrumbs'][] = [
            'href' => $this->url->link('checkout/cart'),
            'text' => $this->language->get('heading_title'),
        ];

        if ($this->cart->hasProducts() || !empty($this->session->data['vouchers'])) {
            $data['heading_title'] = $this->language->get('heading_title');

            if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
                $data['error_warning'] = $this->language->get('error_stock');
            } elseif (isset($this->session->data['error'])) {
                $data['error_warning'] = $this->session->data['error'];

                unset($this->session->data['error']);
            } else {
                $data['error_warning'] = '';
            }

            if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
                $data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/login'), $this->url->link('account/register'));
            } else {
                $data['attention'] = '';
            }

            if (isset($this->session->data['success'])) {
                $data['success'] = $this->session->data['success'];

                unset($this->session->data['success']);
            } else {
                $data['success'] = '';
            }

            $data['action'] = $this->url->link('checkout/cart/edit', '', true);

            if ($this->config->get('config_cart_weight')) {
                $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
            } else {
                $data['weight'] = '';
            }

            $this->load->model('tool/image');
            $this->load->model('tool/upload');

            $data['products'] = [];

            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
                }

                if ($product['image']) {
                    $image = $this->model_tool_image->{Config::get('theme_default_product_cart_thumb_resize', 'resize')}($product['image'], $this->config->get($this->config->get('config_theme') . '_image_cart_width'),
                        $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
                } else {
                    $image = $this->model_tool_image->{Config::get('theme_default_product_cart_thumb_resize', 'resize')}(Config::get('config_no_image', 'placeholder.png'),
                        $this->config->get($this->config->get('config_theme') . '_image_cart_width'), $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
                }

                $option_data = [];

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = [
                        'name'  => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
                    ];
                }

                // // Display prices
                // if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                // 	$unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                //
                // 	$price = $this->currency->format($unit_price, $this->session->data['currency']);
                // 	$total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
                // } else {
                // 	$price = false;
                // 	$total = false;
                // }

                // Display prices
                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format2($product['price_enduser']);
                    $total = $this->currency->format2($product['price_enduser'] * $product['quantity']);
                } else {
                    $price = false;
                    $total = false;
                }

                $recurring = '';

                if ($product['recurring']) {
                    $frequencies = [
                        'day'        => $this->language->get('text_day'),
                        'week'       => $this->language->get('text_week'),
                        'semi_month' => $this->language->get('text_semi_month'),
                        'month'      => $this->language->get('text_month'),
                        'year'       => $this->language->get('text_year'),
                    ];

                    if ($product['recurring']['trial']) {
                        $recurring = sprintf($this->language->get('text_trial_description'),
                                $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')),
                                    $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                    }

                    if ($product['recurring']['duration']) {
                        $recurring .= sprintf($this->language->get('text_payment_description'),
                            $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')),
                                $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                    } else {
                        $recurring .= sprintf($this->language->get('text_payment_cancel'),
                            $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')),
                                $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                    }
                }

                $data['products'][] = [
                    'cart_id'      => $product['cart_id'],
                    'product_id'   => $product['product_id'],
                    'thumb'        => $image,
                    'name'         => $product['name'],
                    'model'        => $product['model'],
                    'option'       => $option_data,
                    'recurring'    => $recurring,
                    'content_meta' => $product['content_meta'],
                    'quantity'     => $product['quantity'],
                    'stock'        => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward'       => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                    'price'        => $price,
                    'total'        => $total,
                    'href'         => $this->url->link('product/product', 'product_id=' . $product['product_id']),
                ];
            }

            // Traverse prepared products array for checkout template
            $this->hook->getHook('checkout/cart/index/afterProducts', $data['products']);

            // Gift Voucher
            $data['vouchers'] = [];

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $key => $voucher) {
                    $data['vouchers'][] = [
                        'key'         => $key,
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                        'remove'      => $this->url->link('checkout/cart', 'remove=' . $key),
                    ];
                }
            }

            $data['totals'] = $this->cart->getTotals_azon();


            $data['continue'] = $this->url->link('common/home');

            $data['checkout'] = $this->url->link('checkout/checkout', '', true);
            $data['checkout_guest'] = $this->url->link('checkout/checkout/guest', '', true);

            $this->load->model('extension/extension');

            $data['modules'] = [];

            $files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

            if ($files) {
                foreach ($files as $file) {
                    $result = $this->load->controller('extension/total/' . basename($file, '.php'));

                    if ($result) {
                        $data['modules'][] = $result;
                    }
                }
            }


            // Needed to redirect, do something with output, if Hook defined!
            if (isset($this->request->post['hook'])) {
                $this->hook->getHook('checkout/cart/index/output', $data);
            } else {
                if (isset($this->request->post['checkout'])) {
                    echo $this->response->setOutput($this->load->view('checkout/cart_info', $data));
                } else {
                    $data['column_left'] = $this->load->controller('common/column_left');
                    $data['column_right'] = $this->load->controller('common/column_right');
                    $data['content_top'] = $this->load->controller('common/content_top');
                    $data['content_bottom'] = $this->load->controller('common/content_bottom');
                    //Whats this for? content_data ?
                    $data['content_data'] = $this->load->controller('common/content_data');
                    $data['footer'] = $this->load->controller('common/footer');
                    $data['header'] = $this->load->controller('common/header');

                    $this->response->setOutput($this->load->view('checkout/cart', $data));
                }
            }
        } else {

            $data['heading_title'] = $this->language->get('heading_title');

            $data['text_error'] = $this->language->get('text_empty');

            $data['button_continue'] = $this->language->get('button_continue');

            $data['continue'] = $this->url->link('common/home');

            unset($this->session->data['success']);

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->hook->getHook('checkout/cart/index/notFound', $data['products']);
            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function add() {
        $this->load->language('checkout/cart');

        $json = [];

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];
        } else {
            $product_id = 0;
        }

        $this->load->model('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            if (isset($this->request->post['quantity']) && ((int)$this->request->post['quantity'] >= $product_info['minimum'])) {
                $quantity = (int)$this->request->post['quantity'];
            } else {
                $quantity = $product_info['minimum'] ? $product_info['minimum'] : 1;
            }

            if (isset($this->request->post['option'])) {
                $option = array_filter($this->request->post['option']);
            } else {
                $option = [];
            }

            $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                    $json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
                    $this->flash->error( sprintf($this->language->get('error_required'), $product_option['name']) ) ;
                    $json['flash'] = sprintf($this->language->get('error_required'), $product_option['name']);
                }
            }

            if (isset($this->request->post['recurring_id'])) {
                $recurring_id = $this->request->post['recurring_id'];
            } else {
                $recurring_id = 0;
            }

            $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

            if ($recurrings) {
                $recurring_ids = [];

                foreach ($recurrings as $recurring) {
                    $recurring_ids[] = $recurring['recurring_id'];
                }

                if (!in_array($recurring_id, $recurring_ids)) {
                    $json['error']['recurring'] = $this->language->get('error_recurring_required'); 
                }
            }
            $hook_data = [
                'quantity' => $quantity,
                'json'     => $json,
                'product'  => $product_info,
            ];
            $this->hook->getHook('checkout/cart/add/beforeadd', $hook_data);

            if (!$json && !$hook_data['json']) {
                $this->cart->add((int)$this->request->post['product_id'], $quantity, $option, $recurring_id);

                $json['success'] = sprintf($this->language->get('text_success'),
                    $this->url->link('product/product',
                        'product_id=' . $this->request->post['product_id']),
                    $product_info['name'], $this->url->link('checkout/cart'));
                $json['text_added_to_cart'] = $this->language->get('text_added_to_cart');

                // Deprecated?
                $json['current_product_in_cart'] = $this->cart->countProducts((int)$this->request->post['product_id']);
                $json['current_cart_total_count'] = $this->cart->countProducts();

                $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'],
                    $this->url->link('checkout/cart'));

                // Unset all shipping and payment methods
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
            } elseif ($json || $hook_data['json']) {

                $json = $hook_data['json'];

                $json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));

                // $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0),
                //  $this->currency->format($total, $this->session->data['currency']));
                // $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0),
                // $this->currency->format($total, $this->session->data['currency']));
            }
        }

        $this->hook->getHook('checkout/cart/index/afteradd', $json);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function edit() {
        $this->load->language('checkout/cart');
        $json = [];
        $this->hook->getHook('checkout/cart/edit/before', $this->request->post['quantity']);
        // Update
        if (!empty($this->request->post['quantity'])) {
            foreach ($this->request->post['quantity'] as $key => $value) {
                $this->cart->update($key, $value);
            }
            // This is Tricky - "too soon" redirect, reason for bugs!
            if (!empty($this->request->post['method']) && $this->request->post['method'] == 'ajax') {
                $json['status'] = 'OK';
                $json['current_product_in_cart'] = $this->cart->countProducts((int)$this->request->post['product_id']);
                $json['current_cart_total_count'] = $this->cart->countProducts();
                echo json_encode($json);
                return false;
            }

            $this->session->data['success'] = $this->language->get('text_remove');

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);

            $this->response->redirect($this->url->link('checkout/checkout/guest'));
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    public function edit_only() {

        // Update
        if (!empty($this->request->post['quantity'])) {
            foreach ($this->request->post['quantity'] as $key => $value) {
                $this->cart->update($key, $value);
            }

            $this->session->data['success'] = $this->language->get('text_remove');

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);
            return true;
        } else {
            return false;
        }
    }

    public function remove() {
        $this->load->language('checkout/cart');

        $json = [];

        // Remove
        if (isset($this->request->post['key'])) {
            $this->cart->remove($this->request->post['key']);

            unset($this->session->data['vouchers'][$this->request->post['key']]);

            $json['success'] = $this->language->get('text_remove');

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);

            // Totals
            $this->load->model('extension/extension');

            $totals = [];
            $taxes = $this->cart->getTaxes();
            $total = 0;

            // Because __call can not keep var references so we put them into an array.
            $total_data = [
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total,
            ];

            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = [];

                $results = $this->model_extension_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);

                        // We have to put the totals in an array so that they pass by reference.
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = [];

                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);
            }

            $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0),
                $this->currency->format($total, $this->session->data['currency']));
        }
        $json['current_cart_total_count'] = $this->cart->countProducts();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Generate Table for Cart Info.
     * @return HTML5_Data
     *
     */
    public function getCartTable() {
        $data = $this->getCartTableData();
        return $this->load->view('checkout/cart_info', $data);
    }

    public function cart_table() {

        $this->edit_only();

        $data = $this->getCartTableData();
        $this->response->setOutput($this->load->view('checkout/cart_info', $data));
    }

    public function getCartTableData() {
        $data = $this->load->language('checkout/cart');
        $data['heading_title'] = $this->language->get('heading_title');

        if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
            $data['error_warning'] = $this->language->get('error_stock');
        } elseif (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];

            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
            $data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/login'), $this->url->link('account/register'));
        } else {
            $data['attention'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['action'] = $this->url->link('checkout/cart/edit', '', true);

        if ($this->config->get('config_cart_weight')) {
            $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
        } else {
            $data['weight'] = '';
        }

        $this->load->model('tool/image');
        $this->load->model('tool/upload');

        $data['products'] = [];

        $products = $this->cart->cartProducts;

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
            }

            if ($product['image']) {
                $image = $this->model_tool_image->{Config::get('theme_default_product_cart_thumb_resize', 'resize')}($product['image'], $this->config->get($this->config->get('config_theme') . '_image_cart_width'),
                    $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = $this->model_tool_image->{Config::get('theme_default_product_cart_thumb_resize', 'resize')}(Config::get('config_no_image', 'placeholder.png'),
                    $this->config->get($this->config->get('config_theme') . '_image_cart_width'), $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
            }

            $option_data = [];

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = [
                    'name'  => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
                ];
            }

            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

                $price = $this->currency->format($unit_price, $this->session->data['currency']);
                $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
            } else {
                $price = false;
                $total = false;
            }

            $recurring = '';

            if ($product['recurring']) {
                $frequencies = [
                    'day'        => $this->language->get('text_day'),
                    'week'       => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month'      => $this->language->get('text_month'),
                    'year'       => $this->language->get('text_year'),
                ];

                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'),
                            $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')),
                                $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }

                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'),
                        $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                        $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'),
                        $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                        $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }

            $data['products'][] = [
                'cart_id'      => $product['cart_id'],
                'product_id'   => $product['product_id'],
                'thumb'        => $image,
                'name'         => $product['name'],
                'model'        => $product['model'],
                'option'       => $option_data,
                'recurring'    => $recurring,
                'content_meta' => $product['content_meta'],
                'quantity'     => $product['quantity'],
                'stock'        => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                'reward'       => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                'price'        => $price,
                'total'        => $total,
                'href'         => $this->url->link('product/product', 'product_id=' . $product['product_id']),
            ];
        }

        // Traverse prepared products array for checkout template
        $this->hook->getHook('checkout/cart/index/afterProducts', $data['products']);

        // Gift Voucher
        $data['vouchers'] = [];

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $data['vouchers'][] = [
                    'key'         => $key,
                    'description' => $voucher['description'],
                    'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                    'remove'      => $this->url->link('checkout/cart', 'remove=' . $key),
                ];
            }
        }

        // Totals

        $data['totals'] = $this->cart->getTotals_azon();


        $data['continue'] = $this->url->link('common/home');

        $data['checkout'] = $this->url->link('checkout/checkout', '', true);
        $data['checkout_guest'] = $this->url->link('checkout/checkout/guest', '', true);

        $this->load->model('extension/extension');
        $data['modules'] = [];
        $files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

        if ($files) {
            foreach ($files as $file) {
                $result = $this->load->controller('extension/total/' . basename($file, '.php'));

                if ($result) {
                    $data['modules'][] = $result;
                }
            }
        }


        return $data;
    }

}
