document.addEventListener('DOMContentLoaded', () => {
    console.log("Electronics Store App Imeanza...");
    
    // Kama upo kwenye page ya bidhaa, pakia bidhaa
    if (document.getElementById('product-list')) {
        loadProducts();
    }
});