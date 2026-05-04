@extends('layouts.public')

@section('title', $page->title.' — Komšije')

@if (! empty($page->meta_description))
    @section('meta_description', $page->meta_description)
@endif

@section('content')
    <article class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="mb-8 border-b border-[var(--komsije-border)] pb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--komsije-primary)]">
                {{ __('Stranica') }}
            </p>
            <h1 class="mt-3 text-3xl font-semibold leading-tight text-[var(--komsije-dark)] sm:text-4xl">
                {{ $page->title }}
            </h1>
            <p class="mt-3 text-sm text-slate-500">
                {{ __('Ažurirano') }}: {{ optional($page->updated_at)->translatedFormat('j. F Y.') }}
            </p>
        </header>

        <div class="page-content text-base leading-relaxed text-slate-700">
            {!! $page->content !!}
        </div>
    </article>
@endsection

@push('styles')
@endpush
