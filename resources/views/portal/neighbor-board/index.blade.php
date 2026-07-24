@extends('portal.layouts.app')

@section('title', __('Komšijska tabla'))

@section('content')
    @php
        $selectedCategory = (string) request('category', '');
    @endphp

    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <div class="relative overflow-hidden rounded-[1.75rem] border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-emerald-50 px-5 py-6 sm:px-7">
            <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-sky-200/40 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-10 left-10 h-24 w-24 rounded-full bg-emerald-200/45 blur-2xl"></div>

            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Komšijska tabla') }}</p>
                    </div>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Objave za :building', ['building' => $currentBuilding->name]) }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Kratke objave između komšija: jednostavno, jasno i bez nereda.') }}</p>
                </div>

                @can('create', [App\Models\NeighborBoardPost::class, $currentBuilding])
                    <a href="{{ route('portal.neighbor-board.create') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] bg-gradient-to-r from-[var(--komsije-primary)] to-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:from-blue-700 hover:to-cyan-700">
                        <span aria-hidden="true" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[13px]">✚</span>
                        <span>{{ __('Nova objava') }}</span>
                    </a>
                @endcan
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 px-1">
            <p class="text-xs font-medium tracking-[0.04em] text-slate-500">{{ __('Filteri se primenjuju automatski dok kucaš i menjaš opcije.') }}</p>
            <p data-neighbor-board-live-status class="text-xs font-semibold text-emerald-700" aria-live="polite"></p>
        </div>

        <form method="GET" action="{{ route('portal.neighbor-board.index') }}" data-neighbor-board-filters class="mt-6 grid gap-4 rounded-[1.5rem] border border-[var(--komsije-border)] bg-slate-50 p-5 lg:grid-cols-4">
            <input type="hidden" name="category" value="{{ $selectedCategory }}">

            <div class="lg:col-span-2">
                <label for="search" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Pretraga') }}</label>
                <input id="search" name="search" type="text" value="{{ request('search') }}" class="komsije-input w-full rounded-2xl px-4 py-3" placeholder="{{ __('Naslov ili opis') }}">
            </div>

            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Status') }}</label>
                <select id="status" name="status" class="komsije-input w-full rounded-2xl px-4 py-3">
                    <option value="all" @selected(($status ?? request('status', 'all')) === 'all')>{{ __('Svi') }}</option>
                    <option value="active" @selected(($status ?? request('status', 'all')) === 'active')>{{ __('Aktivno') }}</option>
                    <option value="resolved" @selected(($status ?? request('status', 'all')) === 'resolved')>{{ __('Rešeno') }}</option>
                    <option value="archived" @selected(($status ?? request('status', 'all')) === 'archived')>{{ __('Arhivirano') }}</option>
                </select>
            </div>

            <div>
                <label for="sort" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Sortiranje') }}</label>
                <select id="sort" name="sort" class="komsije-input w-full rounded-2xl px-4 py-3">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>{{ __('Najnovije') }}</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>{{ __('Najstarije') }}</option>
                </select>
            </div>

            <div class="lg:col-span-5 flex flex-wrap items-center gap-3">
                <a href="{{ route('portal.neighbor-board.index') }}" class="rounded-[1rem] border border-[var(--komsije-border)] bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Resetuj') }}</a>
            </div>
        </form>

        <div class="mt-4 -mx-1 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:overflow-visible sm:px-0 sm:pb-0" data-neighbor-board-quick-categories>
            <div class="flex w-max snap-x snap-mandatory items-center gap-2 sm:w-auto sm:flex-wrap sm:snap-none">
                <button
                    type="button"
                    data-category-value=""
                    class="snap-start shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $selectedCategory === '' ? 'border-[var(--komsije-primary)] bg-blue-50 text-[var(--komsije-primary)]' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-[var(--komsije-primary)]' }}"
                    aria-pressed="{{ $selectedCategory === '' ? 'true' : 'false' }}"
                >
                    {{ __('Sve') }}
                </button>

                @foreach ($categoryOptions as $value => $label)
                    <button
                        type="button"
                        data-category-value="{{ $value }}"
                        class="snap-start shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $selectedCategory === (string) $value ? 'border-[var(--komsije-primary)] bg-blue-50 text-[var(--komsije-primary)]' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-[var(--komsije-primary)]' }}"
                        aria-pressed="{{ $selectedCategory === (string) $value ? 'true' : 'false' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div data-neighbor-board-results aria-live="polite">
            @include('portal.neighbor-board.partials.results', ['posts' => $posts])
        </div>

        <script>
            (() => {
                const form = document.querySelector('[data-neighbor-board-filters]');
                const results = document.querySelector('[data-neighbor-board-results]');
                const liveStatus = document.querySelector('[data-neighbor-board-live-status]');
                const categoryInput = form?.querySelector('input[name="category"]');
                const quickCategoryButtons = Array.from(document.querySelectorAll('[data-neighbor-board-quick-categories] [data-category-value]'));

                if (!form || !results) {
                    return;
                }

                const searchInput = form.querySelector('input[name="search"]');
                const controls = Array.from(form.querySelectorAll('input, select'));
                let requestController = null;
                let debounceTimer = null;

                const setLoading = (isLoading) => {
                    if (isLoading) {
                        results.setAttribute('aria-busy', 'true');
                        results.classList.add('pointer-events-none', 'opacity-60');

                        if (liveStatus) {
                            liveStatus.textContent = '{{ __('Osvežavanje objava...') }}';
                        }

                        return;
                    }

                    results.removeAttribute('aria-busy');
                    results.classList.remove('pointer-events-none', 'opacity-60');

                    if (liveStatus) {
                        liveStatus.textContent = '{{ __('Rezultati ažurirani') }}';
                    }
                };

                const setActiveQuickCategory = () => {
                    const currentValue = categoryInput?.value ?? '';

                    quickCategoryButtons.forEach((button) => {
                        const isActive = button.dataset.categoryValue === currentValue;

                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                        button.classList.toggle('border-[var(--komsije-primary)]', isActive);
                        button.classList.toggle('bg-blue-50', isActive);
                        button.classList.toggle('text-[var(--komsije-primary)]', isActive);
                        button.classList.toggle('border-slate-200', !isActive);
                        button.classList.toggle('bg-white', !isActive);
                        button.classList.toggle('text-slate-600', !isActive);
                    });
                };

                const fetchResults = async () => {
                    const params = new URLSearchParams(new FormData(form));
                    const url = `${form.action}?${params.toString()}`;

                    if (requestController) {
                        requestController.abort();
                    }

                    requestController = new AbortController();
                    setLoading(true);

                    try {
                        const response = await fetch(url, {
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                            signal: requestController.signal,
                        });

                        if (!response.ok) {
                            return;
                        }

                        results.innerHTML = await response.text();
                        window.history.replaceState({}, '', url);
                        setActiveQuickCategory();
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            return;
                        }
                    } finally {
                        setLoading(false);
                    }
                };

                const scheduleFetch = () => {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = window.setTimeout(fetchResults, 260);
                };

                controls.forEach((control) => {
                    if (control === searchInput) {
                        control.addEventListener('input', scheduleFetch);

                        return;
                    }

                    control.addEventListener('change', fetchResults);
                });

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    fetchResults();
                });

                quickCategoryButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        if (!categoryInput) {
                            return;
                        }

                        categoryInput.value = button.dataset.categoryValue ?? '';
                        setActiveQuickCategory();
                        fetchResults();
                    });
                });

                setActiveQuickCategory();
            })();
        </script>
    </section>
@endsection
