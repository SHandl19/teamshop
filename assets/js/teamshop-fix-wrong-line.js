document.addEventListener("DOMContentLoaded", function () {
    // Alle <li>-Elemente mit der Klasse "wc-block-components-product-details__kinder" auswählen
    const kinderElements = document.querySelectorAll("li.wc-block-components-product-details__kinder");

    if (kinderElements.length > 1) {
        // Die erste Zeile als "richtige" behalten, den anderen eine Extra-Klasse geben
        kinderElements.forEach((el, index) => {
            if (index > 0) {
                el.classList.add("teamshop-falsche-zeile");
            }
        });
    }
});
