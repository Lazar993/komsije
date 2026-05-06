@extends('layouts.public')

@section('title', __('Page not found').' — Komšije')
@section('meta_description', __('The page you are looking for may have been moved, deleted, or the address is incorrect.'))
@section('hide_footer', '1')

@section('content')
    <div class="relative isolate">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-[radial-gradient(ellipse_at_top,rgba(37,99,235,0.10),transparent_60%)]"></div>

        <section class="mx-auto w-full max-w-[44rem] px-5 pt-10 pb-16 sm:px-8 sm:pt-16 sm:pb-24">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-[var(--komsije-primary)]">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 0 1 0 1.06L8.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06l-5.25-5.25a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                </svg>
                {{ __('Back') }}
            </a>

            <div class="mt-8 overflow-hidden rounded-[2rem] border border-[var(--komsije-border)] bg-white/90 shadow-sm shadow-slate-200/60 backdrop-blur">
                <div class="grid gap-10 px-6 py-8 sm:px-10 sm:py-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--komsije-primary)]">
                            404
                        </p>
                        <h1 class="mt-4 text-balance text-4xl font-semibold leading-[1.1] tracking-tight text-[var(--komsije-dark)] sm:text-[2.875rem]">
                            {{ __('Page not found') }}
                        </h1>
                        <p class="mt-5 max-w-2xl text-pretty text-lg leading-relaxed text-slate-600">
                            {{ __('The page you are looking for may have been moved, deleted, or the address is incorrect.') }}
                        </p>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                            {{ __('Return to the homepage to continue browsing announcements, tickets, and resident updates.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-2xl bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white transition hover:bg-blue-700">
                                {{ __('Back to home') }}
                            </a>
                        </div>
                    </div>

                    <div class="flex justify-center lg:justify-end">
                        <div class="relative flex h-44 w-44 items-center justify-center rounded-[2rem] border border-blue-100 bg-[linear-gradient(180deg,rgba(239,246,255,0.95),rgba(255,255,255,0.98))] shadow-inner shadow-white">
                            <div class="absolute inset-4 rounded-[1.5rem] border border-dashed border-blue-200/80"></div>
                            <div class="text-center">
                                <div class="text-6xl font-semibold tracking-[-0.08em] text-[var(--komsije-primary)]">404</div>
                                <div class="mt-2 text-xs font-medium uppercase tracking-[0.22em] text-slate-500">Komšije</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection