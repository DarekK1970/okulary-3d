@php
    $currentStatus = old('status', $article->status?->value ?? 'draft');
    $publishedAt = old(
        'published_at',
        $article->published_at?->format('Y-m-d\TH:i')
    );
@endphp

<div class="cms-editor-grid">
    <div class="cms-editor-main">
        <section class="cms-panel">
            <div class="cms-field">
                <label for="title">{{ __('admin.articles.form.title') }}</label>
                <input
                    id="title"
                    name="title"
                    type="text"
                    value="{{ old('title', $article->title) }}"
                    required
                    maxlength="220"
                    data-slug-source
                >
            </div>

            <div class="cms-field">
                <label for="slug">{{ __('admin.articles.form.slug') }}</label>
                <div class="cms-slug-row">
                    <span>/</span>
                    <input
                        id="slug"
                        name="slug"
                        type="text"
                        value="{{ old('slug', $article->slug) }}"
                        maxlength="240"
                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                        data-slug-target
                    >
                </div>
                <small>{{ __('admin.articles.form.slug_help') }}</small>
            </div>

            <div class="cms-field">
                <label for="excerpt">{{ __('admin.articles.form.excerpt') }}</label>
                <textarea id="excerpt" name="excerpt" rows="4" maxlength="1000">{{ old('excerpt', $article->excerpt) }}</textarea>
            </div>

            <div class="cms-field">
                <label>{{ __('admin.articles.form.body') }}</label>

                <div class="wysiwyg" data-wysiwyg>
                    <div class="wysiwyg-toolbar" role="toolbar" aria-label="{{ __('admin.articles.form.editor_toolbar') }}">
                        <button type="button" data-command="formatBlock" data-value="p">P</button>
                        <button type="button" data-command="formatBlock" data-value="h2">H2</button>
                        <button type="button" data-command="formatBlock" data-value="h3">H3</button>
                        <button type="button" data-command="bold"><strong>B</strong></button>
                        <button type="button" data-command="italic"><em>I</em></button>
                        <button type="button" data-command="insertUnorderedList">• Lista</button>
                        <button type="button" data-command="insertOrderedList">1. Lista</button>
                        <button type="button" data-command="formatBlock" data-value="blockquote">❝</button>
                        <button type="button" data-link>🔗</button>
                    </div>

                    <div
                        class="wysiwyg-editor"
                        contenteditable="true"
                        data-editor
                    >{!! old('body_html', $article->body_html) !!}</div>

                    <textarea
                        name="body_html"
                        data-editor-output
                        hidden
                    >{{ old('body_html', $article->body_html) }}</textarea>
                </div>
            </div>
        </section>
    </div>

    <aside class="cms-editor-sidebar">
        <section class="cms-panel">
            <h2>{{ __('admin.articles.form.publication') }}</h2>

            <div class="cms-field">
                <label for="category_id">{{ __('admin.articles.form.category') }}</label>
                <select id="category_id" name="category_id" required>
                    <option value="">{{ __('admin.articles.form.choose_category') }}</option>
                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected((string) old('category_id', $article->category_id) === (string) $category->id)
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label for="status">{{ __('admin.articles.form.status') }}</label>
                <select id="status" name="status" data-status-select required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>
                            {{ __('admin.articles.statuses.' . $status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field" data-publication-date>
                <label for="published_at">{{ __('admin.articles.form.published_at') }}</label>
                <input
                    id="published_at"
                    name="published_at"
                    type="datetime-local"
                    value="{{ $publishedAt }}"
                >
                <small>{{ __('admin.articles.form.published_at_help') }}</small>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('admin.articles.form.hero') }}</h2>

            @if ($article->hero_image_path)
                <div class="cms-current-image">
                    <img src="{{ Storage::url($article->hero_image_path) }}" alt="">
                </div>

                <label class="cms-checkbox">
                    <input type="checkbox" name="remove_hero_image" value="1">
                    <span>{{ __('admin.articles.form.remove_hero') }}</span>
                </label>
            @endif

            <div class="cms-field">
                <label for="hero_image">{{ __('admin.articles.form.hero_upload') }}</label>
                <input
                    id="hero_image"
                    name="hero_image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    data-image-input
                >
                <small>{{ __('admin.articles.form.hero_help') }}</small>
            </div>

            <div class="cms-image-preview" data-image-preview hidden>
                <img alt="{{ __('admin.articles.form.hero_preview') }}">
            </div>
        </section>

        <div class="cms-form-actions">
            <button class="cms-primary-button cms-submit-button" type="submit">
                {{ $article->exists ? __('admin.articles.form.save') : __('admin.articles.form.create') }}
            </button>

            <a class="cms-secondary-button" href="{{ route('admin.articles.index') }}">
                {{ __('admin.articles.form.cancel') }}
            </a>
        </div>
    </aside>
</div>
