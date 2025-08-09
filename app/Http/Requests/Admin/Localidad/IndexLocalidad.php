<?php

namespace App\Http\Requests\Admin\Localidad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexLocalidad extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.localidad.index');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'orderBy' => 'in:id,codigo,nombre,aglomerado_id,tipo_de_localidad_id,tipo_de_poblacion_id,fecha_desde,fecha_hasta,observacion_id,geometria_id,cap_de_rep,cap_de_pcia,cab_de_depto,sede_gob_loc|nullable',
            'orderDirection' => 'in:asc,desc|nullable',
            'search' => 'string|nullable',
            'page' => 'integer|nullable',
            'per_page' => 'integer|nullable',

        ];
    }
}
