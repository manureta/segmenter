import AppForm from '../app-components/Form/AppForm';

Vue.component('provincium-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                codigo:  '' ,
                nombre:  '' ,
                fecha_desde:  '' ,
                fecha_hasta:  '' ,
                observacion_id:  '' ,
                geometria_id:  '' ,
                
            }
        }
    }

});