function sellerLogin() {
    const email = document.getElementById('seller_email').value;
    const password = document.getElementById('seller_password').value;

    fetch('ajaxhandler/sellerLoginAjax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'SUCCESS') {
            window.location.href = '/ecommerce_project/sellerDashboard.php';
        } else {
            document.getElementById('response').innerHTML = `<p style="color:red;">${data.message}</p>`;
        }
    });
}

function registerSeller() {
    const name = document.getElementById('new_name').value;
    const email = document.getElementById('new_email').value;
    const password = document.getElementById('new_password').value;
    const registration = document.getElementById('new_registration').value;

    if (!name || !email || !password || !registration) {
        const responseBox = document.getElementById('register-response');
        responseBox.style.display = 'block';
        responseBox.innerText = 'Please fill in all fields.';
        return;
    }

    fetch('ajaxhandler/sellerRegisterAjax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&registration=${encodeURIComponent(registration)}`
    })
    .then(response => response.json())
    .then(data => {
        const responseBox = document.getElementById('register-response');
        responseBox.style.display = 'block';
        responseBox.style.color = data.status === 'SUCCESS' ? 'green' : 'red';
        responseBox.innerText = data.message;
    })
    .catch(error => {
        const responseBox = document.getElementById('register-response');
        responseBox.style.display = 'block';
        responseBox.style.color = 'red';
        responseBox.innerText = 'Error: ' + error;
    });
}
