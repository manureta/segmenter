import AppForm from '../app-components/Form/AppForm';

Vue.component('localidad-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                codigo:  '' ,
                nombre:  '' ,
                aglomerado_id:  '' ,
                tipo_de_localidad_id:  '' ,
                tipo_de_poblacion_id:  '' ,
                fecha_desde:  '' ,
                fecha_hasta:  '' ,
                observacion_id:  '' ,
                geometria_id:  '' ,
                cap_de_rep:  '' ,
                cap_de_pcia:  '' ,
                cab_de_depto:  '' ,
                sede_gob_loc:  '' ,
                
            }
        }
    }

});