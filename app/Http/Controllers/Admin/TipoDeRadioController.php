<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TipoDeRadio\BulkDestroyTipoDeRadio;
use App\Http\Requests\Admin\TipoDeRadio\DestroyTipoDeRadio;
use App\Http\Requests\Admin\TipoDeRadio\IndexTipoDeRadio;
use App\Http\Requests\Admin\TipoDeRadio\StoreTipoDeRadio;
use App\Http\Requests\Admin\TipoDeRadio\UpdateTipoDeRadio;
use App\Models\TipoDeRadio;
use Brackets\AdminListing\Facades\AdminListing;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TipoDeRadioController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexTipoDeRadio $request
     * @return array|Factory|View
     */
    public function index(IndexTipoDeRadio $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(TipoDeRadio::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['id', 'nombre', 'descripcion'],

            // set columns to searchIn
            ['id', 'nombre', 'descripcion']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.tipo-de-radio.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.tipo-de-radio.create');

        return view('admin.tipo-de-radio.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTipoDeRadio $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreTipoDeRadio $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the TipoDeRadio
        $tipoDeRadio = TipoDeRadio::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/tipo-de-radios'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/tipo-de-radios');
    }

    /**
     * Display the specified resource.
     *
     * @param TipoDeRadio $tipoDeRadio
     * @throws AuthorizationException
     * @return void
     */
    public function show(TipoDeRadio $tipoDeRadio)
    {
        $this->authorize('admin.tipo-de-radio.show', $tipoDeRadio);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param TipoDeRadio $tipoDeRadio
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(TipoDeRadio $tipoDeRadio)
    {
        $this->authorize('admin.tipo-de-radio.edit', $tipoDeRadio);


        return view('admin.tipo-de-radio.edit', [
            'tipoDeRadio' => $tipoDeRadio,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTipoDeRadio $request
     * @param TipoDeRadio $tipoDeRadio
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateTipoDeRadio $request, TipoDeRadio $tipoDeRadio)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values TipoDeRadio
        $tipoDeRadio->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/tipo-de-radios'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/tipo-de-radios');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyTipoDeRadio $request
     * @param TipoDeRadio $tipoDeRadio
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyTipoDeRadio $request, TipoDeRadio $tipoDeRadio)
    {
        $tipoDeRadio->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyTipoDeRadio $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyTipoDeRadio $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    TipoDeRadio::whereIn('id', $bulkChunk)->delete();

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
