<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheit: Direkten Zugriff verhindern
}

if (!class_exists('WC_Teamshop_Product_Admin')) {
    class WC_Teamshop_Product_Admin {
        public function __construct() {
            add_filter('woocommerce_product_data_tabs', array($this, 'add_teamshop_variation_tabs'));
            add_action('woocommerce_product_data_panels', array($this, 'output_teamshop_variation_tabs'));
			add_action('woocommerce_process_product_meta_teamshop_product', array($this, 'save_teamshop_variations'));
			add_action('wp_ajax_add_teamshop_variation', array($this, 'ajax_add_variation'));
            add_action('wp_ajax_delete_teamshop_variation', array($this, 'ajax_delete_variation'));
            add_action('wp_ajax_get_teamshop_terms', array($this, 'ajax_get_terms'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_teamshop_scripts'));
			add_action('wp_ajax_teamshop_save_variations', array($this, 'ajax_save_teamshop_variations'));
            add_action('wp_ajax_update_teamshop_variation_attributes', array($this, 'ajax_update_variation_attributes'));

			
        }

        public function enqueue_teamshop_scripts() {
    if (!is_admin()) {
        return;
    }

    $screen = get_current_screen();
    if ($screen && $screen->id === 'product') {
        wp_enqueue_script(
            'teamshop-variations-js',
            plugins_url('assets/js/add-to-cart-teamshop.js', dirname(__FILE__)),
            array('jquery'),
            '1.0',
            true
        );
        wp_localize_script('teamshop-variations-js', 'teamshop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('teamshop_nonce')
        ));
    }
}


        public function add_teamshop_variation_tabs($tabs) {
            $tabs['teamshop_add_variations'] = array(
                'label'    => __('Varianten anlegen', 'teamshop-product-plugin'),
                'target'   => 'teamshop_add_variations_panel',
                'class'    => array('show_if_teamshop_product'),
                'priority' => 21,
            );
            $tabs['teamshop_created_variations'] = array(
                'label'    => __('Erstellte Varianten', 'teamshop-product-plugin'),
                'target'   => 'teamshop_created_variations_panel',
                'class'    => array('show_if_teamshop_product'),
                'priority' => 22,
            );
            return $tabs;
        }

        public function output_teamshop_variation_tabs() {
            global $post;
if (!isset($post->ID)) {
    error_log("FEHLER: \$post->ID ist nicht gesetzt!");
    return;
}

$product = wc_get_product($post->ID);
if (!$product) {
    error_log("FEHLER: Produkt konnte nicht geladen werden!");
    return;
}

global $wpdb;
$variations = $wpdb->get_col($wpdb->prepare("
    SELECT ID FROM {$wpdb->posts} 
    WHERE post_parent = %d 
    AND post_type = 'product_variation'
", $post->ID));

            $attributes = wc_get_attribute_taxonomies();
            ?>
            <div id="teamshop_add_variations_panel" class="panel woocommerce_options_panel">
                <div class="options_group">
                    <p><?php _e('Hier kannst du Varianten basierend auf den Produkteigenschaften anlegen.', 'teamshop-product-plugin'); ?></p>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Eigenschaft', 'teamshop-product-plugin'); ?></th>
                                <th><?php _e('Wert', 'teamshop-product-plugin'); ?></th>
                                <th><?php _e('Auf Lager', 'teamshop-product-plugin'); ?></th>
                                <th><?php _e('Aktionen', 'teamshop-product-plugin'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="teamshop-add-variations-list"></tbody>
                    </table>
                    <button type="button" class="button button-primary" id="add-teamshop-variation">+ Variante hinzufügen</button>
                </div>
            </div>
			<div id="teamshop_created_variations_panel" class="panel woocommerce_options_panel">
    <div class="options_group">
        <p><?php _e('Hier siehst du die bereits erstellten Varianten.', 'teamshop-product-plugin'); ?></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Variante ID', 'teamshop-product-plugin'); ?></th>
                    <th><?php _e('Eigenschaft', 'teamshop-product-plugin'); ?></th>
                    <th><?php _e('Wert', 'teamshop-product-plugin'); ?></th>
                    <th><?php _e('Preis', 'teamshop-product-plugin'); ?></th>
                    <th><?php _e('Lagerbestand', 'teamshop-product-plugin'); ?></th>
                    <th><?php _e('Auf Lager', 'teamshop-product-plugin'); ?></th>
                    <th><?php _e('Aktionen', 'teamshop-product-plugin'); ?></th>
                </tr>
            </thead>
            <tbody id="teamshop-variations-list">
                <?php
               // $variations = $product->get_children(); // Lade die Varianten des Produkts
                if (!empty($variations)) {
                    foreach ($variations as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        ?>
                        <tr>
                            <td><?php echo esc_html($variation_id); ?></td>
                            <td><?php echo esc_html(get_post_meta($variation_id, 'attribute_name', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($variation_id, 'attribute_value', true)); ?></td>
                            <td><input type="text" class="teamshop-price" data-id="<?php echo esc_attr($variation->get_id()); ?>" value="<?php echo esc_attr($variation->get_price()); ?>" /></td>
<td><input type="number" class="teamshop-stock" data-id="<?php echo esc_attr($variation->get_id()); ?>" value="<?php echo esc_attr($variation->get_stock_quantity()); ?>" /></td>
<td>
    <select class="teamshop-stock-status" data-id="<?php echo esc_attr($variation->get_id()); ?>">
        <option value="instock" <?php selected($variation->get_stock_status(), 'instock'); ?>>Auf Lager</option>
        <option value="outofstock" <?php selected($variation->get_stock_status(), 'outofstock'); ?>>Nicht auf Lager</option>
    </select>
</td>

                            <td>
                                <button type="button" class="remove-variation" data-id="<?php echo esc_attr($variation_id); ?>">✕</button>
                            </td>
                        </tr>
                        <?php 
                    }
                } else {
                    echo '<tr><td colspan="7">' . __('Keine Varianten gefunden.', 'teamshop-product-plugin') . '</td></tr>';
                }
                ?>
            </tbody>
</table>
<button type="button" class="button button-primary" id="save-teamshop-variations">Änderungen speichern</button>
    </div>
</div>

            <?php 

        }

        public function save_teamshop_variations($post_id) {
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    if (!empty($_POST['teamshop_variation']) && is_array($_POST['teamshop_variation'])) {
        foreach ($_POST['teamshop_variation'] as $variation_id => $data) {
            if (get_post_type($variation_id) === 'product_variation') {
                update_post_meta($variation_id, 'attribute_name', isset($data['attribute']) ? sanitize_text_field($data['attribute']) : '');
                update_post_meta($variation_id, 'attribute_value', isset($data['value']) ? sanitize_text_field($data['value']) : '');
                update_post_meta($variation_id, '_price', isset($data['price']) ? wc_format_decimal($data['price']) : '');
                update_post_meta($variation_id, '_stock', isset($data['stock']) ? intval($data['stock']) : 0);
                update_post_meta($variation_id, '_stock_status', isset($data['stock_status']) ? sanitize_text_field($data['stock_status']) : 'instock');
				update_post_meta($variation_id, '_manage_stock', 'yes'); 
            }
        }
    }
}


public function ajax_add_variation() {
    check_ajax_referer('teamshop_nonce', 'security');

    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        wp_send_json_error(array('message' => __('Ungültige Produkt-ID.', 'teamshop-product-plugin')));
    }

    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);

    if (!$product || $product->get_type() !== 'teamshop_product') {
        wp_send_json_error(array('message' => __('Produkt nicht gefunden oder falscher Typ.', 'teamshop-product-plugin')));
    }

    // Neue Variante erstellen
    $variation = new WC_Product_Variation();
    $variation->set_parent_id($product_id);
    $variation->set_status('publish');
    $variation_id = $variation->save();

    if (!$variation_id) {
        wp_send_json_error(array('message' => __('Fehler beim Erstellen der Variante.', 'teamshop-product-plugin')));
    }

    // 🛠 Taxonomie-Verknüpfung sicherstellen
    if (!empty($_POST['taxonomy']) && !empty($_POST['term'])) {
        $term_id = intval($_POST['term']);
        teamshop_fix_variation_relationships($variation_id, sanitize_text_field($_POST['taxonomy']), $term_id);
    }


    if (!$variation_id) {
        wp_send_json_error(array('message' => __('Fehler beim Erstellen der Variante.', 'teamshop-product-plugin')));
    }

    // 🆕 Taxonomie & Term speichern
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    $term_id = isset($_POST['term']) ? intval($_POST['term']) : '';

    if ($taxonomy && $term_id) {
        // 💡 Term-Namen anhand der ID holen
        $term = get_term($term_id, $taxonomy);
        if (!is_wp_error($term) && $term) {
            $term_name = $term->name;
        } else {
            $term_name = '';
        }

        // 💡 Taxonomie-Name ohne "pa_" Prefix ermitteln
        $taxonomy_label = str_replace('pa_', '', $taxonomy);
        
        // 🔥 Fix für attribute_name & attribute_value
        update_post_meta($variation_id, 'attribute_name', $taxonomy_label); // "Damen"
        update_post_meta($variation_id, 'attribute_value', $term_name); // "32"

        // 🔥 Fix für Meta Key `attribute_pa_*`
        update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_name);
    } else {
        error_log("❌ Fehler: Taxonomie oder Term fehlen!");
    }
    // 🛠 Preis und regulären Preis speichern
    /*if (isset($_POST['price'])) {
    $price = wc_format_decimal($_POST['price']);
    update_post_meta($variation_id, '_price', $price);
    update_post_meta($variation_id, '_regular_price', $price);
    }*/

    // 🛠 Standardwerte für Stock-Management & Sichtbarkeit setzen
    update_post_meta($variation_id, '_manage_stock', 'yes'); // ✅ Auto-Stock aktivieren
    update_post_meta($variation_id, '_visibility', 'visible'); // ✅ Sichtbarkeit sicherstellen

    // 🛠 Sicherstellen, dass alle Attribute als Variation erkannt werden
    $attributes = get_post_meta($product_id, '_product_attributes', true);

    if (!empty($attributes) && is_array($attributes)) {
        foreach ($attributes as $key => &$attribute) { // 🔥 Referenz für direkte Änderung
            $attribute['is_variation'] = 1; // ✅ Markiert als Variante
            $attribute['is_visible'] = 1; // ✅ Sichtbar im Frontend
            $attribute['is_taxonomy'] = 1; // 🔥 WooCommerce benötigt diesen Key
            $attribute['checkout_visible'] = 1; // ✅ Sichtbar im Checkout
        }

        // 🔍 Debugging: Überprüfe, ob Werte gesetzt wurden
        error_log("🔍 Vor dem Speichern - Produkt-ID {$product_id}: " . print_r($attributes, true));

        // 🔄 Aktualisieren und WooCommerce Cache leeren
        update_post_meta($product_id, '_product_attributes', $attributes);
        wc_delete_product_transients($product_id);
        clean_post_cache($product_id); // 🔥 WooCommerce Cache für sicheres Laden leeren

        // 🔍 Debugging: Überprüfe, ob Speicherung erfolgreich war
        $updated_attributes = get_post_meta($product_id, '_product_attributes', true);
        error_log("✅ Nach dem Speichern - Produkt-ID {$product_id}: " . print_r($updated_attributes, true));
}




// Alle Attribute (Taxonomien) abrufen
$attributes = wc_get_attribute_taxonomies();

ob_start();

    ?>
    <tr>
    <!-- Dropdown für Eigenschaften (Taxonomien) -->
    <td>
        <select class="teamshop-attribute-select">
            <option value="">-- Eigenschaft wählen --</option>
            <?php foreach ($attributes as $attribute): ?>
                <option value="<?php echo esc_attr('pa_' . $attribute->attribute_name); ?>">
                    <?php echo esc_html($attribute->attribute_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- Dropdown für Werte (Terms) - bleibt leer, wird per AJAX nachgeladen -->
    <td>
        <select class="teamshop-term-select" disabled>
            <option value="">-- Erst Eigenschaft wählen --</option>
        </select>
    </td>


    <!-- Checkbox für "Auf Lager" -->
    <td>
        <select class="teamshop-stock-status" data-id="<?php echo esc_attr($variation_id); ?>">
            <option value="instock">Auf Lager</option>
            <option value="outofstock">Nicht auf Lager</option>
        </select>
    </td>

    <!-- Löschen-Button -->
    <td>
        <button type="button" class="remove-variation" data-id="<?php echo esc_attr($variation_id); ?>">✕</button>
    </td>
</tr>

    <?php
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}




		
		public function ajax_delete_variation() {
    check_ajax_referer('teamshop_nonce', 'security');

    if (!isset($_POST['variation_id']) || !is_numeric($_POST['variation_id'])) {
        wp_send_json_error(array('message' => __('Ungültige Varianten-ID.', 'teamshop-product-plugin')));
    }

    $variation_id = intval($_POST['variation_id']);

    if (wp_delete_post($variation_id, true)) {
        wp_send_json_success(array('message' => __('Variante gelöscht.', 'teamshop-product-plugin')));
    } else {
        wp_send_json_error(array('message' => __('Fehler beim Löschen der Variante.', 'teamshop-product-plugin')));
    }
}

		
		public function ajax_get_terms() {
    check_ajax_referer('teamshop_nonce', 'security');

    if (!isset($_POST['taxonomy'])) {
        wp_send_json_error(array('message' => __('Ungültige Taxonomie.', 'teamshop-product-plugin')));
    }

    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $terms = get_terms(array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ));

    if (is_wp_error($terms)) {
        wp_send_json_error(array('message' => __('Fehler beim Laden der Werte.', 'teamshop-product-plugin')));
    }

    $term_options = array();
    foreach ($terms as $term) {
        $term_options[] = array(
            'id'   => $term->term_id,
            'name' => $term->name
        );
    }

    wp_send_json_success($term_options);
}

public function ajax_save_teamshop_variations() {
    check_ajax_referer('teamshop_nonce', 'security');

    error_log("🔍 Eingehende AJAX-Daten:");
    error_log(print_r($_POST, true));

    if (!isset($_POST['variations']) || empty($_POST['variations'])) {
        error_log("❌ Fehler: Ungültige Daten empfangen!");
        wp_send_json_error(array('message' => __('Ungültige Daten.', 'teamshop-product-plugin')));
    }

    $raw_variations = wp_unslash($_POST['variations']);
    $variations = json_decode($raw_variations, true);

    if (!is_array($variations)) {
        error_log('❌ JSON-Decoding fehlgeschlagen: ' . print_r($raw_variations, true));
        wp_send_json_error(array('message' => __('JSON-Daten konnten nicht dekodiert werden.', 'teamshop-product-plugin')));
    }

    foreach ($variations as $variation) {
        $variation_id = intval($variation['id']);
        error_log("🔄 Speichere Variante ID: " . $variation_id);

        if (get_post_type($variation_id) !== 'product_variation') {
            error_log("❌ Fehler: Keine gültige Varianten-ID gefunden!");
            continue;
        }

        // 🛠 Aktualisiere Preis, regulären Preis, Lagerbestand & Status
        $price = wc_format_decimal($variation['price']);
        update_post_meta($variation_id, '_price', $price);
        update_post_meta($variation_id, '_regular_price', $price); // 🔥 Fix

        update_post_meta($variation_id, '_stock', intval($variation['stock']));
        update_post_meta($variation_id, '_stock_status', sanitize_text_field($variation['stock_status']));

        // 🔄 Cache leeren, damit Änderungen im Backend erscheinen
        wc_delete_product_transients($variation_id);
        clean_post_cache($variation_id);
    }

    error_log("✅ Alle Änderungen gespeichert!");
    wp_send_json_success();
}


public function ajax_update_variation_attributes() {
    check_ajax_referer('teamshop_nonce', 'security');

    if (!isset($_POST['variation_id'], $_POST['taxonomy'], $_POST['term'])) {
        wp_send_json_error(array('message' => __('Ungültige Anfrage.', 'teamshop-product-plugin')));
    }

    $variation_id = intval($_POST['variation_id']);
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $term_id = intval($_POST['term']);

    if (get_post_type($variation_id) !== 'product_variation') {
        wp_send_json_error(array('message' => __('Ungültige Varianten-ID.', 'teamshop-product-plugin')));
    }

    // 💡 Term-Namen anhand der ID holen
    $term = get_term($term_id, $taxonomy);
    if (!is_wp_error($term) && $term) {
        $term_name = $term->name;
    } else {
        wp_send_json_error(array('message' => __('Ungültiger Term.', 'teamshop-product-plugin')));
    }

    // 💡 Taxonomie-Label aus WooCommerce-Attributen holen
    $taxonomy_label = '';
    $attributes = wc_get_attribute_taxonomies();
    foreach ($attributes as $attribute) {
        if ('pa_' . $attribute->attribute_name === $taxonomy) {
            $taxonomy_label = $attribute->attribute_label;
            break;
        }
    }

    // 🔥 Attribute als Metadaten speichern
    update_post_meta($variation_id, 'attribute_name', $taxonomy_label);
    update_post_meta($variation_id, 'attribute_value', $term_name);
    update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_name);

    // ✅ WICHTIG: Variante mit der Taxonomie verknüpfen!
    wp_set_object_terms($variation_id, $term_id, $taxonomy);

    // 🛠 Sicherstellen, dass die Elternprodukt-Attribute korrekt gespeichert sind
    $product_id = wp_get_post_parent_id($variation_id);
    $product_attributes = get_post_meta($product_id, '_product_attributes', true);

    if (!empty($product_attributes) && is_array($product_attributes)) {
        if (isset($product_attributes[$taxonomy])) {
            $product_attributes[$taxonomy]['is_variation'] = 1; // ✅ Markiere als Variation
            $product_attributes[$taxonomy]['is_visible'] = 1;   // ✅ Sichtbar machen
            $product_attributes[$taxonomy]['is_taxonomy'] = 1;  // ✅ Markiere als Taxonomie
        }
        update_post_meta($product_id, '_product_attributes', $product_attributes);
    }

    // 🔄 WooCommerce Cache leeren, damit Änderungen sichtbar werden
    wc_delete_product_transients($product_id);
    clean_post_cache($product_id);

    wp_send_json_success(array('message' => __('Attribute gespeichert.', 'teamshop-product-plugin')));
}







    }

    if (!isset($GLOBALS['wc_teamshop_product_admin'])) {
    $GLOBALS['wc_teamshop_product_admin'] = new WC_Teamshop_Product_Admin();
}
}
