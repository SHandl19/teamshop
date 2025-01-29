jQuery(document).ready(function ($) {
    let selectedVariationId = null;
    let selectedAttributes = {};

    $('.teamshop-swatch').on('click', function () {
        selectedVariationId = $(this).data('variation-id');
        let attributeName = $(this).closest('.teamshop-attribute').data('attribute');
        let attributeValue = $(this).data('term');

        if (!selectedVariationId) {
            console.error("⚠️ Keine gültige Varianten-ID gefunden.");
            return;
        }

        // ✨ Nur das aktuelle Attribut speichern, vorherige löschen
        selectedAttributes = {}; // Zurücksetzen!
        selectedAttributes[attributeName] = attributeValue;

        $('.teamshop-swatch').removeClass('active');
        $(this).addClass('active');

        console.log("✅ Gewählte Variante ID:", selectedVariationId);
        console.log("✅ Gesendete Attribute:", selectedAttributes);

        $('#teamshop-add-to-cart').prop('disabled', false);
        $('#teamshop-add-to-cart').attr('data-variation-id', selectedVariationId);
    });

    $('#teamshop-add-to-cart').on('click', function () {
        let variationIdToAdd = $(this).attr('data-variation-id');
        let quantity = $('#teamshop-quantity').val();

        if (!variationIdToAdd) {
            alert('❌ Bitte zuerst eine Variante auswählen.');
            return;
        }

        console.log("📦 Füge Variante in den Warenkorb: ", variationIdToAdd, " Menge: ", quantity);
        console.log("📌 Gesendete Attribute:", selectedAttributes);

        $.ajax({
            url: teamshop_ajax.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'teamshop_add_to_cart_callback',
                product_id: variationIdToAdd,
                quantity: quantity,
                selected_attributes: selectedAttributes // ✅ Nur das letzte Attribut wird gesendet
            },
            success: function (response) {
                if (response.success) {
                    alert('✅ Produkt wurde in den Warenkorb gelegt!');
                    location.reload();
                } else {
                    alert('⚠️ Fehler: ' + response.data.message);
                }
            },
            error: function () {
                alert('❌ Fehler beim Hinzufügen zum Warenkorb.');
            }
        });
    });
});
