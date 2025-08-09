<div class="form-group row align-items-center" :class="{'has-danger': errors.has('codigo'), 'has-success': fields.codigo && fields.codigo.valid }">
    <label for="codigo" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.provincium.columns.codigo') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.codigo" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('codigo'), 'form-control-success': fields.codigo && fields.codigo.valid}" id="codigo" name="codigo" placeholder="{{ trans('admin.provincium.columns.codigo') }}">
        <div v-if="errors.has('codigo')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('codigo') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('nombre'), 'has-success': fields.nombre && fields.nombre.valid }">
    <label for="nombre" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.provincium.columns.nombre') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.nombre" v-validate="'required'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('nombre'), 'form-control-success': fields.nombre && fields.nombre.valid}" id="nombre" name="nombre" placeholder="{{ trans('admin.provincium.columns.nombre') }}">
        <div v-if="errors.has('nombre')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('nombre') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('fecha_desde'), 'has-success': fields.fecha_desde && fields.fecha_desde.valid }">
    <label for="fecha_desde" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.provincium.columns.fecha_desde') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-sm-8'">
        <div class="input-group input-group--custom">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <datetime v-model="form.fecha_desde" :config="datePickerConfig" v-validate="'date_format:yyyy-MM-dd HH:mm:ss'" class="flatpickr" :class="{'form-control-danger': errors.has('fecha_desde'), 'form-control-success': fields.fecha_desde && fields.fecha_desde.valid}" id="fecha_desde" name="fecha_desde" placeholder="{{ trans('brackets/admin-ui::admin.forms.select_a_date') }}"></datetime>
        </div>
        <div v-if="errors.has('fecha_desde')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('fecha_desde') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('fecha_hasta'), 'has-success': fields.fecha_hasta && fields.fecha_hasta.valid }">
    <label for="fecha_hasta" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.provincium.columns.fecha_hasta') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-sm-8'">
        <div class="input-group input-group--custom">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <datetime v-model="form.fecha_hasta" :config="datePickerConfig" v-validate="'date_format:yyyy-MM-dd HH:mm:ss'" class="flatpickr" :class="{'form-control-danger': errors.has('fecha_hasta'), 'form-control-success': fields.fecha_hasta && fields.fecha_hasta.valid}" id="fecha_hasta" name="fecha_hasta" placeholder="{{ trans('brackets/admin-ui::admin.forms.select_a_date') }}"></datetime>
        </div>
        <div v-if="errors.has('fecha_hasta')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('fecha_hasta') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('observacion_id'), 'has-success': fields.observacion_id && fields.observacion_id.valid }">
    <label for="observacion_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.provincium.columns.observacion_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.observacion_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('observacion_id'), 'form-control-success': fields.observacion_id && fields.observacion_id.valid}" id="observacion_id" name="observacion_id" placeholder="{{ trans('admin.provincium.columns.observacion_id') }}">
        <div v-if="errors.has('observacion_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('observacion_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('geometria_id'), 'has-success': fields.geometria_id && fields.geometria_id.valid }">
    <label for="geometria_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.provincium.columns.geometria_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.geometria_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('geometria_id'), 'form-control-success': fields.geometria_id && fields.geometria_id.valid}" id="geometria_id" name="geometria_id" placeholder="{{ trans('admin.provincium.columns.geometria_id') }}">
        <div v-if="errors.has('geometria_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('geometria_id') }}</div>
    </div>
</div>


