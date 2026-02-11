async function loadProducts() {
    try {
        const response = await fetch('backend/api/get_products.php');
        const products = await response.json();
        const container = document.getElementById('product-list');
        
        container.innerHTML = products.map(p => `
            <div class="product-card">
                <h3>${p.name}</h3>
                <p>Price: TSh ${p.price}</p>
                <button onclick="addToCart(${p.product_id})">Add to Cart</button>
            </div>
        `).join('');
    } catch (error) {
        console.error('Haikuweza kupakia bidhaa:', error);
    }
}