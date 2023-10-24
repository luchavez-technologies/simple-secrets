<?php

namespace Luchavez\SimpleSecrets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Luchavez\SimpleSecrets\Http\Requests\Secret\DeleteSecretRequest;
use Luchavez\SimpleSecrets\Http\Requests\Secret\IndexSecretRequest;
use Luchavez\SimpleSecrets\Http\Requests\Secret\RestoreSecretRequest;
use Luchavez\SimpleSecrets\Http\Requests\Secret\ShowSecretRequest;
use Luchavez\SimpleSecrets\Models\Secret;
use Luchavez\SimpleSecrets\Repositories\SecretRepository;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Class SecretController
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretController extends Controller
{
    /**
     * @param  SecretRepository  $repository
     */
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
     * @param  IndexSecretRequest  $request
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function index(IndexSecretRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthorizationException();
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
     * @param  ShowSecretRequest  $request
     * @param  Secret  $secret
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function show(ShowSecretRequest $request, Secret $secret): JsonResponse
    {
        if ($secret->owner()->isNot($request->user())) {
            throw new AuthorizationException();
        }

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
     * @param  DeleteSecretRequest  $request
     * @param  Secret  $secret
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(DeleteSecretRequest $request, Secret $secret): JsonResponse
    {
        if ($secret->owner()->isNot($request->user())) {
            throw new AuthorizationException();
        }

        $secret->delete();

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
     * @param  RestoreSecretRequest  $request
     * @param  Secret  $secret
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function restore(RestoreSecretRequest $request, Secret $secret): JsonResponse
    {
        if ($secret->owner()->isNot($request->user())) {
            throw new AuthorizationException();
        }

        $secret->restore();

        return simpleResponse()
            ->data($secret)
            ->message('Successfully restored record.')
            ->success()
            ->generate();
    }
}
