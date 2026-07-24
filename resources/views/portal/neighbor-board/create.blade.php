@extends('portal.layouts.app')

@section('title', __('Nova objava'))

@section('content')
    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Komšijska tabla') }}</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Nova objava') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Jednostavna objava za komšije. Maksimalno tri slike.') }}</p>
        </div>

        <div class="mt-8 max-w-3xl">
            @include('portal.neighbor-board._form')
        </div>
    </section>
@endsection
