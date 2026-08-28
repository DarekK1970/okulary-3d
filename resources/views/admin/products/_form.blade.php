@php
    $sourceLocale = old(
        'source_locale',
        $product->source_locale ?? config('locales.default', 'pl')
    );

    $variants = old('variants');

    if ($variants === null) {
        $variants = $product->exists
            ? $product->variants->map(fn ($variant) => $variant->toArray())->all()
            : [[
                'id' => null,
                'sku' => '',
                'name' => '',
                'price_gross' => '',
                'vat_rate' => '23.00',
                'currency' => 'PLN',
                'stock_quantity' => 0,
                'track_stock' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]];
    }

    $selectedMediaIds = array_map(
        'intval',
        (array) old(
            'media_ids',
            $product->exists ? $product->media->pluck('id')->all() : []
        )
    );

    $primaryMediaId = (int) old(
        'primary_media_id',
        $product->exists
            ? ($product->media->first(fn ($media) => (bool) $media->pivot->is_primary)?->id ?? 0)
            : 0
    );
@endphp

<div class="catalog-product-form-grid">
    <div class="catalog-product-main">
        <section class="cms-panel">
            <div class="catalog-section-title">
                <div>
                    <span class="admin-eyebrow">{{ __('catalog.admin.products.form.languages') }}</span>
                    <h2>{{ __('catalog.admin.products.form.localized_content') }}</h2>
                </div>
            </div>

            @foreach ($supportedLocales as $locale => $language)
                @php
                    $translation = $product->exists
                        ? $product->translation($locale)
                        : null;
                    $prefix = "translations.{$locale}";
                    $translationStatus = old(
                        "{$prefix}.translation_status",
                        $translation?->translation_status?->value ?? 'draft'
                    );
                @endphp

                <fieldset class="catalog-language-fieldset">
                    <legend>{{ strtoupper($locale) }} — {{ $language['native'] }}</legend>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.name') }}</label>
                        <input
                            name="translations[{{ $locale }}][name]"
                            type="text"
                            value="{{ old("{$prefix}.name", $translation?->name) }}"
                            maxlength="220"
                            data-catalog-slug-source="{{ $locale }}"
                        >
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.slug') }}</label>
                        <div class="cms-slug-row">
                            <span>/{{ $locale }}/shop/</span>
                            <input
                                name="translations[{{ $locale }}][slug]"
                                type="text"
                                value="{{ old("{$prefix}.slug", $translation?->slug) }}"
                                maxlength="240"
                                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                data-catalog-slug-target="{{ $locale }}"
                            >
                        </div>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.short_description') }}</label>
                        <textarea
                            name="translations[{{ $locale }}][short_description]"
                            rows="4"
                            maxlength="1200"
                        >{{ old("{$prefix}.short_description", $translation?->short_description) }}</textarea>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.description') }}</label>

                        <div class="wysiwyg" data-wysiwyg>
                            <div class="wysiwyg-toolbar" role="toolbar">
                                <button type="button" data-command="formatBlock" data-value="p">P</button>
                                <button type="button" data-command="formatBlock" data-value="h2">H2</button>
                                <button type="button" data-command="formatBlock" data-value="h3">H3</button>
                                <button type="button" data-command="bold"><strong>B</strong></button>
                                <button type="button" data-command="italic"><em>I</em></button>
                                <button type="button" data-command="insertUnorderedList">•</button>
                                <button type="button" data-command="insertOrderedList">1.</button>
                                <button type="button" data-link>🔗</button>
                            </div>

                            <div
                                class="wysiwyg-editor catalog-product-editor"
                                contenteditable="true"
                                data-editor
                            >{!! old("{$prefix}.description_html", $translation?->description_html) !!}</div>

                            <textarea
                                name="translations[{{ $locale }}][description_html]"
                                data-editor-output
                                hidden
                            >{{ old("{$prefix}.description_html", $translation?->description_html) }}</textarea>
                        </div>
                    </div>

                    <div class="catalog-seo-box">
                        <div class="cms-field">
                            <label>SEO title</label>
                            <input
                                name="translations[{{ $locale }}][seo_title]"
                                type="text"
                                value="{{ old("{$prefix}.seo_title", $translation?->seo_title) }}"
                                maxlength="70"
                            >
                        </div>

                        <div class="cms-field">
                            <label>Meta description</label>
                            <textarea
                                name="translations[{{ $locale }}][seo_description]"
                                rows="3"
                                maxlength="180"
                            >{{ old("{$prefix}.seo_description", $translation?->seo_description) }}</textarea>
                        </div>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.common.translation_status') }}</label>
                        <select name="translations[{{ $locale }}][translation_status]">
                            @foreach (['draft', 'review', 'ready'] as $status)
                                <option value="{{ $status }}" @selected($translationStatus === $status)>
                                    {{ __('catalog.translation_statuses.' . $status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </fieldset>
            @endforeach
        </section>

        <section class="cms-panel">
            <div class="catalog-section-title">
                <div>
                    <span class="admin-eyebrow">SKU / STOCK</span>
                    <h2>{{ __('catalog.admin.products.form.variants') }}</h2>
                    <p>{{ __('catalog.admin.products.form.variants_help') }}</p>
                </div>

                <button class="cms-secondary-button" type="button" data-add-variant>
                    + {{ __('catalog.admin.products.form.add_variant') }}
                </button>
            </div>

            <div class="catalog-variants" data-variants-container>
                @foreach ($variants as $index => $variant)
                    <div class="catalog-variant-row" data-variant-row>
                        <input
                            type="hidden"
                            name="variants[{{ $index }}][id]"
                            value="{{ $variant['id'] ?? '' }}"
                            data-variant-field="id"
                        >

                        <div class="cms-field">
                            <label>SKU</label>
                            <input
                                name="variants[{{ $index }}][sku]"
                                type="text"
                                value="{{ $variant['sku'] ?? '' }}"
                                maxlength="100"
                                required
                                data-variant-field="sku"
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.products.form.variant_name') }}</label>
                            <input
                                name="variants[{{ $index }}][name]"
                                type="text"
                                value="{{ $variant['name'] ?? '' }}"
                                maxlength="140"
                                data-variant-field="name"
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.products.form.price_gross') }}</label>
                            <input
                                name="variants[{{ $index }}][price_gross]"
                                type="number"
                                step="0.01"
                                min="0"
                                value="{{ $variant['price_gross'] ?? '' }}"
                                required
                                data-variant-field="price_gross"
                            >
                        </div>

                        <div class="cms-field">
                            <label>VAT %</label>
                            <input
                                name="variants[{{ $index }}][vat_rate]"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value="{{ $variant['vat_rate'] ?? '23.00' }}"
                                required
                                data-variant-field="vat_rate"
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.products.form.currency') }}</label>
                            <select
                                name="variants[{{ $index }}][currency]"
                                data-variant-field="currency"
                            >
                                <option value="PLN" @selected(($variant['currency'] ?? 'PLN') === 'PLN')>PLN</option>
                                <option value="EUR" @selected(($variant['currency'] ?? 'PLN') === 'EUR')>EUR</option>
                            </select>
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.products.form.stock') }}</label>
                            <input
                                name="variants[{{ $index }}][stock_quantity]"
                                type="number"
                                min="0"
                                value="{{ $variant['stock_quantity'] ?? 0 }}"
                                required
                                data-variant-field="stock_quantity"
                            >
                        </div>

                        <div class="catalog-variant-flags">
                            <label class="cms-checkbox">
                                <input
                                    name="variants[{{ $index }}][track_stock]"
                                    type="checkbox"
                                    value="1"
                                    @checked($variant['track_stock'] ?? true)
                                    data-variant-field="track_stock"
                                >
                                <span>{{ __('catalog.admin.products.form.track_stock') }}</span>
                            </label>

                            <label class="cms-checkbox">
                                <input
                                    name="variants[{{ $index }}][is_active]"
                                    type="checkbox"
                                    value="1"
                                    @checked($variant['is_active'] ?? true)
                                    data-variant-field="is_active"
                                >
                                <span>{{ __('catalog.admin.common.active') }}</span>
                            </label>
                        </div>

                        <input
                            name="variants[{{ $index }}][sort_order]"
                            type="hidden"
                            value="{{ $variant['sort_order'] ?? $index }}"
                            data-variant-field="sort_order"
                        >

                        <button class="catalog-remove-variant" type="button" data-remove-variant>×</button>
                    </div>
                @endforeach
            </div>

            <template data-variant-template>
                <div class="catalog-variant-row" data-variant-row>
                    <input type="hidden" data-variant-field="id" value="">

                    <div class="cms-field">
                        <label>SKU</label>
                        <input type="text" maxlength="100" required data-variant-field="sku">
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.variant_name') }}</label>
                        <input type="text" maxlength="140" data-variant-field="name">
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.price_gross') }}</label>
                        <input type="number" step="0.01" min="0" required data-variant-field="price_gross">
                    </div>

                    <div class="cms-field">
                        <label>VAT %</label>
                        <input type="number" step="0.01" min="0" max="100" value="23.00" required data-variant-field="vat_rate">
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.currency') }}</label>
                        <select data-variant-field="currency">
                            <option value="PLN">PLN</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('catalog.admin.products.form.stock') }}</label>
                        <input type="number" min="0" value="0" required data-variant-field="stock_quantity">
                    </div>

                    <div class="catalog-variant-flags">
                        <label class="cms-checkbox">
                            <input type="checkbox" value="1" checked data-variant-field="track_stock">
                            <span>{{ __('catalog.admin.products.form.track_stock') }}</span>
                        </label>

                        <label class="cms-checkbox">
                            <input type="checkbox" value="1" checked data-variant-field="is_active">
                            <span>{{ __('catalog.admin.common.active') }}</span>
                        </label>
                    </div>

                    <input type="hidden" data-variant-field="sort_order">
                    <button class="catalog-remove-variant" type="button" data-remove-variant>×</button>
                </div>
            </template>
        </section>

        <section class="cms-panel">
            <div class="catalog-section-title">
                <div>
                    <span class="admin-eyebrow">{{ __('media.kicker') }}</span>
                    <h2>{{ __('catalog.admin.products.form.gallery') }}</h2>
                    <p>{{ __('catalog.admin.products.form.gallery_help') }}</p>
                </div>

                <a class="cms-secondary-button" href="{{ route('admin.media.index') }}" target="_blank">
                    {{ __('catalog.admin.products.form.manage_media') }} ↗
                </a>
            </div>

            <div class="catalog-media-grid">
                @forelse ($mediaAssets as $media)
                    <div class="catalog-media-choice">
                        <img src="{{ $media->url() }}" alt="" loading="lazy">
                        <span>{{ $media->title ?: $media->original_name }}</span>

                        <div>
                            <label>
                                <input
                                    type="checkbox"
                                    name="media_ids[]"
                                    value="{{ $media->id }}"
                                    @checked(in_array($media->id, $selectedMediaIds, true))
                                >
                                {{ __('catalog.admin.products.form.use_image') }}
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="primary_media_id"
                                    value="{{ $media->id }}"
                                    @checked($primaryMediaId === $media->id)
                                >
                                {{ __('catalog.admin.products.form.primary_image') }}
                            </label>
                        </div>
                    </div>
                @empty
                    <p class="cms-empty">{{ __('catalog.admin.products.form.no_media') }}</p>
                @endforelse
            </div>

            <div class="catalog-upload-box">
                <label>{{ __('catalog.admin.products.form.upload_new') }}</label>
                <input
                    type="file"
                    name="new_media[]"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    multiple
                >
                <small>{{ __('catalog.admin.products.form.upload_new_help') }}</small>
            </div>
        </section>
    </div>

    <aside class="catalog-product-sidebar">
        <section class="cms-panel">
            <h2>{{ __('catalog.admin.products.form.settings') }}</h2>

            <div class="cms-field">
                <label>{{ __('catalog.admin.products.form.category') }}</label>
                <select name="category_id" required>
                    <option value="">{{ __('catalog.admin.products.form.choose_category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                            {{ $category->sourceTranslation()?->name ?? ('#' . $category->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label>{{ __('catalog.admin.common.source_locale') }}</label>
                <select name="source_locale" required>
                    @foreach ($supportedLocales as $locale => $language)
                        <option value="{{ $locale }}" @selected($sourceLocale === $locale)>
                            {{ strtoupper($locale) }} — {{ $language['native'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label>{{ __('catalog.admin.products.form.status') }}</label>
                <select name="status" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $product->status?->value ?? 'draft') === $status->value)>
                            {{ __('catalog.product_statuses.' . $status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label>{{ __('catalog.admin.products.form.brand') }}</label>
                <input
                    name="brand"
                    type="text"
                    value="{{ old('brand', $product->brand) }}"
                    maxlength="120"
                >
            </div>

            <label class="cms-checkbox">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))>
                <span>{{ __('catalog.admin.products.form.featured') }}</span>
            </label>
        </section>

        <div class="cms-form-actions">
            <button class="cms-primary-button" type="submit">
                {{ $product->exists ? __('catalog.admin.common.save') : __('catalog.admin.products.form.create') }}
            </button>

            <a class="cms-secondary-button" href="{{ route('admin.products.index') }}">
                {{ __('catalog.admin.common.cancel') }}
            </a>
        </div>
    </aside>
</div>
