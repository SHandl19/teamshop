<?php
/**
 * Plugin Name: Teamshop Produkte
 * Plugin URI: https://example.com
 * Description: Fügt eine neue Produktart "Teamshop Produkt" zu WooCommerce hinzu.
 * Version: 1.0.0
 * Author: Dein Name
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: teamshop-product-plugin
 */

// Verhindert direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Prüfen, ob WooCommerce aktiv ist
function is_woocommerce_active() {
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

if (!is_woocommerce_active()) {
    return;
}

// Mindestversion von WooCommerce prüfen
define('TEAMSHOP_MIN_WC_VERSION', '9.6.0');
function teamshop_check_wc_version() {
    if (defined('WC_VERSION') && version_compare(WC_VERSION, TEAMSHOP_MIN_WC_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>Teamshop Produkte benötigt WooCommerce '.TEAMSHOP_MIN_WC_VERSION.' oder neuer.</p></div>';
        });
        return false;
    }
    return true;
}

if (!teamshop_check_wc_version()) {
    return;
}

// Hauptklasse des Plugins
class WC_Teamshop_Product_Plugin {

    public function __construct() {
        if (!did_action('woocommerce_loaded')) {
            add_action('woocommerce_loaded', array($this, 'init'));
        }
        
        add_filter('product_type_selector', array($this, 'add_teamshop_product_type'));
        add_action('woocommerce_admin_process_product_object', array($this, 'save_teamshop_product_type'));

        // 🛠 Sicherstellen, dass _product_type immer gesetzt ist
        add_action('save_post_product', array($this, 'ensure_teamshop_product_type'), 10, 3);
    }

    public function init() {
        if (class_exists('WC_Product')) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-wc-product-teamshop.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-wc-product-teamshop-data-store.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-wc-teamshop-product-admin.php';
            require_once plugin_dir_path(__FILE__) . 'includes/ajax-update-price.php';
            require_once plugin_dir_path(__FILE__) . 'includes/teamshop-set-title.php';
        }
    }

    public function register_teamshop_data_store($stores) {
        $stores['product'] = 'WC_Product_Teamshop_Data_Store_CPT'; // 🔥 Fix: WooCommerce akzeptiert nur 'product'
        return $stores;
    }

    // Produkttyp in WooCommerce Dropdown hinzufügen
    public function add_teamshop_product_type($types) {
        $types['teamshop_product'] = __('Teamshop Produkt', 'teamshop-product-plugin');
        return $types;
    }

    // Sicherstellen, dass der Produkttyp für Teamshop-Produkte nach dem Speichern erhalten bleibt
    public function save_teamshop_product_type($product) {
        if ($product instanceof WC_Product && isset($_POST['product_type']) && $_POST['product_type'] === 'teamshop_product') {
            $product->set_type('teamshop_product');

            // 🔥 Sicherstellen, dass _product_type = team_product gesetzt wird
            if (get_post_meta($product->get_id(), '_product_type', true) !== 'team_product') {
                update_post_meta($product->get_id(), '_product_type', 'team_product');
                error_log("✅ Produkt-ID {$product->get_id()} als 'team_product' gespeichert!");
            }
        }
    }

    // 🔄 Sicherstellen, dass _product_type = team_product auch nach dem Speichern gesetzt bleibt
    public function ensure_teamshop_product_type($post_id, $post, $update) {
        if ($post->post_type !== 'product') {
            return;
        }

        $product = wc_get_product($post_id);
        if ($product && $product->get_type() === 'teamshop_product') {
            if (get_post_meta($post_id, '_product_type', true) !== 'team_product') {
                update_post_meta($post_id, '_product_type', 'team_product');
                error_log("🔄 Post-ID {$post_id}: _product_type wurde erneut gesetzt.");
            }
        }
    }
}

// Plugin starten
new WC_Teamshop_Product_Plugin();

// Sicherstellen, dass WooCommerce den Produkttyp kennt
function teamshop_register_product_class($classname, $product_type) {
    if ($product_type === 'teamshop_product') {
        $classname = 'WC_Product_Teamshop';
    }
    return $classname;
}
add_filter('woocommerce_product_class', 'teamshop_register_product_class', 10, 2);

add_filter('template_include', function ($template) {
    global $post;
    if ($post && get_post_meta($post->ID, '_product_type', true) === 'team_product') {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-teamshop-product.php';
        error_log("🚀 VORHER - Teamshop-Template setzen: {$new_template}");

        if (file_exists($new_template)) {
            error_log("✅ FINAL - Teamshop-Template bleibt aktiv!");
            return $new_template;
        }
    }

    error_log("❌ WooCommerce überschreibt mit: {$template}");
    return $template;
}, 999);
