import AppForm from '../app-components/Form/AppForm';

Vue.component('tipo-de-radio-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                nombre:  '' ,
                descripcion:  '' ,
                
            }
        }
    }

});