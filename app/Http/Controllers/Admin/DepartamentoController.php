<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departamento\BulkDestroyDepartamento;
use App\Http\Requests\Admin\Departamento\DestroyDepartamento;
use App\Http\Requests\Admin\Departamento\IndexDepartamento;
use App\Http\Requests\Admin\Departamento\StoreDepartamento;
use App\Http\Requests\Admin\Departamento\UpdateDepartamento;
use App\Models\Departamento;
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

class DepartamentoController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexDepartamento $request
     * @return array|Factory|View
     */
    public function index(IndexDepartamento $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Departamento::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            [''],

            // set columns to searchIn
            ['']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.departamento.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.departamento.create');

        return view('admin.departamento.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDepartamento $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreDepartamento $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Departamento
        $departamento = Departamento::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/departamentos'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/departamentos');
    }

    /**
     * Display the specified resource.
     *
     * @param Departamento $departamento
     * @throws AuthorizationException
     * @return void
     */
    public function show(Departamento $departamento)
    {
        $this->authorize('admin.departamento.show', $departamento);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Departamento $departamento
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Departamento $departamento)
    {
        $this->authorize('admin.departamento.edit', $departamento);


        return view('admin.departamento.edit', [
            'departamento' => $departamento,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateDepartamento $request
     * @param Departamento $departamento
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateDepartamento $request, Departamento $departamento)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Departamento
        $departamento->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/departamentos'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/departamentos');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyDepartamento $request
     * @param Departamento $departamento
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyDepartamento $request, Departamento $departamento)
    {
        $departamento->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyDepartamento $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyDepartamento $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    Departamento::whereIn('id', $bulkChunk)->delete();

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
