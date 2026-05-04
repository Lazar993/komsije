@extends('layouts.public')

@section('title', $page->title.' — Komšije')

@if (! empty($page->meta_description))
    @section('meta_description', $page->meta_description)
@endif

@section('content')
    <div class="relative isolate">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-[radial-gradient(ellipse_at_top,rgba(37,99,235,0.10),transparent_60%)]"></div>

        <article class="mx-auto w-full max-w-[44rem] px-5 pt-10 pb-16 sm:px-8 sm:pt-16 sm:pb-24">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-[var(--komsije-primary)]">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 0 1 0 1.06L8.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06l-5.25-5.25a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                </svg>
                {{ __('Nazad') }}
            </a>

            <header class="mt-8">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--komsije-primary)]">
                    {{ __('Stranica') }}
                </p>
                <h1 class="mt-4 text-balance text-4xl font-semibold leading-[1.1] tracking-tight text-[var(--komsije-dark)] sm:text-[2.875rem]">
                    {{ $page->title }}
                </h1>
                @if (! empty($page->meta_description))
                    <p class="mt-5 text-pretty text-lg leading-relaxed text-slate-600">
                        {{ $page->meta_description }}
                    </p>
                @endif
                <div class="mt-6 flex items-center gap-3 text-sm text-slate-500">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-[var(--komsije-primary)]">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2ZM4.75 5.5c-.69 0-1.25.56-1.25 1.25V8h13V6.75c0-.69-.56-1.25-1.25-1.25H4.75ZM16.5 9.5h-13v5.75c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25V9.5Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span>
                        {{ __('Ažurirano') }}
                        <time datetime="{{ optional($page->updated_at)->toIso8601String() }}" class="font-medium text-slate-700">
                            {{ optional($page->updated_at)->translatedFormat('j. F Y.') }}
                        </time>
                    </span>
                </div>
            </header>

            <hr class="my-10 border-0 border-t border-[var(--komsije-border)]">

            <div class="page-content">
                {!! $page->content !!}
            </div>
        </article>
    </div>
@endsection
