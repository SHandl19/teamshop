<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheit: Direkten Zugriff verhindern
}

class WC_Product_Teamshop_Data_Store_CPT extends WC_Product_Data_Store_CPT {
    public function __construct() {

    }


    /**
     * Setzt den Produkttyp beim Laden aus der Datenbank.
     *
     * @param WC_Product $product Produktobjekt
     */
    public function read(&$product) {
        parent::read($product);
        
        if ($product->get_type() === 'teamshop_product') {
            $product->set_props([
                'type' => 'teamshop_product',
            ]);
    
            // 🔥 Korrekt als Meta speichern
            $product->update_meta_data('_product_type', 'team_product'); 
            $product->save_meta_data(); // Meta-Daten wirklich speichern
        }
    }
    

    /**
     * Speichert die Produktdaten in der Datenbank.
     *
     * @param WC_Product $product Produktobjekt
     * @param bool $force Ob erzwungen gespeichert werden soll
     */
    public function update(&$product, $force = false) {
        parent::update($product, $force);
    
        if ($product->get_type() === 'teamshop_product') {
            $product->update_meta_data('_product_type', 'team_product');
            $product->save_meta_data(); // 🔥 Speichern der Metadaten
        }
    }
    
    public function create(&$product) {
        parent::create($product);
    
        if ($product->get_type() === 'teamshop_product') {
            $product->update_meta_data('_product_type', 'team_product');
            $product->save_meta_data(); // 🔥 Speichern der Metadaten
        }
    }
    
    

    /**
     * Speichert Produktvariationen korrekt in WooCommerce.
     *
     * @param WC_Product_Variation $variation Varianten-Produktobjekt
     */
    public function update_variation(&$variation) {
        if ($variation->get_type() === 'variation') {
            parent::update($variation);
            update_post_meta($variation->get_id(), '_product_type', 'product_variation');
            update_post_meta($variation->get_id(), '_price', $variation->get_price());
            update_post_meta($variation->get_id(), '_stock', $variation->get_stock_quantity());
            update_post_meta($variation->get_id(), '_sku', $variation->get_sku());
        }
    }
}
add_filter('woocommerce_data_stores', function($stores) {
    static $registered = false;

    if ($registered) {
        return $stores;
    }

    $registered = true;
    
    $stores['product-teamshop'] = 'WC_Product_Teamshop_Data_Store_CPT';
    return $stores;
});



