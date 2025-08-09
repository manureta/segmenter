@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.departamento.actions.edit', ['name' => $departamento->id]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <departamento-form
                :action="'{{ $departamento->resource_url }}'"
                :data="{{ $departamento->toJson() }}"
                v-cloak
                inline-template>
            
                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.departamento.actions.edit', ['name' => $departamento->id]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.departamento.components.form-elements')
                    </div>
                    
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>
                    
                </form>

        </departamento-form>

        </div>
    
</div>

@endsection