// Kazi ya kusajili user (Register)
async function registerUser(userData) {
    try {
        const response = await fetch('backend/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        const result = await response.json();
        alert(result.message);
    } catch (error) {
        console.error('Error:', error);
    }
}

// Kazi ya kuingia (Login)
async function loginUser(email, password) {
    // Hapa utatumia Fetch API kuunganisha na login.php
    console.log("Jaribio la login kwa:", email);
}