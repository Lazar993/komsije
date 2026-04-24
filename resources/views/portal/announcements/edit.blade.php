@extends('portal.layouts.app')

@section('title', __('Edit Announcement'))

@section('content')
    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Izmena objave') }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $announcement->title }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Ažurirajte sadržaj, vreme objave ili status objavljivanja za ovu poruku.') }}</p>

        <div class="mt-8">
            @include('portal.announcements._form', ['announcement' => $announcement])
        </div>
    </section>
@endsection