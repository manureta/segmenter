<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Localidad\BulkDestroyLocalidad;
use App\Http\Requests\Admin\Localidad\DestroyLocalidad;
use App\Http\Requests\Admin\Localidad\IndexLocalidad;
use App\Http\Requests\Admin\Localidad\StoreLocalidad;
use App\Http\Requests\Admin\Localidad\UpdateLocalidad;
use App\Models\Localidad;
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

class LocalidadController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexLocalidad $request
     * @return array|Factory|View
     */
    public function index(IndexLocalidad $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Localidad::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['id', 'codigo', 'nombre', 'aglomerado_id', 'tipo_de_localidad_id', 'tipo_de_poblacion_id', 'fecha_desde', 'fecha_hasta', 'observacion_id', 'geometria_id', 'cap_de_rep', 'cap_de_pcia', 'cab_de_depto', 'sede_gob_loc'],

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

        return view('admin.localidad.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.localidad.create');

        return view('admin.localidad.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreLocalidad $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreLocalidad $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Localidad
        $localidad = Localidad::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/localidads'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/localidads');
    }

    /**
     * Display the specified resource.
     *
     * @param Localidad $localidad
     * @throws AuthorizationException
     * @return void
     */
    public function show(Localidad $localidad)
    {
        $this->authorize('admin.localidad.show', $localidad);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Localidad $localidad
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Localidad $localidad)
    {
        $this->authorize('admin.localidad.edit', $localidad);


        return view('admin.localidad.edit', [
            'localidad' => $localidad,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateLocalidad $request
     * @param Localidad $localidad
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateLocalidad $request, Localidad $localidad)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Localidad
        $localidad->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/localidads'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/localidads');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyLocalidad $request
     * @param Localidad $localidad
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyLocalidad $request, Localidad $localidad)
    {
        $localidad->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyLocalidad $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyLocalidad $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    Localidad::whereIn('id', $bulkChunk)->delete();

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
