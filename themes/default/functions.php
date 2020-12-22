<?php

!defined('DIR_SYSTEM') ? die() : false;

/*
 * This file will be included at catalog/controller/startup.php as first priority
 * Right before inclusion of Theme specific functions.php
 *
 */

if (APPLICATION == 'catalog') {
    // The new addScripts and addStyles method:
    // Theme name: $this->config->get('theme_name')
    // $this->document->addScript('themes/default/assets/vendor/jquery/jquery-2.1.1.min.js');


    // Bootstrap 5 update
    $this->document->addScript('https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js', 'header');
    $this->document->addStyle('https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css', 'stylesheet', 'screen');

    $this->document->addScript('assets/vendor/datetimepicker/moment.js', 'header', 'product/product');

    // $this->document->addScript('themes/default/assets/vendor/jquery/jquery-ui.button.min.js', 'footer');
    $this->document->addScript('themes/default/assets/vendor/notify/notify.min.js', 'footer');
    $this->document->addScript('assets/vendor/jquery.print/jquery.print.min.js', 'footer');
    $this->document->addScript('assets/js/common.js', 'footer');

    $this->document->addScript('assets/vendor/magnific/jquery.magnific-popup.min.js', 'footer', 'product/product');
    // $this->document->addScript('assets/vendor/datetimepicker/bootstrap-datetimepicker.min.js', 'header', 'product/product');

    $this->document->addStyle('assets/vendor/font-awesome/css/font-awesome.min.css', 'stylesheet', 'screen');
    $this->document->addStyle('//fonts.googleapis.com/css?family=Open+Sans:400,400i,300,700', 'stylesheet', 'screen');
    $this->document->addStyle('assets/vendor/magnific/magnific-popup.css', 'stylesheet', 'screen');
    // $this->document->addStyle('assets/vendor/datetimepicker/bootstrap-datetimepicker.min.css', 'stylesheet', 'screen','product/product');

    $this->document->addStyle('assets/css/stylesheet.css', 'stylesheet', 'screen');
    $this->document->addStyle('assets/css/additional.css', 'stylesheet', 'screen');

}
/* * * Theme specific - override Settings !  * * */

// Example:
$template_config_settings = [
    'theme_default_product_info_thumb_resize'            => 'resize',
    'theme_default_product_info_popup_resize'            => 'resize',
    'theme_default_product_info_image_popup_resize'      => 'resize',
    'theme_default_product_info_image_mid_resize'        => 'resize',
    'theme_default_extension_module_featured'            => 'resize',
    'theme_default_product_short_description_length'     => 250,
    'theme_default_product_category_list_resize'         => 'cropsize',
    'theme_default_manufacturers_thumb_resize'           => 'propsize',
    'theme_default_product_category_popup_resize'        => 'propsize',
    'theme_default_product_cart_thumb_resize'            => 'cropsize',
    'theme_default_product_info_group_resize'            => 'resize',
    'theme_default_latest_thumb_resize'                  => 'resize',
    'theme_default_category_sort'                        => 'p.sort_order',
    'theme_default_category_order'                       => 'ASC',
    'theme_default_product_limits'                       => [8, 16, 32],
    'theme_default_category_show_subcategories_products' => true,
    'config_watermark_image_path'                        => DIR_IMAGE . "watermark.png",
    'config_watermark_resize'                            => false,
    'config_watermark_propsize'                          => false,
    'config_watermark_downsize'                          => false,
    'config_watermark_cropsize'                          => false,
    'config_watermark_onesize'                           => false,
    'checkout_serial_fields'                             => [
        ['key' => 'company_name', 'validate' => false],
        ['key' => 'address_1', 'validate' => false],
        ['key' => 'vat_num', 'validate' => false],
        ['key' => 'reg_num', 'validate' => false],
        ['key' => 'address_2', 'validate' => false],
        ['key' => 'postcode2', 'validate' => false],
        ['key' => 'customer_type', 'validate' => false],
    ],
    'theme_default_extension_module_slideshow_resize'    => 'propsize',
    'theme_default_image_cart_resize'                    => 'resize',

    'theme_default_bestseller_thumb_resize'                 => 'resize',
    'theme_default_module_category_show_only_subcategories' => true, // model/extension/category - method will show only sub-categories.
    'admin_category_autocomplete_limit'                     => 15,
    // 'theme_default_image_category_width'       => 80,
    // 'theme_default_image_category_height'      => 80,
];

foreach ($template_config_settings as $key => $val) {
    $this->config->set($key, $val);
}

/* Introducing HOOKS */
/* * ********** Example for product/index/after ************* */

/*
 * To set hook.
 * string = hook name
 * string = callback functions
 */

$this->hook->setHook('product/index/after', 'default_remove_image');
$this->hook->setHook('product/index/after', 'content_meta');
$this->hook->setHook('footer/index/after', 'modifyFooter');


/*
 * callback functions
 * reference = to array
 *
 */
if (!function_exists('default_remove_image')) {
    function default_remove_image(&$data, &$registry) {
        $db = $registry->get('db');
        $registry->get('load')->model('catalog/product');
        // Real modifications.
        //
        // $product = $registry->get('model_catalog_product')->getProduct(51);
        // prd($product);
        // prd($db->query('select * from oc_product limit 10'));
        /*
          $data['image_mid'] = '';
          $data['popup'] = '';
          $data['thumb'] = '';
         */
    }
}

if (!function_exists('content_meta')) {
    function content_meta(&$data, &$registry) {
        $config = $registry->get('config');
        $url = $registry->get('url');
        if (!empty($data['content_meta'])) {
            foreach ($data['content_meta'] as $meta_type => $val) {
                // Product Videos
                if ($meta_type == 'product_video') {
                    $data['product_videos'] = [];
                    foreach ($val as $video) {
                        $data['product_videos'][] = [
                            'video'     => 'https://www.youtube.com/watch?v=' . $video['video'][$config->get('config_language_id')] . '',
                            'video_src' => $url->link('common/youtube',
                                'inpt=' . $video['video'][$config->get('config_language_id')] . '&quality=maxres&play')
                            //   HTTPS_SERVER . 'youtube/yt-thumb.php?inpt=' . $video . '&quality=hq&play"'
                        ];

                    }
                }
            }
        }
    }
}

