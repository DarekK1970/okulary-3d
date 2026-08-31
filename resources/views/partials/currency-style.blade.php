<style>
/* K86.4C — storefront currency selector */

.currency-switcher {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.currency-switcher select {
    min-width: 72px;
    height: 34px;
    padding: 0 27px 0 10px;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background:
        linear-gradient(45deg, transparent 50%, #7d8899 50%)
            calc(100% - 13px) 14px / 5px 5px no-repeat,
        linear-gradient(135deg, #7d8899 50%, transparent 50%)
            calc(100% - 9px) 14px / 5px 5px no-repeat,
        #f8fafc;
    color: #4d5a6d;
    font-size: .72rem;
    font-weight: 800;
    line-height: 1;
    appearance: none;
    cursor: pointer;
}

.currency-switcher select:hover,
.currency-switcher select:focus {
    border-color: #b7dce8;
    outline: none;
    color: var(--color-cyan-dark);
}

.mobile-currency-switcher {
    display: block;
    margin-top: 10px;
}

.mobile-currency-switcher select {
    width: 100%;
    min-height: 42px;
    padding: 0 12px;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    background: #f8fafc;
    color: #405067;
    font-size: .76rem;
    font-weight: 800;
}

.currency-switcher noscript button,
.mobile-currency-switcher noscript button {
    margin-top: 6px;
}

.shop-currency-note {
    margin-top: 8px;
    color: #8b96a8;
    font-size: .55rem;
    line-height: 1.45;
}

.product-currency-note {
    margin: 15px 0 0;
    padding: 10px 12px;
    border: 1px solid #deebf0;
    border-radius: 9px;
    background: #f7fbfd;
    color: #718094;
    font-size: .57rem;
    line-height: 1.5;
}

.product-currency-note strong {
    color: #3f566b;
}

@media (max-width: 820px) {
    .currency-switcher {
        display: none;
    }
}

</style>
