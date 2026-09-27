<?php

namespace App\Policies;

use App\Model\Operativo;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OperativoPolicy
{
    use HandlesAuthorization;

    /**
     * Cualquiera autenticado puede pedir el listado - el filtrado de
     * cuáles ve realmente pasa por la query del controller (propios +
     * públicos + todos si tiene "Administrar Operativos"), no acá.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Ver el detalle: dueño, público, o con permiso de administrar.
     */
    public function view(User $user, Operativo $operativo)
    {
        return $operativo->es_publico
            || $operativo->user_id === $user->id
            || $user->can('Administrar Operativos');
    }

    /**
     * Cualquiera autenticado puede crear el suyo - nace privado
     * (es_publico=false por default, ver migración).
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Editar: dueño, o con permiso de administrar (no alcanza con que sea
     * público - ser público solo da derecho a VER, no a editar).
     */
    public function update(User $user, Operativo $operativo)
    {
        return $operativo->user_id === $user->id || $user->can('Administrar Operativos');
    }

    public function delete(User $user, Operativo $operativo)
    {
        return $this->update($user, $operativo);
    }

    /**
     * Pasar de privado a público: hace falta ser dueño Y tener el permiso
     * explícito - o tener "Administrar Operativos" (puede publicar
     * cualquiera, no solo el propio).
     */
    public function publicar(User $user, Operativo $operativo)
    {
        return ($operativo->user_id === $user->id && $user->can('Publicar Operativos'))
            || $user->can('Administrar Operativos');
    }
}
