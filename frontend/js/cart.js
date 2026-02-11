let cart = [];

function addToCart(productId) {
    cart.push(productId);
    updateCartUI();
    console.log("Item imeongezwa kwenye kapu:", productId);
}

function updateCartUI() {
    const cartCount = document.getElementById('cart-count');
    if(cartCount) cartCount.innerText = cart.length;
}