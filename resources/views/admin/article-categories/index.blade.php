@extends('admin.layout')

@section('title', __('admin.categories.title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.categories.title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('admin.categories.kicker') }}</span>
            <h1>{{ __('admin.categories.title') }}</h1>
            <p>{{ __('admin.categories.description') }}</p>
        </div>
    </div>

    <div class="category-admin-grid">
        <section class="cms-panel">
            <h2>{{ __('admin.categories.new') }}</h2>

            <form method="post" action="{{ route('admin.article-categories.store') }}" class="category-form">
                @csrf

                <div class="cms-field">
                    <label for="new_name">{{ __('admin.categories.form.name') }}</label>
                    <input id="new_name" name="name" type="text" required maxlength="120">
                </div>

                <div class="cms-field">
                    <label for="new_slug">{{ __('admin.categories.form.slug') }}</label>
                    <input id="new_slug" name="slug" type="text" maxlength="140">
                </div>

                <div class="cms-field">
                    <label for="new_description">{{ __('admin.categories.form.description') }}</label>
                    <textarea id="new_description" name="description" rows="4" maxlength="1000"></textarea>
                </div>

                <div class="cms-field">
                    <label for="new_sort_order">{{ __('admin.categories.form.order') }}</label>
                    <input id="new_sort_order" name="sort_order" type="number" min="0" max="9999" value="0">
                </div>

                <label class="cms-checkbox">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>{{ __('admin.categories.form.active') }}</span>
                </label>

                <button class="cms-primary-button" type="submit">
                    {{ __('admin.categories.form.add') }}
                </button>
            </form>
        </section>

        <section class="cms-panel">
            <h2>{{ __('admin.categories.list') }}</h2>

            <div class="category-list">
                @forelse ($categories as $category)
                    <details class="category-item">
                        <summary>
                            <span>
                                <strong>{{ $category->name }}</strong>
                                <small>/{{ $category->slug }}</small>
                            </span>

                            <span class="category-meta">
                                {{ $category->articles_count }} {{ __('admin.categories.articles_short') }}
                            </span>
                        </summary>

                        <div class="category-item-body">
                            <form method="post" action="{{ route('admin.article-categories.update', $category) }}" class="category-form">
                                @csrf
                                @method('PUT')

                                <div class="cms-field">
                                    <label>{{ __('admin.categories.form.name') }}</label>
                                    <input name="name" type="text" value="{{ $category->name }}" required maxlength="120">
                                </div>

                                <div class="cms-field">
                                    <label>{{ __('admin.categories.form.slug') }}</label>
                                    <input name="slug" type="text" value="{{ $category->slug }}" maxlength="140">
                                </div>

                                <div class="cms-field">
                                    <label>{{ __('admin.categories.form.description') }}</label>
                                    <textarea name="description" rows="3" maxlength="1000">{{ $category->description }}</textarea>
                                </div>

                                <div class="cms-field">
                                    <label>{{ __('admin.categories.form.order') }}</label>
                                    <input name="sort_order" type="number" min="0" max="9999" value="{{ $category->sort_order }}">
                                </div>

                                <label class="cms-checkbox">
                                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active)>
                                    <span>{{ __('admin.categories.form.active') }}</span>
                                </label>

                                <button class="cms-secondary-button" type="submit">
                                    {{ __('admin.categories.form.save') }}
                                </button>
                            </form>

                            <form
                                method="post"
                                action="{{ route('admin.article-categories.destroy', $category) }}"
                                onsubmit="return confirm('{{ __('admin.categories.delete_confirm') }}')"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="cms-danger-button" type="submit">
                                    {{ __('admin.categories.delete') }}
                                </button>
                            </form>
                        </div>
                    </details>
                @empty
                    <p class="cms-empty">{{ __('admin.categories.empty') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
@endsection
