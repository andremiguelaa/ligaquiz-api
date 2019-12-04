<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Storage;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'email', 'name', 'surname', 'password', 'roles', 'avatar', 'subscription', 'reminders',
    ];

    protected $hidden = [
        'password', 'avatar', 'created_at', 'updated_at'
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::url('avatars/' . $this->avatar . '?' . strtotime($this->updated_at));
        }

        return null;
    }

    public function isAdmin()
    {
        return isset(json_decode($this->roles)->admin);
    }

    public function getRoles()
    {
        if ($this->roles) {
            return array_keys(get_object_vars(json_decode($this->roles)));
        }

        return [];
    }

    public function hasPermission($slug)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $roles = $this->getRoles();
        $rolePermissions = RolesPermissions::whereIn('role', $roles)->select('permissions')->get();
        foreach ($rolePermissions as $permission) {
            if (isset(json_decode($permission->permissions)->$slug)) {
                return true;
            }
        }

        return false;
    }
}
