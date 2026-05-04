@extends('portal.layouts.app')

@section('title', __('Edit Ticket'))

@section('content')
    <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <article class="komsije-surface min-w-0 rounded-[2rem] p-6 sm:p-8">
            <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Izmena prijave') }}</p>
            <h1 class="mt-3 break-words text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{{ $ticket->title }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Ažurirajte status, prioritet, zaduženje i dodatne detalje u istoj prijavi.') }}</p>

            <div class="mt-8">
                @include('portal.tickets._form', ['ticket' => $ticket])
            </div>
        </article>

        <aside class="komsije-surface min-w-0 rounded-[2rem] p-6">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('Saveti za izmenu') }}</h2>
            <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                <p>{{ __('Ažurirajte status čim postoji promena kako bi stanari imali tačne informacije.') }}</p>
                <p>{{ __('Po potrebi dodajte napomenu statusa radi jasnijeg toka rešavanja.') }}</p>
                <p>{{ __('Ako kvar više nije aktuelan, zatvorite prijavu kada je rešen.') }}</p>
            </div>
        </aside>
    </section>
@endsection