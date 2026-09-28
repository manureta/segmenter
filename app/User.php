<?php

namespace App;

use App\Model\Archivo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'profile_pic'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function visible_files()
    {
       return $this->belongsToMany(Archivo::class, 'file_viewer');
    }
    
    public function mis_files()
    {
      return $this->hasMany(Archivo::class);
    }

    public function getProfilePicURL()
    {
      if($this->profile_pic) {
        return url('storage/'.$this->profile_pic);
      } else {
        return "/images/mandarina.svg";
      }

    }

    /**
     * Provincias asignadas fijo (alcance geográfico restringido, pedido
     * explícito) - asignación manual por un admin, no autoservicio.
     */
    public function provincias()
    {
        return $this->belongsToMany('App\Model\Provincia', 'user_provincia');
    }

    /**
     * Sin ninguna provincia asignada, o Super Admin: sin restricción (ve
     * todo). Con 1+ asignadas: sólo ve esas provincias (y su jerarquía)
     * en los reportes que respeten esto.
     */
    public function tieneAlcanceRestringido()
    {
        return !$this->hasRole('Super Admin') && $this->provincias()->exists();
    }

    /**
     * null = sin restricción (global). Array = códigos de 2 dígitos de
     * las provincias permitidas.
     */
    public function codigosProvinciaPermitidos()
    {
        return $this->tieneAlcanceRestringido()
            ? $this->provincias()->pluck('codigo')->all()
            : null;
    }
}
