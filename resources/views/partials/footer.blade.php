@php
    $locale = app()->getLocale();
@endphp

<footer class="site-footer" id="about">
    <section class="newsletter-strip" id="newsletter" aria-label="{{ __('site.newsletter.title') }}">
        <div class="site-container newsletter-inner">
            <div class="newsletter-copy">
                <span class="newsletter-icon" aria-hidden="true">✦</span>
                <div>
                    <h2>{{ __('site.newsletter.title') }}</h2>
                    <p>{{ __('site.newsletter.description') }}</p>
                </div>
            </div>

            <div class="newsletter-form-wrap">
                @if (session('newsletter_status'))
                    <div class="newsletter-message" role="status">
                        {{ session('newsletter_status') }}
                    </div>
                @endif

                <form
                    class="newsletter-form"
                    action="{{ route('newsletter.subscribe', ['locale' => $locale]) }}"
                    method="post"
                >
                    @csrf

                    <label class="sr-only" for="newsletter-email">
                        {{ __('site.newsletter.email_label') }}
                    </label>
                    <input
                        id="newsletter-email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="{{ __('site.newsletter.email_placeholder') }}"
                        autocomplete="email"
                        required
                    >
                    <button type="submit">{{ __('site.newsletter.submit') }}</button>

                    <label class="newsletter-consent">
                        <input
                            type="checkbox"
                            name="consent"
                            value="1"
                            required
                        >
                        <span>{{ __('newsletter.public.consent') }}</span>
                    </label>

                    @error('email')
                        <small class="newsletter-error">{{ $message }}</small>
                    @enderror
                    @error('consent')
                        <small class="newsletter-error">{{ $message }}</small>
                    @enderror
                </form>
            </div>
        </div>
    </section>

    <div class="site-container footer-grid">
        <div class="footer-brand">
            <img
                src="{{ asset('images/logo-okulary-3d.svg') }}"
                alt="{{ __('site.brand_logo_alt') }}"
                width="170"
                height="42"
            >
            <p>{{ __('site.footer.description') }}</p>
        </div>

        <div class="footer-column">
            <h3>{{ __('site.footer.portal') }}</h3>
            <a href="{{ route('home', ['locale' => $locale]) }}#articles">{{ __('site.nav.articles') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#history">{{ __('site.nav.history') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#techniques">{{ __('site.nav.techniques') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#lab">{{ __('site.nav.lab') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#gallery">{{ __('site.nav.gallery') }}</a>
        </div>

        <div class="footer-column">
            <h3>{{ __('site.footer.shop') }}</h3>
            <a href="{{ route('home', ['locale' => $locale]) }}#shop">{{ __('site.footer.glasses') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#shop">{{ __('site.footer.lenticular') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#shop">{{ __('site.footer.stereoscopes') }}</a>
            <a href="{{ route('home', ['locale' => $locale]) }}#shop">{{ __('site.footer.cameras') }}</a>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'shop-terms']) }}">{{ __('static_pages.footer.shop_terms') }}</a>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'secure-payments']) }}">{{ __('static_pages.footer.secure_payments') }}</a>
        </div>

        <div class="footer-column">
            <h3>{{ __('site.footer.support') }}</h3>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'faq']) }}">{{ __('site.footer.faq') }}</a>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'shipping-payments']) }}">{{ __('site.footer.shipping') }}</a>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'returns-complaints']) }}">{{ __('site.footer.returns') }}</a>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'privacy-policy']) }}">{{ __('site.footer.privacy') }}</a>
            <a href="{{ route('static-pages.show', ['locale' => $locale, 'key' => 'portal-terms']) }}">{{ __('static_pages.footer.portal_terms') }}</a>
        </div>

        <div class="footer-column">
            <h3>{{ __('site.footer.community') }}</h3>
            <a href="{{ route('home', ['locale' => $locale]) }}#gallery">{{ __('site.nav.gallery') }}</a>
            <a href="#newsletter">{{ __('site.footer.newsletter') }}</a>
            <a href="#">{{ __('site.footer.cooperation') }}</a>
        </div>

        <div class="footer-column footer-contact">
            <h3>{{ __('site.footer.contact') }}</h3>
            <a href="mailto:kontakt@okulary-3d.pl">kontakt@okulary-3d.pl</a>
            <span>okulary-3d.pl</span>
        </div>
    </div>

    <div class="site-container footer-bottom">
        <span>© {{ date('Y') }} Wortal Okulary 3D.</span>
        <span>{{ __('site.footer.copyright') }}</span>
    </div>
</footer>
