@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.localidade.actions.edit', ['name' => $localidade->id]))

@section('body')

    <div class="container-xl">
        <div class="card">

            <localidade-form
                :action="'{{ $localidade->resource_url }}'"
                :data="{{ $localidade->toJson() }}"
                v-cloak
                inline-template>
            
                <form class="form-horizontal form-edit" method="post" @submit.prevent="onSubmit" :action="action" novalidate>


                    <div class="card-header">
                        <i class="fa fa-pencil"></i> {{ trans('admin.localidade.actions.edit', ['name' => $localidade->id]) }}
                    </div>

                    <div class="card-body">
                        @include('admin.localidade.components.form-elements')
                    </div>
                    
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="submiting">
                            <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                            {{ trans('brackets/admin-ui::admin.btn.save') }}
                        </button>
                    </div>
                    
                </form>

        </localidade-form>

        </div>
    
</div>

@endsection