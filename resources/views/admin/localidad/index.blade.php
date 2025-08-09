@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.localidad.actions.index'))

@section('body')

    <localidad-listing
        :data="{{ $data->toJson() }}"
        :url="'{{ url('admin/localidads') }}'"
        inline-template>

        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ trans('admin.localidad.actions.index') }}
                        <a class="btn btn-primary btn-spinner btn-sm pull-right m-b-0" href="{{ url('admin/localidads/create') }}" role="button"><i class="fa fa-plus"></i>&nbsp; {{ trans('admin.localidad.actions.create') }}</a>
                    </div>
                    <div class="card-body" v-cloak>
                        <div class="card-block">
                            <form @submit.prevent="">
                                <div class="row justify-content-md-between">
                                    <div class="col col-lg-7 col-xl-5 form-group">
                                        <div class="input-group">
                                            <input class="form-control" placeholder="{{ trans('brackets/admin-ui::admin.placeholder.search') }}" v-model="search" @keyup.enter="filter('search', $event.target.value)" />
                                            <span class="input-group-append">
                                                <button type="button" class="btn btn-primary" @click="filter('search', search)"><i class="fa fa-search"></i>&nbsp; {{ trans('brackets/admin-ui::admin.btn.search') }}</button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-sm-auto form-group ">
                                        <select class="form-control" v-model="pagination.state.per_page">
                                            
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                            </form>

                            <table class="table table-hover table-listing">
                                <thead>
                                    <tr>
                                        <th class="bulk-checkbox">
                                            <input class="form-check-input" id="enabled" type="checkbox" v-model="isClickedAll" v-validate="''" data-vv-name="enabled"  name="enabled_fake_element" @click="onBulkItemsClickedAllWithPagination()">
                                            <label class="form-check-label" for="enabled">
                                                #
                                            </label>
                                        </th>

                                        <th is='sortable' :column="'id'">{{ trans('admin.localidad.columns.id') }}</th>
                                        <th is='sortable' :column="'codigo'">{{ trans('admin.localidad.columns.codigo') }}</th>
                                        <th is='sortable' :column="'nombre'">{{ trans('admin.localidad.columns.nombre') }}</th>
                                        <th is='sortable' :column="'aglomerado_id'">{{ trans('admin.localidad.columns.aglomerado_id') }}</th>
                                        <th is='sortable' :column="'tipo_de_localidad_id'">{{ trans('admin.localidad.columns.tipo_de_localidad_id') }}</th>
                                        <th is='sortable' :column="'tipo_de_poblacion_id'">{{ trans('admin.localidad.columns.tipo_de_poblacion_id') }}</th>
                                        <th is='sortable' :column="'fecha_desde'">{{ trans('admin.localidad.columns.fecha_desde') }}</th>
                                        <th is='sortable' :column="'fecha_hasta'">{{ trans('admin.localidad.columns.fecha_hasta') }}</th>
                                        <th is='sortable' :column="'observacion_id'">{{ trans('admin.localidad.columns.observacion_id') }}</th>
                                        <th is='sortable' :column="'geometria_id'">{{ trans('admin.localidad.columns.geometria_id') }}</th>
                                        <th is='sortable' :column="'cap_de_rep'">{{ trans('admin.localidad.columns.cap_de_rep') }}</th>
                                        <th is='sortable' :column="'cap_de_pcia'">{{ trans('admin.localidad.columns.cap_de_pcia') }}</th>
                                        <th is='sortable' :column="'cab_de_depto'">{{ trans('admin.localidad.columns.cab_de_depto') }}</th>
                                        <th is='sortable' :column="'sede_gob_loc'">{{ trans('admin.localidad.columns.sede_gob_loc') }}</th>

                                        <th></th>
                                    </tr>
                                    <tr v-show="(clickedBulkItemsCount > 0) || isClickedAll">
                                        <td class="bg-bulk-info d-table-cell text-center" colspan="16">
                                            <span class="align-middle font-weight-light text-dark">{{ trans('brackets/admin-ui::admin.listing.selected_items') }} @{{ clickedBulkItemsCount }}.  <a href="#" class="text-primary" @click="onBulkItemsClickedAll('/admin/localidads')" v-if="(clickedBulkItemsCount < pagination.state.total)"> <i class="fa" :class="bulkCheckingAllLoader ? 'fa-spinner' : ''"></i> {{ trans('brackets/admin-ui::admin.listing.check_all_items') }} @{{ pagination.state.total }}</a> <span class="text-primary">|</span> <a
                                                        href="#" class="text-primary" @click="onBulkItemsClickedAllUncheck()">{{ trans('brackets/admin-ui::admin.listing.uncheck_all_items') }}</a>  </span>

                                            <span class="pull-right pr-2">
                                                <button class="btn btn-sm btn-danger pr-3 pl-3" @click="bulkDelete('/admin/localidads/bulk-destroy')">{{ trans('brackets/admin-ui::admin.btn.delete') }}</button>
                                            </span>

                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in collection" :key="item.id" :class="bulkItems[item.id] ? 'bg-bulk' : ''">
                                        <td class="bulk-checkbox">
                                            <input class="form-check-input" :id="'enabled' + item.id" type="checkbox" v-model="bulkItems[item.id]" v-validate="''" :data-vv-name="'enabled' + item.id"  :name="'enabled' + item.id + '_fake_element'" @click="onBulkItemClicked(item.id)" :disabled="bulkCheckingAllLoader">
                                            <label class="form-check-label" :for="'enabled' + item.id">
                                            </label>
                                        </td>

                                    <td>@{{ item.id }}</td>
                                        <td>@{{ item.codigo }}</td>
                                        <td>@{{ item.nombre }}</td>
                                        <td>@{{ item.aglomerado_id }}</td>
                                        <td>@{{ item.tipo_de_localidad_id }}</td>
                                        <td>@{{ item.tipo_de_poblacion_id }}</td>
                                        <td>@{{ item.fecha_desde | datetime }}</td>
                                        <td>@{{ item.fecha_hasta | datetime }}</td>
                                        <td>@{{ item.observacion_id }}</td>
                                        <td>@{{ item.geometria_id }}</td>
                                        <td>@{{ item.cap_de_rep }}</td>
                                        <td>@{{ item.cap_de_pcia }}</td>
                                        <td>@{{ item.cab_de_depto }}</td>
                                        <td>@{{ item.sede_gob_loc }}</td>
                                        
                                        <td>
                                            <div class="row no-gutters">
                                                <div class="col-auto">
                                                    <a class="btn btn-sm btn-spinner btn-info" :href="item.resource_url + '/edit'" title="{{ trans('brackets/admin-ui::admin.btn.edit') }}" role="button"><i class="fa fa-edit"></i></a>
                                                </div>
                                                <form class="col" @submit.prevent="deleteItem(item.resource_url)">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ trans('brackets/admin-ui::admin.btn.delete') }}"><i class="fa fa-trash-o"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="row" v-if="pagination.state.total > 0">
                                <div class="col-sm">
                                    <span class="pagination-caption">{{ trans('brackets/admin-ui::admin.pagination.overview') }}</span>
                                </div>
                                <div class="col-sm-auto">
                                    <pagination></pagination>
                                </div>
                            </div>

                            <div class="no-items-found" v-if="!collection.length > 0">
                                <i class="icon-magnifier"></i>
                                <h3>{{ trans('brackets/admin-ui::admin.index.no_items') }}</h3>
                                <p>{{ trans('brackets/admin-ui::admin.index.try_changing_items') }}</p>
                                <a class="btn btn-primary btn-spinner" href="{{ url('admin/localidads/create') }}" role="button"><i class="fa fa-plus"></i>&nbsp; {{ trans('admin.localidad.actions.create') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </localidad-listing>

@endsection