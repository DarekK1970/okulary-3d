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

const formatMoney = (cents, currency, locale) => {
    const browserLocale = locale === 'en' ? 'en-GB' : 'pl-PL';

    return `${(cents / 100).toLocaleString(browserLocale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${currency}`;
};

const initCheckoutMethods = () => {
    const form = document.querySelector('[data-checkout-form]');

    if (!form) {
        return;
    }

    const shippingOutput = form.querySelector('[data-checkout-shipping]');
    const totalOutput = form.querySelector('[data-checkout-total]');
    const pointWrap = form.querySelector('[data-shipping-point-wrap]');
    const pointInput = pointWrap?.querySelector(
        'input[name="shipping_point"]'
    );

    const subtotal = Number(form.dataset.subtotalCents || 0);
    const currency = form.dataset.currency || 'PLN';
    const locale = form.dataset.locale || 'pl';

    const update = () => {
        const selected = form.querySelector(
            'input[name="shipping_method"]:checked'
        );

        const shippingCents = Number(
            selected?.dataset.priceCents || 0
        );

        const requiresPoint =
            selected?.dataset.requiresPoint === '1';

        if (shippingOutput) {
            shippingOutput.textContent = formatMoney(
                shippingCents,
                currency,
                locale
            );
        }

        if (totalOutput) {
            totalOutput.textContent = formatMoney(
                subtotal + shippingCents,
                currency,
                locale
            );
        }

        if (pointWrap) {
            pointWrap.classList.toggle(
                'is-hidden',
                !requiresPoint
            );
        }

        if (pointInput) {
            pointInput.required = requiresPoint;
        }
    };

    form.querySelectorAll(
        'input[name="shipping_method"]'
    ).forEach((input) => {
        input.addEventListener('change', update);
    });

    update();
};

const initCart = () => {
    initShippingAddress();
    initCheckoutMethods();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCart);
} else {
    initCart();
}
