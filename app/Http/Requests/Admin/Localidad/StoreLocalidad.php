<?php

namespace App\Http\Requests\Admin\Localidad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreLocalidad extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.localidad.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string'],
            'nombre' => ['nullable', 'string'],
            'aglomerado_id' => ['nullable', 'integer'],
            'tipo_de_localidad_id' => ['nullable', 'integer'],
            'tipo_de_poblacion_id' => ['nullable', 'integer'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'observacion_id' => ['nullable', 'integer'],
            'geometria_id' => ['nullable', 'integer'],
            'cap_de_rep' => ['nullable', 'integer'],
            'cap_de_pcia' => ['nullable', 'integer'],
            'cab_de_depto' => ['nullable', 'integer'],
            'sede_gob_loc' => ['nullable', 'integer'],
            
        ];
    }

    /**
    * Modify input data
    *
    * @return array
    */
    public function getSanitized(): array
    {
        $sanitized = $this->validated();

        //Add your code for manipulation with request data here

        return $sanitized;
    }
}
