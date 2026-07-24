@extends('portal.layouts.app')

@section('title', __('Izmena objave'))

@section('content')
    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Komšijska tabla') }}</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Izmeni objavu') }}</h1>
        </div>

        <div class="mt-8 max-w-3xl">
            @include('portal.neighbor-board._form', ['post' => $post])
        </div>
    </section>
@endsection
