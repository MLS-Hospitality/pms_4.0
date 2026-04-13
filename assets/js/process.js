document.addEventListener('DOMContentLoaded', function () {
    var paymentSelect = document.getElementsByName('pmethod')[0];
    if (!paymentSelect) return;

    // Reference the form once
    const paymentForm = paymentSelect.closest('form');

    // Create spinner
    var spinner = document.createElement('div');
    spinner.id = 'payment-spinner';
    spinner.style.position = 'fixed';
    spinner.style.top = '50%';
    spinner.style.left = '50%';
    spinner.style.transform = 'translate(-50%, -50%)';
    spinner.style.zIndex = '9999';
    spinner.style.display = 'none';
    spinner.innerHTML = `
        <div style="
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        "></div>`;
    document.body.appendChild(spinner);

    // Spinner animation
    var style = document.createElement('style');
    style.innerHTML = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    const accountInfo = document.getElementById('account_info');
    const submitBtn = document.getElementById('disablemode');

    paymentSelect.onchange = function () {
        var payment_type = parseInt(this.value, 10);
        spinner.style.display = 'block';

        if (accountInfo) accountInfo.innerHTML = '';

        const formData = new FormData();
        formData.append(window.CSRF.name, window.CSRF.hash);
        formData.append('payment_type', payment_type);

        /* ===================== CARD PAYMENT ===================== */
        if (payment_type === 1) {
            submitBtn.disabled = true;

            fetch('payment/initiate', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';

                if (data.status === true) {
                    const popup = new PaystackPop();
                    const access_code = data.data.access_code;

                    popup.resumeTransaction(access_code, {
                        onSuccess(response) {
                            // ✅ SUBMIT FORM AFTER SUCCESSFUL PAYMENT
                            const refInput = document.createElement('input');
                            refInput.type = 'hidden';
                            refInput.name = 'payment_reference';
                            refInput.value = response.reference;
                            paymentForm.appendChild(refInput);

                            paymentForm.submit();
                        },
                        onCancel() {
                            alert('Transaction was canceled.');
                            submitBtn.disabled = true;
                        },
                        onLoad() {
                            console.log('Transaction loading...');
                        },
                        onError(error) {
                            alert('An error occurred: ' + error.message);
                            submitBtn.disabled = true;
                        }
                    });
                } else {
                    alert(data.message || 'Payment initiation failed');
                    submitBtn.disabled = false;
                }
            })
            .catch(err => {
                spinner.style.display = 'none';
                submitBtn.disabled = false;
                console.error('FETCH ERROR:', err);
            });

        /* ===================== BANK TRANSFER ===================== */
        } else if (payment_type === 4) {
            submitBtn.disabled = false;

            fetch('payment/account/data', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';

                if (!accountInfo) return;

                accountInfo.innerHTML = `
                    <div class="card p-3">
                        <h5 class="mb-3">Bank Transfer Details</h5>
                        <p><strong>Bank Name:</strong> ${data.bank_name}</p>
                        <p><strong>Account Name:</strong> ${data.bank_account_name}</p>
                        <p><strong>Account Number:</strong> ${data.bank_account_number}</p>
                        <p><strong>Amount:</strong> ${data.bank_account_charge}</p>
                        <p>
                            <strong>
                                Kindly click <b>Submit</b> after completing the transfer
                                and present proof of payment during check-in.
                            </strong>
                        </p>
                    </div>
                `;
            })
            .catch(err => {
                spinner.style.display = 'none';
                console.error('FETCH ERROR:', err);
            });

        /* ===================== CASH PAYMENT ===================== */
        } else {
            spinner.style.display = 'none';
            submitBtn.disabled = false;

            if (!accountInfo) return;

            accountInfo.innerHTML = `
                <div class="card p-3">
                    <h5 class="mb-3">Cash Payment Notice</h5>
                    <p>
                        This reservation is marked for <strong>cash payment</strong>.
                        Full settlement is required <strong>at the hotel front desk</strong>
                        before room allocation.
                    </p>
                    <p>
                        Check-in and room access will only be granted
                        after payment verification.
                    </p>
                </div>
            `;
        }
    };
});
