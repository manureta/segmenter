<div class="form-group row align-items-center" :class="{'has-danger': errors.has('codigo'), 'has-success': fields.codigo && fields.codigo.valid }">
    <label for="codigo" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.codigo') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.codigo" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('codigo'), 'form-control-success': fields.codigo && fields.codigo.valid}" id="codigo" name="codigo" placeholder="{{ trans('admin.localidad.columns.codigo') }}">
        <div v-if="errors.has('codigo')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('codigo') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('nombre'), 'has-success': fields.nombre && fields.nombre.valid }">
    <label for="nombre" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.nombre') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.nombre" v-validate="''" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('nombre'), 'form-control-success': fields.nombre && fields.nombre.valid}" id="nombre" name="nombre" placeholder="{{ trans('admin.localidad.columns.nombre') }}">
        <div v-if="errors.has('nombre')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('nombre') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('aglomerado_id'), 'has-success': fields.aglomerado_id && fields.aglomerado_id.valid }">
    <label for="aglomerado_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.aglomerado_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.aglomerado_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('aglomerado_id'), 'form-control-success': fields.aglomerado_id && fields.aglomerado_id.valid}" id="aglomerado_id" name="aglomerado_id" placeholder="{{ trans('admin.localidad.columns.aglomerado_id') }}">
        <div v-if="errors.has('aglomerado_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('aglomerado_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('tipo_de_localidad_id'), 'has-success': fields.tipo_de_localidad_id && fields.tipo_de_localidad_id.valid }">
    <label for="tipo_de_localidad_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.tipo_de_localidad_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.tipo_de_localidad_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('tipo_de_localidad_id'), 'form-control-success': fields.tipo_de_localidad_id && fields.tipo_de_localidad_id.valid}" id="tipo_de_localidad_id" name="tipo_de_localidad_id" placeholder="{{ trans('admin.localidad.columns.tipo_de_localidad_id') }}">
        <div v-if="errors.has('tipo_de_localidad_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('tipo_de_localidad_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('tipo_de_poblacion_id'), 'has-success': fields.tipo_de_poblacion_id && fields.tipo_de_poblacion_id.valid }">
    <label for="tipo_de_poblacion_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.tipo_de_poblacion_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.tipo_de_poblacion_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('tipo_de_poblacion_id'), 'form-control-success': fields.tipo_de_poblacion_id && fields.tipo_de_poblacion_id.valid}" id="tipo_de_poblacion_id" name="tipo_de_poblacion_id" placeholder="{{ trans('admin.localidad.columns.tipo_de_poblacion_id') }}">
        <div v-if="errors.has('tipo_de_poblacion_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('tipo_de_poblacion_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('fecha_desde'), 'has-success': fields.fecha_desde && fields.fecha_desde.valid }">
    <label for="fecha_desde" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.fecha_desde') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <div class="input-group input-group--custom">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <datetime v-model="form.fecha_desde" :config="datetimePickerConfig" v-validate="'date_format:yyyy-MM-dd HH:mm:ss'" class="flatpickr" :class="{'form-control-danger': errors.has('fecha_desde'), 'form-control-success': fields.fecha_desde && fields.fecha_desde.valid}" id="fecha_desde" name="fecha_desde" placeholder="{{ trans('brackets/admin-ui::admin.forms.select_date_and_time') }}"></datetime>
        </div>
        <div v-if="errors.has('fecha_desde')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('fecha_desde') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('fecha_hasta'), 'has-success': fields.fecha_hasta && fields.fecha_hasta.valid }">
    <label for="fecha_hasta" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.fecha_hasta') }}</label>
    <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <div class="input-group input-group--custom">
            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
            <datetime v-model="form.fecha_hasta" :config="datetimePickerConfig" v-validate="'date_format:yyyy-MM-dd HH:mm:ss'" class="flatpickr" :class="{'form-control-danger': errors.has('fecha_hasta'), 'form-control-success': fields.fecha_hasta && fields.fecha_hasta.valid}" id="fecha_hasta" name="fecha_hasta" placeholder="{{ trans('brackets/admin-ui::admin.forms.select_date_and_time') }}"></datetime>
        </div>
        <div v-if="errors.has('fecha_hasta')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('fecha_hasta') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('observacion_id'), 'has-success': fields.observacion_id && fields.observacion_id.valid }">
    <label for="observacion_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.observacion_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.observacion_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('observacion_id'), 'form-control-success': fields.observacion_id && fields.observacion_id.valid}" id="observacion_id" name="observacion_id" placeholder="{{ trans('admin.localidad.columns.observacion_id') }}">
        <div v-if="errors.has('observacion_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('observacion_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('geometria_id'), 'has-success': fields.geometria_id && fields.geometria_id.valid }">
    <label for="geometria_id" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.geometria_id') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.geometria_id" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('geometria_id'), 'form-control-success': fields.geometria_id && fields.geometria_id.valid}" id="geometria_id" name="geometria_id" placeholder="{{ trans('admin.localidad.columns.geometria_id') }}">
        <div v-if="errors.has('geometria_id')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('geometria_id') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('cap_de_rep'), 'has-success': fields.cap_de_rep && fields.cap_de_rep.valid }">
    <label for="cap_de_rep" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.cap_de_rep') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.cap_de_rep" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('cap_de_rep'), 'form-control-success': fields.cap_de_rep && fields.cap_de_rep.valid}" id="cap_de_rep" name="cap_de_rep" placeholder="{{ trans('admin.localidad.columns.cap_de_rep') }}">
        <div v-if="errors.has('cap_de_rep')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('cap_de_rep') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('cap_de_pcia'), 'has-success': fields.cap_de_pcia && fields.cap_de_pcia.valid }">
    <label for="cap_de_pcia" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.cap_de_pcia') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.cap_de_pcia" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('cap_de_pcia'), 'form-control-success': fields.cap_de_pcia && fields.cap_de_pcia.valid}" id="cap_de_pcia" name="cap_de_pcia" placeholder="{{ trans('admin.localidad.columns.cap_de_pcia') }}">
        <div v-if="errors.has('cap_de_pcia')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('cap_de_pcia') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('cab_de_depto'), 'has-success': fields.cab_de_depto && fields.cab_de_depto.valid }">
    <label for="cab_de_depto" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.cab_de_depto') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.cab_de_depto" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('cab_de_depto'), 'form-control-success': fields.cab_de_depto && fields.cab_de_depto.valid}" id="cab_de_depto" name="cab_de_depto" placeholder="{{ trans('admin.localidad.columns.cab_de_depto') }}">
        <div v-if="errors.has('cab_de_depto')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('cab_de_depto') }}</div>
    </div>
</div>

<div class="form-group row align-items-center" :class="{'has-danger': errors.has('sede_gob_loc'), 'has-success': fields.sede_gob_loc && fields.sede_gob_loc.valid }">
    <label for="sede_gob_loc" class="col-form-label text-md-right" :class="isFormLocalized ? 'col-md-4' : 'col-md-2'">{{ trans('admin.localidad.columns.sede_gob_loc') }}</label>
        <div :class="isFormLocalized ? 'col-md-4' : 'col-md-9 col-xl-8'">
        <input type="text" v-model="form.sede_gob_loc" v-validate="'integer'" @input="validate($event)" class="form-control" :class="{'form-control-danger': errors.has('sede_gob_loc'), 'form-control-success': fields.sede_gob_loc && fields.sede_gob_loc.valid}" id="sede_gob_loc" name="sede_gob_loc" placeholder="{{ trans('admin.localidad.columns.sede_gob_loc') }}">
        <div v-if="errors.has('sede_gob_loc')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('sede_gob_loc') }}</div>
    </div>
</div>


