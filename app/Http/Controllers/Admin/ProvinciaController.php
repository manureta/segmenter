<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Provincium\BulkDestroyProvincium;
use App\Http\Requests\Admin\Provincium\DestroyProvincium;
use App\Http\Requests\Admin\Provincium\IndexProvincium;
use App\Http\Requests\Admin\Provincium\StoreProvincium;
use App\Http\Requests\Admin\Provincium\UpdateProvincium;
use App\Models\Provincium;
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

class ProvinciaController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexProvincium $request
     * @return array|Factory|View
     */
    public function index(IndexProvincium $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Provincium::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['id', 'codigo', 'nombre', 'fecha_desde', 'fecha_hasta', 'observacion_id', 'geometria_id'],

            // set columns to searchIn
            ['id', 'codigo', 'nombre']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.provincium.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.provincium.create');

        return view('admin.provincium.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProvincium $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreProvincium $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Provincium
        $provincium = Provincium::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/provincia'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/provincia');
    }

    /**
     * Display the specified resource.
     *
     * @param Provincium $provincium
     * @throws AuthorizationException
     * @return void
     */
    public function show(Provincium $provincium)
    {
        $this->authorize('admin.provincium.show', $provincium);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Provincium $provincium
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Provincium $provincium)
    {
        $this->authorize('admin.provincium.edit', $provincium);


        return view('admin.provincium.edit', [
            'provincium' => $provincium,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProvincium $request
     * @param Provincium $provincium
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateProvincium $request, Provincium $provincium)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Provincium
        $provincium->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/provincia'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/provincia');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyProvincium $request
     * @param Provincium $provincium
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyProvincium $request, Provincium $provincium)
    {
        $provincium->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyProvincium $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyProvincium $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    Provincium::whereIn('id', $bulkChunk)->delete();

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
