@php
    $locale = app()->getLocale();
@endphp

<footer class="site-footer" id="about">
    <section class="newsletter-strip" aria-label="{{ __('site.newsletter.title') }}">
        <div class="site-container newsletter-inner">
            <div class="newsletter-copy">
                <span class="newsletter-icon" aria-hidden="true">✦</span>
                <div>
                    <h2>{{ __('site.newsletter.title') }}</h2>
                    <p>{{ __('site.newsletter.description') }}</p>
                </div>
            </div>

            <form class="newsletter-form" action="#" method="post">
                <label class="sr-only" for="newsletter-email">
                    {{ __('site.newsletter.email_label') }}
                </label>
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    placeholder="{{ __('site.newsletter.email_placeholder') }}"
                    autocomplete="email"
                >
                <button type="submit">{{ __('site.newsletter.submit') }}</button>
            </form>
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
        </div>

        <div class="footer-column">
            <h3>{{ __('site.footer.support') }}</h3>
            <a href="#">{{ __('site.footer.faq') }}</a>
            <a href="#">{{ __('site.footer.shipping') }}</a>
            <a href="#">{{ __('site.footer.returns') }}</a>
            <a href="#">{{ __('site.footer.privacy') }}</a>
        </div>

        <div class="footer-column">
            <h3>{{ __('site.footer.community') }}</h3>
            <a href="{{ route('home', ['locale' => $locale]) }}#gallery">{{ __('site.nav.gallery') }}</a>
            <a href="#">{{ __('site.footer.newsletter') }}</a>
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
