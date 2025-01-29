<?php
/**
 * Template für Teamshop-Produkte
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $product;

if (!$product || !is_a($product, 'WC_Product')) {
    $product_id = get_the_ID();
    $product = wc_get_product($product_id);
}

if (!$product) {
    return; // Falls kein Produkt existiert, breche ab.
}

// Stelle sicher, dass NUR Teamshop-Produkte dieses Template nutzen
if (!$product || !in_array($product->get_type(), array('team_product', 'teamshop_product'))) {
    wc_get_template('single-product.php');
    return;
}


get_header('shop');

/**
 * woocommerce_before_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action('woocommerce_before_main_content');
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-6">
            <?php do_action('woocommerce_before_single_product_summary'); ?>
        </div>
        <div class="col-lg-6">
            <div class="summary entry-summary">
                <?php do_action('woocommerce_single_product_summary'); ?>
            </div>

            <div id="teamshop-variations">
                <h3>Verfügbare Varianten</h3>
                <?php
                $variation_ids = get_posts([
                    'post_type'   => 'product_variation',
                    'post_parent' => $product->get_id(),
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields'      => 'ids'
                ]);

                if (empty($variation_ids)) {
                    echo '<p style="color:red;">❌ Keine Varianten gefunden!</p>';
                } else {
                    $attribute_terms = [];
                    $attribute_prices = [];
                    $variation_data = [];

                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if (!$variation) {
                            continue;
                        }

                        $variation_attributes = $variation->get_attributes();
                        $price = $variation->get_price();

                        if (empty($variation_attributes)) {
                            $variation_meta = get_post_meta($variation_id);
                            foreach ($variation_meta as $meta_key => $meta_value) {
                                if (strpos($meta_key, 'attribute_pa_') === 0) {
                                    $attribute_name = str_replace('attribute_', '', $meta_key);
                                    $variation_attributes[$attribute_name] = $meta_value[0];
                                }
                            }
                        }

                        foreach ($variation_attributes as $attribute_name => $attribute_value) {
                            if (!isset($attribute_terms[$attribute_name])) {
                                $attribute_terms[$attribute_name] = [];
                            }
                            if (!in_array($attribute_value, $attribute_terms[$attribute_name])) {
                                $attribute_terms[$attribute_name][] = $attribute_value;
                            }

                            if (!isset($attribute_prices[$attribute_name])) {
                                $attribute_prices[$attribute_name] = $price;
                            }

                            $variation_data[$attribute_name][$attribute_value] = $variation_id;
                        }
                    }

                    foreach ($attribute_terms as $attribute_name => $terms) {
                        $formatted_price = isset($attribute_prices[$attribute_name]) ? wc_price($attribute_prices[$attribute_name]) : 'N/A';

                        echo '<div class="teamshop-attribute" data-attribute="' . esc_attr($attribute_name) . '">
                                <strong>' . wc_attribute_label($attribute_name) . ': ' . $formatted_price . '</strong><br>';

                        foreach ($terms as $term_slug) {
                            $variation_id_for_term = isset($variation_data[$attribute_name][$term_slug]) ? $variation_data[$attribute_name][$term_slug] : '';

                            $disabled = '';
                            if (!$variation_id_for_term || !wc_get_product($variation_id_for_term)->is_in_stock()) {
                                $disabled = 'swatch btn btn-outline-secondary disabled';
                            }

                            echo '<button class="teamshop-swatch swatch btn btn-outline-primary ' . esc_attr($disabled) . '" 
                                data-term="' . esc_attr($term_slug) . '" 
                                data-variation-id="' . esc_attr($variation_id_for_term) . '">' . esc_html($term_slug) . '</button> ';
                        }
                        echo '</div><br>';
                    }

                    // ✅ Hook für spätere Plugins
                    do_action('woocommerce_before_add_to_cart_form');

                    // ✅ Mengenfeld
                    echo '<div class="teamshop-quantity mt-3">
                            <label for="teamshop-quantity">Menge:</label>
                            <input type="number" id="teamshop-quantity" class="form-control" value="1" min="1">
                          </div>';

                    // ✅ Warenkorb-Button
                    echo '<button id="teamshop-add-to-cart" class="btn btn-primary mt-3" disabled>
                            <i class="bi bi-cart"></i> In den Warenkorb
                          </button>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * woocommerce_after_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('woocommerce_after_main_content');

get_footer('shop');
