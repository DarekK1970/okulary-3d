const formatMoney = (
    cents,
    currency,
    locale
) => {
    const browserLocale =
        locale === 'en'
            ? 'en-GB'
            : 'pl-PL';

    return `${(cents / 100).toLocaleString(
        browserLocale,
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )} ${currency}`;
};

const initCheckout = () => {
    const form = document.querySelector(
        '[data-checkout-form]'
    );

    if (!form) {
        return;
    }

    const sameAddress = form.querySelector(
        'input[name="shipping_same_as_billing"][type="checkbox"]'
    );
    const shippingFields = form.querySelector(
        '.checkout-shipping-fields'
    );
    const billingCountry = form.querySelector(
        'input[name="billing_country_code"]'
    );
    const shippingCountry = form.querySelector(
        '[data-shipping-country]'
    );
    const methodsWrap = form.querySelector(
        '[data-shipping-methods]'
    );
    const status = form.querySelector(
        '[data-shipping-status]'
    );
    const shippingOutput = form.querySelector(
        '[data-checkout-shipping]'
    );
    const totalOutput = form.querySelector(
        '[data-checkout-total]'
    );
    const pointWrap = form.querySelector(
        '[data-shipping-point-wrap]'
    );
    const pointInput = pointWrap?.querySelector(
        'input[name="shipping_point"]'
    );
    const submitButton = form.querySelector(
        '[data-place-order]'
    );

    const subtotal = Number(
        form.dataset.subtotalCents || 0
    );
    const currency =
        form.dataset.currency || 'PLN';
    const locale =
        form.dataset.locale || 'pl';
    const quoteUrl =
        form.dataset.shippingOptionsUrl;
    const loadingLabel =
        form.dataset.loadingLabel || 'Loading…';
    const noMethodsLabel =
        form.dataset.noMethodsLabel
        || 'No shipping methods.';
    const errorLabel =
        form.dataset.errorLabel
        || 'Unable to calculate shipping.';

    const mapEnabled =
        form.dataset.furgonetkaMapEnabled === '1';
    const mapApiKey =
        form.dataset.furgonetkaMapApiKey || '';
    const mapNotReadyLabel =
        form.dataset.furgonetkaMapNotReady || 'Map is not ready.';
    const mapSelectedLabel =
        form.dataset.furgonetkaMapSelected || 'Selected point';

    const mapButton = form.querySelector('[data-furgonetka-map-button]');
    const pointCodeInput = form.querySelector('[data-shipping-point-code]');
    const pointNameInput = form.querySelector('[data-shipping-point-name]');
    const pointTypeInput = form.querySelector('[data-shipping-point-type]');
    const pointOriginalIdInput = form.querySelector('[data-shipping-point-original-id]');
    const pointCountryInput = form.querySelector('[data-shipping-point-country]');
    const pointSummary = form.querySelector('[data-furgonetka-map-summary]');

    const syncAddressVisibility = () => {
        if (
            !sameAddress
            || !shippingFields
        ) {
            return;
        }

        shippingFields.classList.toggle(
            'is-hidden',
            sameAddress.checked
        );

        if (
            sameAddress.checked
            && billingCountry
            && shippingCountry
        ) {
            billingCountry.value =
                shippingCountry.value;
        }
    };

    const selectedMethod = () =>
        form.querySelector(
            'input[name="shipping_method"]:checked'
        );

    const updateTotals = () => {
        const selected =
            selectedMethod();

        const shippingCents = Number(
            selected?.dataset.priceCents || 0
        );

        const requiresPoint =
            selected?.dataset
                .requiresPoint === '1';

        if (shippingOutput) {
            shippingOutput.textContent =
                selected
                    ? formatMoney(
                        shippingCents,
                        currency,
                        locale
                    )
                    : '—';
        }

        if (totalOutput) {
            totalOutput.textContent =
                formatMoney(
                    subtotal
                    + (
                        selected
                            ? shippingCents
                            : 0
                    ),
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
            pointInput.required =
                requiresPoint;

            if (!requiresPoint) {
                pointInput.value = '';
                if (pointNameInput) pointNameInput.value = '';
                if (pointTypeInput) pointTypeInput.value = '';
                if (pointOriginalIdInput) pointOriginalIdInput.value = '';
                if (pointCountryInput) pointCountryInput.value = '';
                if (pointSummary) pointSummary.textContent = '';
            }
        }

        if (mapButton) {
            mapButton.hidden = !requiresPoint;
        }

        if (submitButton) {
            submitButton.disabled =
                !selected;
        }
    };

    const makeMethod = (
        method,
        checked = false
    ) => {
        const label =
            document.createElement('label');
        label.className =
            'checkout-method';

        const input =
            document.createElement('input');
        input.type = 'radio';
        input.name = 'shipping_method';
        input.value = method.key;
        input.required = true;
        input.dataset.shippingMethod = '';
        input.dataset.priceCents =
            String(method.price_cents);
        input.dataset.requiresPoint =
            method.requires_point ? '1' : '0';
        input.checked = checked;

        const copy =
            document.createElement('span');
        const name =
            document.createElement('strong');
        name.textContent = method.name;

        const price =
            document.createElement('small');
        price.textContent =
            method.formatted_price;

        copy.append(name, price);
        label.append(input, copy);

        return label;
    };

    const showStatus = (
        message,
        isError = false
    ) => {
        if (!status) {
            return;
        }

        status.hidden = false;
        status.textContent = message;
        status.classList.toggle(
            'is-error',
            isError
        );
    };

    const hideStatus = () => {
        if (!status) {
            return;
        }

        status.hidden = true;
        status.textContent = '';
        status.classList.remove(
            'is-error'
        );
    };

    const loadMethods = async () => {
        if (
            !shippingCountry
            || !methodsWrap
            || !quoteUrl
        ) {
            return;
        }

        showStatus(loadingLabel);
        methodsWrap.classList.add(
            'is-loading'
        );
        methodsWrap.replaceChildren();

        if (submitButton) {
            submitButton.disabled = true;
        }

        if (
            sameAddress?.checked
            && billingCountry
        ) {
            billingCountry.value =
                shippingCountry.value;
        }

        try {
            const url = new URL(
                quoteUrl,
                window.location.origin
            );

            url.searchParams.set(
                'country',
                shippingCountry.value
            );

            const response = await fetch(
                url.toString(),
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}`
                );
            }

            const payload =
                await response.json();

            const methods =
                Array.isArray(payload.methods)
                    ? payload.methods
                    : [];

            if (methods.length === 0) {
                showStatus(
                    noMethodsLabel,
                    true
                );
                updateTotals();
                return;
            }

            methods.forEach(
                (method, index) => {
                    methodsWrap.append(
                        makeMethod(
                            method,
                            index === 0
                        )
                    );
                }
            );

            hideStatus();
            updateTotals();
        } catch (error) {
            console.error(
                'Shipping quote failed.',
                error
            );

            showStatus(
                errorLabel,
                true
            );

            updateTotals();
        } finally {
            methodsWrap.classList.remove(
                'is-loading'
            );
        }
    };

    const clearPickupPoint = () => {
        if (pointCodeInput) pointCodeInput.value = '';
        if (pointNameInput) pointNameInput.value = '';
        if (pointTypeInput) pointTypeInput.value = '';
        if (pointOriginalIdInput) pointOriginalIdInput.value = '';
        if (pointCountryInput) pointCountryInput.value = '';
        if (pointSummary) pointSummary.textContent = '';
    };

    const openPickupMap = () => {
        if (!mapEnabled || !mapApiKey || !shippingCountry) {
            return;
        }

        if (!window.Furgonetka) {
            window.alert(mapNotReadyLabel);
            return;
        }

        new window.Furgonetka.Map({
            apiKey: mapApiKey,
            countryCodesFilter: [shippingCountry.value],
            locale,
            callback: (params) => {
                const point = params?.point;
                if (!point?.code) return;

                if (pointCodeInput) pointCodeInput.value = point.code;
                if (pointNameInput) pointNameInput.value = point.name || '';
                if (pointTypeInput) pointTypeInput.value = point.type || '';
                if (pointOriginalIdInput) {
                    pointOriginalIdInput.value = point.original_point_id || '';
                }
                if (pointCountryInput) {
                    pointCountryInput.value =
                        point.country_code || shippingCountry.value;
                }
                if (pointSummary) {
                    pointSummary.textContent =
                        `${mapSelectedLabel}: ${point.name || point.code}`;
                }
            },
        }).show();
    };

    mapButton?.addEventListener('click', openPickupMap);
    shippingCountry?.addEventListener('change', clearPickupPoint);

    sameAddress?.addEventListener(
        'change',
        syncAddressVisibility
    );

    shippingCountry?.addEventListener(
        'change',
        loadMethods
    );

    methodsWrap?.addEventListener(
        'change',
        (event) => {
            if (
                event.target.matches(
                    'input[name="shipping_method"]'
                )
            ) {
                updateTotals();
            }
        }
    );

    syncAddressVisibility();
    updateTotals();
};

if (
    document.readyState
    === 'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initCheckout
    );
} else {
    initCheckout();
}
