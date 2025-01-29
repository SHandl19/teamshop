jQuery(document).ready(function ($) {
    console.log("JS für Variantenverwaltung geladen!");

    // Taxonomie ändern → Werte (Terms) nachladen
$(document).on('change', '.teamshop-attribute-select', function () {
    var taxonomy = $(this).val();
    var termSelect = $(this).closest('tr').find('.teamshop-term-select');

    console.log("📌 Gewählte Taxonomie:", taxonomy);

    if (!taxonomy) {
        termSelect.prop('disabled', true).html('<option value="">-- Erst Eigenschaft wählen --</option>');
        return;
    }

    $.post(teamshop_ajax.ajax_url, {
        action: 'get_teamshop_terms',
        security: teamshop_ajax.security,
        taxonomy: taxonomy
    }, function (response) {
        if (response.success) {
            var options = '<option value="">-- Wert wählen --</option>';
            response.data.forEach(function (term) {
                options += '<option value="' + term.id + '">' + term.name + '</option>';
            });
            termSelect.html(options).prop('disabled', false);
        } else {
            alert(response.data.message);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("⚠️ AJAX-Fehler:", textStatus, errorThrown);
        alert("Fehler beim Laden der Werte: " + textStatus);
    });
});

// Speichert Taxonomie + Term erst, wenn beide ausgewählt sind!
$(document).on('change', '.teamshop-term-select', function () {
    var row = $(this).closest('tr');
    var taxonomy = row.find('.teamshop-attribute-select').val();
    var term = $(this).val();
    var variationId = row.find('.remove-variation').data('id');

    console.log("📌 Speichere Taxonomie:", taxonomy, " | Term:", term, " für Variante:", variationId);

    if (!taxonomy || !term) {
        console.warn("⚠️ Keine Taxonomie oder kein Term gewählt.");
        return;
    }

    var data = {
        action: 'update_teamshop_variation_attributes',
        security: teamshop_ajax.security,
        variation_id: variationId,
        taxonomy: taxonomy,
        term: term
    };

    $.post(teamshop_ajax.ajax_url, data, function (response) {
        if (response.success) {
            console.log("✅ Erfolgreich gespeichert:", response);
        } else {
            console.error("❌ Fehler beim Speichern:", response.data.message);
            alert("Fehler beim Speichern: " + response.data.message);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("⚠️ AJAX-Fehler:", textStatus, errorThrown);
        alert("Fehler beim Serveraufruf: " + textStatus);
    });
});


    // Variante hinzufügen
    $(document).on('click', '#add-teamshop-variation', function () {
        console.log("🚀 Variante hinzufügen Button geklickt!");

        var data = {
            action: 'add_teamshop_variation',
            product_id: woocommerce_admin_meta_boxes ? woocommerce_admin_meta_boxes.post_id : 0,
            security: teamshop_ajax.security
        };

        console.log("📡 AJAX-Daten vor dem Absenden:", data);

        $.post(teamshop_ajax.ajax_url, data, function (response) {
            console.log("✅ AJAX Antwort erhalten:", response);

            if (response.success) {
                console.log("🆕 Neue Variante wird zur Tabelle hinzugefügt.");
                $('#teamshop-add-variations-list').append(response.data.html);

                // **🐛 FIX: Warte kurz, dann erst Taxonomie & Term auslesen**
                setTimeout(function () {
                    var lastRow = $('#teamshop-add-variations-list tr:last');
                    var taxonomy = lastRow.find('.teamshop-attribute-select').val();
                    var term = lastRow.find('.teamshop-term-select').val();

                    console.log("🔍 Nach Wartezeit - Gefundene Taxonomie:", taxonomy);
                    console.log("🔍 Nach Wartezeit - Gefundener Term:", term);

                    if (!taxonomy || !term) {
                        console.warn("⚠️ Keine Taxonomie oder kein Term gewählt.");
                    }
                }, 500); // **Wartezeit, damit die Zeile sicher existiert**
            } else {
                console.error("❌ Fehler beim Hinzufügen der Variante:", response.data.message);
                alert(response.data.message);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error("⚠️ AJAX-Fehler:", textStatus, errorThrown);
            alert("Fehler beim Serveraufruf: " + textStatus);
        });
    });
    


    // Variante löschen
    $(document).on('click', '.remove-variation', function () {
        var row = $(this).closest('tr');
        var variation_id = row.find('td:first').text();

        var data = {
            action: 'delete_teamshop_variation',
            variation_id: variation_id,
            security: teamshop_ajax.security
        };

        console.log("AJAX-Daten für Löschung:", data);

        $.post(teamshop_ajax.ajax_url, data, function (response) {
            console.log("Antwort für Löschung:", response);
            if (response.success) {
                row.remove();
            } else {
                alert(response.data.message);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX-Fehler bei Löschung:", textStatus, errorThrown);
            alert("Fehler beim Serveraufruf: " + textStatus);
        });
    });

}); // ✅ WICHTIG: Hier wurde die fehlende Klammer eingefügt!

jQuery(document).ready(function ($) {
    $('#save-teamshop-variations').on('click', function () {
    var variations = []; // 🔥 Sicherstellen, dass variations existiert!

        $('#teamshop-variations-list tr').each(function () {
            var row = $(this);
            var variation_id = row.find('.remove-variation').data('id');

            if (variation_id) {
                variations.push({
                    id: variation_id,
                    price: row.find('.teamshop-price').val() || '0',
                    stock: row.find('.teamshop-stock').val() || '0',
                    stock_status: row.find('.teamshop-stock-status').val() || 'instock'
                });
            }
        });
		
    console.log("📤 Vor dem Absenden - Variations Array:", variations); // 🔥 Debugging


        if (variations.length === 0) {
            alert("Keine Änderungen gefunden!");
            return;
        }

        var data = {
            action: 'teamshop_save_variations',
            security: teamshop_ajax.security,
            variations: JSON.stringify(variations) // 🔥 Fix: Daten als JSON senden
        };
		
		 console.log("📡 AJAX-Daten:", data); // 🔥 Debugging: Zeigt, was gesendet wird

        $.post(teamshop_ajax.ajax_url, data, function (response) {
			console.log("✅ Server-Antwort:", response);
            if (response.success) {
                alert("Änderungen gespeichert!");
                location.reload();
            } else {
                alert("Fehler beim Speichern: " + response.data.message);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX-Fehler:", textStatus, errorThrown);
            alert("Fehler beim Serveraufruf: " + textStatus);
        });
    });
});

