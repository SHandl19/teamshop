<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsprüfung
}

// 🔹 Menüpunkt UNTER "Produkte" hinzufügen
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=product', // Untermenü von "Produkte"
        'Teamshop Varianten aktualisieren',
        'Teamshop Varianten aktualisieren',
        'manage_woocommerce',
        'teamshop_update_variation_titles',
        'teamshop_variation_update_page'
    );
});

// 🔘 Admin-Seite mit Button
function teamshop_variation_update_page() {
    ?>
    <div class="wrap">
        <h1>Teamshop Varianten aktualisieren</h1>
        <p>Hier kannst du alle Varianten von Produkten mit <strong>_product_type = team_product</strong> aktualisieren.</p>
        <button id="update-teamshop-variations" class="button button-primary">Varianten aktualisieren</button>
        <div id="teamshop-update-status"></div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#update-teamshop-variations').on('click', function () {
                    var button = $(this);
                    button.prop('disabled', true).text('Aktualisierung läuft...');
                    $('#teamshop-update-status').html('<p><strong>Bitte warten...</strong></p>');

                    $.post(ajaxurl, { action: 'teamshop_update_all_variations' }, function (response) {
                        if (response.success) {
                            $('#teamshop-update-status').html('<p><strong>✅ Erfolgreich:</strong> ' + response.data.message + '</p>');
                        } else {
                            $('#teamshop-update-status').html('<p><strong>⚠️ Fehler:</strong> ' + response.data.message + '</p>');
                        }
                        button.prop('disabled', false).text('Varianten aktualisieren');
                    });
                });
            });
        </script>
    </div>
    <?php
}

// 🛠 AJAX-Funktion zur Aktualisierung der Varianten
add_action('wp_ajax_teamshop_update_all_variations', 'teamshop_update_all_variations');

function teamshop_update_all_variations() {
    global $wpdb;

    // 🔍 Elternprodukte mit `_product_type = team_product` finden
    $parent_products = $wpdb->get_col("
        SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = '_product_type'
        AND meta_value = 'team_product'
    ");

    if (empty($parent_products)) {
        wp_send_json_error(['message' => '❌ Keine passenden Produkte gefunden.']);
    }

    $updated_count = 0;

    // 🔄 Durch alle Elternprodukte iterieren
    foreach ($parent_products as $parent_id) {
        $variations = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_parent = $parent_id 
            AND post_type = 'product_variation'
        ");

        if (empty($variations)) {
            continue;
        }

        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }

            // 🎯 Taxonomie und Term aus den Meta-Daten abrufen
            $attribute_meta = get_post_meta($variation_id, 'attribute_name', true);
            $term_meta = get_post_meta($variation_id, 'attribute_value', true);

            if (!$attribute_meta || !$term_meta) {
                continue;
            }

            $new_title = get_the_title($parent_id) . ' - ' . $term_meta;
            $new_excerpt = wc_attribute_label($attribute_meta) . ': ' . $term_meta;

            // 🛠 Titel & Excerpt speichern
            wp_update_post([
                'ID'         => $variation_id,
                'post_title' => $new_title,
                'post_excerpt' => $new_excerpt,
            ]);

            $updated_count++;
        }
    }

    wp_send_json_success(['message' => "$updated_count Varianten aktualisiert."]);
}
