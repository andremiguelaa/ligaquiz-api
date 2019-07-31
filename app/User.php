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

    protected $fillable = [
        'email', 'name', 'surname', 'password', 'roles', 'avatar', 'subscription', 'reminders',
    ];

    protected $hidden = [
        'password', 'avatar'
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
