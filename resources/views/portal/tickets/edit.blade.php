@extends('portal.layouts.app')

@section('title', __('Edit Ticket'))

@section('content')
    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Izmena prijave') }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $ticket->title }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Ažurirajte status, prioritet, zaduženje i dodatne detalje u istoj prijavi.') }}</p>

        <div class="mt-8">
            @include('portal.tickets._form', ['ticket' => $ticket])
        </div>
    </section>
@endsection