<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheit: Direkten Zugriff verhindern
}

// WooCommerce-Produkttyp definieren
class WC_Product_Teamshop extends WC_Product {

    /**
     * Produkttyp für Teamshop-Produkte.
     *
     * @var string
     */
    public string $product_type = 'teamshop'; // ✅ FIX für PHP 8+

    /**
     * Konstruktor für das Teamshop-Produkt.
     *
     * @param int|WC_Product $product Produkt-ID oder Produkt-Objekt
     */
    public function __construct($product) {
        parent::__construct($product);
    }

    /**
     * Gibt den Produkttyp zurück.
     *
     * @return string Produkttyp
     */
    public function get_type(): string {
        return 'teamshop_product';
    }

    /**
     * 🛑 Entfernt WooCommerce's `add-to-cart-variation.js` für Teamshop-Produkte.
     */
    public static function remove_woocommerce_scripts() {
        if (!is_singular('product')) {
            return;
        }

        global $product;
        if (!$product || $product->get_type() !== 'teamshop_product') {
            return;
        }

        // ✅ Standard-Skripte vor der Einbindung entfernen
        add_action('wp_print_scripts', function () {
            wp_dequeue_script('wc-add-to-cart-variation');
            wp_deregister_script('wc-add-to-cart-variation');
            wp_dequeue_script('wc-gzd-add-to-cart-variation');
            wp_deregister_script('wc-gzd-add-to-cart-variation');
        }, 100);
    }

    /**
     * ✅ Eigene Teamshop-JS nur auf Teamshop-Produktseiten laden.
     */
    public static function enqueue_teamshop_frontend_scripts() {
        if (!is_singular('product')) {
            return;
        }

        global $product;
        if (!$product || $product->get_type() !== 'teamshop_product') {
            return;
        }

        // ✅ Eigene Teamshop-JS einbinden
        wp_enqueue_script(
            'teamshop-add-to-cart',
            plugins_url('assets/js/teamshop-add-to-cart-frontend.js', __DIR__), 
            ['jquery'],
            null,
            true
        );

        // AJAX-Parameter für JS verfügbar machen
        wp_localize_script('teamshop-add-to-cart', 'teamshop_ajax', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'product_id' => get_the_ID(),
            'security'   => wp_create_nonce('teamshop_nonce')
        ));
    }

    /**
     * ✅ WooCommerce-Tabs für Teamshop-Produkte sichtbar machen.
     */
    public static function enable_teamshop_product_tabs($tabs) {
        $tabs['general']['class'][] = 'show_if_teamshop_product';   // 🛠 "Allgemeines" aktivieren
        $tabs['inventory']['class'][] = 'show_if_teamshop_product'; // 🏪 "Lagerbestand" aktivieren
        return $tabs;
    }

    /**
     * ✅ Sicherstellen, dass die Tabs im Backend angezeigt werden.
     */
    public static function enable_teamshop_panels() {
        echo '<script>
            jQuery(document).ready(function($) {
                $(".show_if_teamshop_product").show();
            });
        </script>';
    }

    /**
     * ✅ Lagerverwaltungsoptionen für Teamshop-Produkte hinzufügen
     */
    public static function add_inventory_fields() {
        global $post;
        $product = wc_get_product($post->ID);

        if ($product && $product->get_type() === 'teamshop_product') {
            echo '<div class="options_group show_if_teamshop_product">';

            woocommerce_wp_checkbox([
                'id'            => '_manage_stock',
                'label'         => __('Lagerbestand verwalten?', 'woocommerce'),
                'description'   => __('Aktiviere diese Option, um den Lagerbestand für dieses Produkt zu verwalten.', 'woocommerce'),
            ]);

            woocommerce_wp_text_input([
                'id'                => '_stock',
                'label'             => __('Anzahl auf Lager', 'woocommerce'),
                'desc_tip'          => 'true',
                'type'              => 'number',
                'custom_attributes' => ['step' => '1', 'min' => '0'],
            ]);

            woocommerce_wp_select([
                'id'      => '_stock_status',
                'label'   => __('Lagerbestand', 'woocommerce'),
                'options' => [
                    'instock'     => __('Vorrätig', 'woocommerce'),
                    'outofstock'  => __('Nicht vorrätig', 'woocommerce'),
                    'onbackorder' => __('Auf Lieferrückstand', 'woocommerce'),
                ],
            ]);

            woocommerce_wp_select([
                'id'      => '_backorders',
                'label'   => __('Lieferrückstand erlauben?', 'woocommerce'),
                'options' => [
                    'no'          => __('Nicht erlauben', 'woocommerce'),
                    'notify'      => __('Erlauben, aber Kunde benachrichtigen', 'woocommerce'),
                    'yes'         => __('Erlauben', 'woocommerce'),
                ],
            ]);

            echo '</div>';
        }
    }

    /**
     * ✅ Lagerverwaltungsdaten für Teamshop-Produkte speichern
     */
    public static function save_inventory_fields($post_id) {
        $product = wc_get_product($post_id);

        if ($product && $product->get_type() === 'teamshop_product') {
            $manage_stock = isset($_POST['_manage_stock']) ? 'yes' : 'no';
            $stock = isset($_POST['_stock']) ? intval($_POST['_stock']) : 0;
            $stock_status = $_POST['_stock_status'] ?? 'instock';
            $backorders = $_POST['_backorders'] ?? 'no';

            update_post_meta($post_id, '_manage_stock', $manage_stock);
            update_post_meta($post_id, '_stock', $stock);
            update_post_meta($post_id, '_stock_status', $stock_status);
            update_post_meta($post_id, '_backorders', $backorders);
        }
    }

    /**
     * ✅ Hooks registrieren
     */
    public static function register_hooks() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'remove_woocommerce_scripts'), 20);
        add_action('wp_footer', array(__CLASS__, 'enqueue_teamshop_frontend_scripts'));

        // WooCommerce-Produkt-Tabs erweitern
        add_filter('woocommerce_product_data_tabs', array(__CLASS__, 'enable_teamshop_product_tabs'));
        add_action('woocommerce_product_data_panels', array(__CLASS__, 'enable_teamshop_panels'));

        // Lagerverwaltung hinzufügen
        add_action('woocommerce_product_options_inventory_product_data', array(__CLASS__, 'add_inventory_fields'));
        add_action('woocommerce_process_product_meta', array(__CLASS__, 'save_inventory_fields'));
    }
}

// ✅ Hook nur einmal aufrufen
WC_Product_Teamshop::register_hooks();
