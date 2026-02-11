document.addEventListener('DOMContentLoaded', () => {
    fetchProducts();

    // Sehemu ya Search
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            fetchProducts(e.target.value);
        });
    }
});

async function fetchProducts(query = '') {
    // Tunasafiri kwenda kwenye API yetu
    const url = `../../backend/api/products/get_products.php?search=${query}`;
    
    try {
        const response = await fetch(url);
        const products = await response.json();
        
        const container = document.getElementById('product-list');
        if (!container) return;

        if (products.length === 0) {
            container.innerHTML = '<p style="text-align:center; width:100%;">Hakuna bidhaa iliyopatikana.</p>';
            return;
        }

        // Tunatengeneza kadi za bidhaa
        container.innerHTML = products.map(p => `
            <div class="card">
                <h3>${p.name}</h3>
                <p>${p.description}</p>
                <span class="price">$${p.price}</span>
                <button onclick="addToCart(${p.id})">Add to Cart</button>
            </div>
        `).join(''); // .join('') ni muhimu sana hapa!

    } catch (err) {
        console.error("Tatizo la kupata bidhaa:", err);
    }
}