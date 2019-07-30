<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Storage;
use App\RolesPermissions;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'name', 'surname', 'password', 'roles', 'avatar', 'subscription', 'reminders',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        return Storage::url('avatars/' . $this->id . '/' . $this->avatar);
    }

    public function isAdmin()
    {
        return isset(json_decode($this->roles)->admin);
    }

    public function getRoles()
    {
        return array_keys(get_object_vars(json_decode($this->roles)));
    }

    public function hasPermission($slug)
    {
        $roles = $this->getRoles();
        $permissions = RolesPermissions::whereIn('role', $roles)->select('permissions')->get();
        foreach ($permissions as $permission) {
            if (isset(json_decode($permission->permissions)->$slug)) {
                return true;
            }
        }
        return false;
    }
}
