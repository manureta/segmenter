@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('admin.tipo-de-radio.actions.create'))

@section('body')

    <div class="container-xl">

                <div class="card">
        
        <tipo-de-radio-form
            :action="'{{ url('admin/tipo-de-radios') }}'"
            v-cloak
            inline-template>

            <form class="form-horizontal form-create" method="post" @submit.prevent="onSubmit" :action="action" novalidate>
                
                <div class="card-header">
                    <i class="fa fa-plus"></i> {{ trans('admin.tipo-de-radio.actions.create') }}
                </div>

                <div class="card-body">
                    @include('admin.tipo-de-radio.components.form-elements')
                </div>
                                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" :disabled="submiting">
                        <i class="fa" :class="submiting ? 'fa-spinner' : 'fa-download'"></i>
                        {{ trans('brackets/admin-ui::admin.btn.save') }}
                    </button>
                </div>
                
            </form>

        </tipo-de-radio-form>

        </div>

        </div>

    
@endsection