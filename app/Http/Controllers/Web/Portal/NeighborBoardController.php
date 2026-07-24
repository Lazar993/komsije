<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Enums\NeighborBoardCategory;
use App\Enums\NeighborBoardPostStatus;
use App\Http\Requests\NeighborBoard\StoreNeighborBoardCommentRequest;
use App\Http\Requests\NeighborBoard\StoreNeighborBoardPostRequest;
use App\Http\Requests\NeighborBoard\UpdateNeighborBoardPostRequest;
use App\Models\NeighborBoardPost;
use App\Repositories\Contracts\NeighborBoardPostRepositoryInterface;
use App\Services\NeighborBoardService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NeighborBoardController extends PortalController
{
    public function __construct(
        private readonly NeighborBoardService $service,
        private readonly NeighborBoardPostRepositoryInterface $posts,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', NeighborBoardPost::class);

        $building = $this->tenantContext->building();
        $status = (string) $request->query('status', 'all');

        if (! in_array($status, [
            'all',
            NeighborBoardPostStatus::Active->value,
            NeighborBoardPostStatus::Resolved->value,
            NeighborBoardPostStatus::Archived->value,
        ], true)) {
            $status = 'all';
        }

        $filters = [
            'status' => $status,
            'category' => $request->query('category'),
            'sort' => $request->query('sort', 'newest'),
            'search' => $request->query('search', ''),
        ];

        $posts = $this->posts->paginateForBuilding((int) $building->getKey(), $filters)
            ->withQueryString();

        if ($request->ajax()) {
            return view('portal.neighbor-board.partials.results', [
                'posts' => $posts,
                'status' => $status,
            ]);
        }

        return $this->portalView($request, 'portal.neighbor-board.index', [
            'posts' => $posts,
            'status' => $status,
            'categoryOptions' => NeighborBoardCategory::options(),
        ]);
    }

    public function create(Request $request): View
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [NeighborBoardPost::class, $building]);

        return $this->portalView($request, 'portal.neighbor-board.create', [
            'categoryOptions' => NeighborBoardCategory::options(),
        ]);
    }

    public function store(StoreNeighborBoardPostRequest $request): RedirectResponse
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [NeighborBoardPost::class, $building]);

        $post = $this->service->create($building, $request->user(), array_merge($request->validated(), [
            'images' => $request->file('images', []),
        ]));

        return redirect()
            ->route('portal.neighbor-board.show', $post)
            ->with('status', __('Objava je uspešno kreirana.'));
    }

    public function show(Request $request, NeighborBoardPost $post): View
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $post);

        $post->load([
            'author.apartments' => fn ($query) => $query->select(['apartments.id', 'apartments.building_id', 'apartments.number']),
            'images',
            'comments.user.apartments' => fn ($query) => $query->select(['apartments.id', 'apartments.building_id', 'apartments.number']),
        ])->loadCount('comments');

        return $this->portalView($request, 'portal.neighbor-board.show', [
            'post' => $post,
        ]);
    }

    public function edit(Request $request, NeighborBoardPost $post): View
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $post);

        $post->load('images');

        return $this->portalView($request, 'portal.neighbor-board.edit', [
            'post' => $post,
            'categoryOptions' => NeighborBoardCategory::options(),
        ]);
    }

    public function update(UpdateNeighborBoardPostRequest $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $post);

        $post = $this->service->update($post, array_merge($request->validated(), [
            'images' => $request->file('images', []),
        ]), $request->user());

        return redirect()
            ->route('portal.neighbor-board.show', $post)
            ->with('status', __('Objava je uspešno ažurirana.'));
    }

    public function markResolved(Request $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('markResolved', $post);

        $this->service->markResolved($post);

        return redirect()
            ->route('portal.neighbor-board.show', $post)
            ->with('status', __('Objava je označena kao rešena.'));
    }

    public function comment(StoreNeighborBoardCommentRequest $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('comment', $post);

        $this->service->addComment($post, $request->user(), $request->validated());

        return redirect()
            ->route('portal.neighbor-board.show', $post)
            ->with('status', __('Komentar je dodat.'));
    }

    public function archive(Request $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('archive', $post);

        $this->service->archive($post);

        return redirect()
            ->route('portal.neighbor-board.show', $post)
            ->with('status', __('Objava je arhivirana.'));
    }

    public function restore(Request $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('restoreArchived', $post);

        $this->service->restoreArchived($post);

        return redirect()
            ->route('portal.neighbor-board.show', $post)
            ->with('status', __('Objava je vraćena u aktivne.'));
    }

    public function togglePin(Request $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('pin', $post);

        $this->service->pin($post, ! (bool) $post->is_pinned);

        return redirect()->route('portal.neighbor-board.show', $post);
    }

    public function toggleCommentsLock(Request $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('lockComments', $post);

        $this->service->lockComments($post, ! (bool) $post->comments_locked);

        return redirect()->route('portal.neighbor-board.show', $post);
    }

    public function destroy(Request $request, NeighborBoardPost $post): RedirectResponse
    {
        abort_if($post->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('delete', $post);

        $this->service->delete($post);

        return redirect()
            ->route('portal.neighbor-board.index')
            ->with('status', __('Objava je obrisana.'));
    }
}
