<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsprüfung
}

// AJAX-Hook registrieren
add_action('wp_ajax_teamshop_add_to_cart_callback', 'teamshop_add_to_cart_callback');
add_action('wp_ajax_nopriv_teamshop_add_to_cart_callback', 'teamshop_add_to_cart_callback');

function teamshop_add_to_cart_callback() {
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        wp_send_json_error(['message' => '❌ Keine gültige Produkt-ID!']);
    }

    if (!isset($_POST['quantity']) || empty($_POST['quantity'])) {
        wp_send_json_error(['message' => '❌ Keine Menge übermittelt!']);
    }

    if (!isset($_POST['selected_attributes']) || empty($_POST['selected_attributes'])) {
        wp_send_json_error(['message' => '❌ Keine Attribute übermittelt!']);
    }

    $variation_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $selected_attributes = array_map('sanitize_text_field', $_POST['selected_attributes']);

    $variation = wc_get_product($variation_id);
    if (!$variation || !$variation->is_type('variation')) {
        wp_send_json_error(['message' => '❌ Keine gültige Produktvariante!']);
    }

    if (!WC()->cart) {
        wp_send_json_error(['message' => '❌ WooCommerce Warenkorb konnte nicht geladen werden!']);
    }

    // ✅ WooCommerce dazu zwingen, nur die gewählten Attribute zu speichern
    $cart_item_data = [
        'variation_id'        => $variation_id,
        'selected_attributes' => $selected_attributes,
        'variation'           => $selected_attributes, // WooCommerce MUSS jetzt nur diese Werte anzeigen
    ];

    $added = WC()->cart->add_to_cart($variation_id, $quantity, 0, [], $cart_item_data);
    if ($added) {
        wp_send_json_success(['message' => '✅ Produkt erfolgreich in den Warenkorb gelegt!']);
    } else {
        wp_send_json_error(['message' => '⚠️ Produkt konnte nicht hinzugefügt werden.']);
    }
}

function teamshop_enqueue_scripts() {
    if (is_singular('product')) {
        global $post;

        wp_enqueue_script(
            'teamshop-add-to-cart',
            plugins_url('assets/js/teamshop-add-to-cart-frontend.js', __DIR__), 
            ['jquery'],
            null,
            true
        );

        wp_localize_script('teamshop-add-to-cart', 'teamshop_ajax', [
            'ajax_url'   => admin_url('admin-ajax.php'),
            'product_id' => $post->ID,
            'security'   => wp_create_nonce('teamshop_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'teamshop_enqueue_scripts');

add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    $filtered_data = [];

    // Entferne ALLE automatisch hinzugefügten Attribute von WooCommerce
    foreach ($item_data as $index => $data) {
        if (strpos($data['name'], 'pa_') !== false) {
            unset($item_data[$index]);
        }
    }

    // Nur die tatsächlich gewählten Attribute anzeigen
    if (!empty($cart_item['selected_attributes']) && is_array($cart_item['selected_attributes'])) {
        foreach ($cart_item['selected_attributes'] as $attribute_name => $attribute_value) {
            if (!empty($attribute_value)) {
                $filtered_data[] = [
                    'name'  => wc_attribute_label($attribute_name),
                    'value' => esc_html($attribute_value),
                ];
            }
        }
    }

    return $filtered_data;
}, 10, 2);


// 🛠 Stellt sicher, dass Variantenattribute in Bestellungen gespeichert werden
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (!empty($values['selected_attributes'])) {
        foreach ($values['selected_attributes'] as $key => $value) {
            $item->add_meta_data(wc_attribute_label($key), $value, true);
        }
    }
}, 10, 4);
