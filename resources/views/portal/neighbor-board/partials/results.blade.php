@if ($posts->isEmpty())
    <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center text-sm text-slate-500">
        {{ __('Nema objava za izabrane filtere.') }}
    </div>
@else
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @foreach ($posts as $post)
            <x-portal.neighbor-board-card :post="$post" :href="route('portal.neighbor-board.show', $post)" />
        @endforeach
    </div>
@endif

<div class="mt-6">{{ $posts->links() }}</div>
