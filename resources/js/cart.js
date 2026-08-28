const initShippingAddress = () => {
    const checkbox = document.querySelector(
        'input[name="shipping_same_as_billing"][type="checkbox"]'
    );
    const fields = document.querySelector('.checkout-shipping-fields');

    if (!checkbox || !fields) {
        return;
    }

    const update = () => {
        fields.classList.toggle('is-hidden', checkbox.checked);
    };

    checkbox.addEventListener('change', update);
    update();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initShippingAddress);
} else {
    initShippingAddress();
}
