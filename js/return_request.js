document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.returnForm');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const orderItemId = this.querySelector('input[name="order_item_id"]').value;
            const returnReason = this.querySelector('textarea[name="return_reason"]').value;
            const messageBox = this.querySelector('.messageBox');

            fetch('ajaxhandler/handle_return_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_item_id: orderItemId,
                    return_reason: returnReason
                })
            })
            .then(res => res.json())
            .then(data => {
                messageBox.textContent = data.message;
                messageBox.classList.remove('text-danger', 'text-success');
                if (data.success) {
                    messageBox.classList.add('text-success');
                } else {
                    messageBox.classList.add('text-danger');
                }
            })
            .catch(err => {
                messageBox.textContent = 'Error submitting return request.';
                messageBox.classList.add('text-danger');
            });
        });
    });
});
