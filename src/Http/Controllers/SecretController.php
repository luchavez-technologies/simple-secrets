<?php

namespace Luchavez\SimpleSecrets\Http\Controllers;

use App\Http\Controllers\Controller;
use Luchavez\SimpleSecrets\Repositories\SecretRepository;
use Luchavez\StarterKit\Exceptions\UnauthorizedException;
use Illuminate\Http\JsonResponse;

// Model
use Luchavez\SimpleSecrets\Models\Secret;

// Requests
use Luchavez\SimpleSecrets\Http\Requests\Secret\IndexSecretRequest;
use Luchavez\SimpleSecrets\Http\Requests\Secret\ShowSecretRequest;
use Luchavez\SimpleSecrets\Http\Requests\Secret\DeleteSecretRequest;
use Luchavez\SimpleSecrets\Http\Requests\Secret\RestoreSecretRequest;

// Events
use Luchavez\SimpleSecrets\Events\Secret\SecretCollectedEvent;
use Luchavez\SimpleSecrets\Events\Secret\SecretShownEvent;
use Luchavez\SimpleSecrets\Events\Secret\SecretArchivedEvent;
use Luchavez\SimpleSecrets\Events\Secret\SecretRestoredEvent;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Class SecretController
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretController extends Controller
{
    public function __construct(public SecretRepository $repository)
    {
        $this->middleware(config('simple-secrets.middlewares.index'))->only('index');
        $this->middleware(config('simple-secrets.middlewares.show'))->only('show');
        $this->middleware(config('simple-secrets.middlewares.destroy'))->only('destroy');
    }

    /**
     * Secret List
     *
     * @group Secret Management
     *
     * @param IndexSecretRequest $request
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function index(IndexSecretRequest $request): JsonResponse
    {
        if (! ($user = $request->user())) {
            throw new UnauthorizedException();
        }

        $data = $this->repository->builder()
            ->where(Secret::getOwnerIdColumn(), $user->id) // secrets shown should be owned by auth user
            ->allowedFilters([
                'description',
                AllowedFilter::scope('type'),
                AllowedFilter::scope('hidden'),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts([
                'created_at',
                'updated_at',
                'deleted_at',
                Secret::getExpiresAtColumn(),
                Secret::getDisabledAtColumn(),
            ])
            ->defaultSort('-id');

        if ($request->has('full_data') === true) {
            $data = $data->get();
        } else {
            $data = $data->fastPaginate($request->get('per_page', 15));
        }

        event(new SecretCollectedEvent($data));

        return simpleResponse()
            ->data($data)
            ->message('Successfully collected record.')
            ->success()
            ->generate();
    }

    /**
     * Show Secret
     *
     * @group Secret Management
     *
     * @param ShowSecretRequest $request
     * @param Secret $secret
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function show(ShowSecretRequest $request, Secret $secret): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is($secret->owner)) {
            throw new UnauthorizedException();
        }

        event(new SecretShownEvent($secret));

        return simpleResponse()
            ->data($secret)
            ->message('Successfully collected record.')
            ->success()
            ->generate();
    }

    /**
     * Soft Delete Secret
     *
     * @group Secret Management
     *
     * @param DeleteSecretRequest $request
     * @param Secret $secret
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function destroy(DeleteSecretRequest $request, Secret $secret): JsonResponse
    {
        if (! ($user = $request->user()) || $user->is($secret->owner)) {
            throw new UnauthorizedException();
        }

        $secret->delete();

        event(new SecretArchivedEvent($secret));

        return simpleResponse()
            ->data($secret)
            ->message('Successfully archived record.')
            ->success()
            ->generate();
    }

    /**
     * Restore Secret
     *
     * @group Secret Management
     *
     * @param RestoreSecretRequest $request
     * @param $secret
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function restore(RestoreSecretRequest $request, $secret): JsonResponse
    {
        if (! ($user = $request->user()) || $user->is($secret->owner)) {
            throw new UnauthorizedException();
        }

        $data = Secret::withTrashed()->find($secret)->restore();

        event(new SecretRestoredEvent($data));

        return simpleResponse()
            ->data($data)
            ->message('Successfully restored record.')
            ->success()
            ->generate();
    }
}
