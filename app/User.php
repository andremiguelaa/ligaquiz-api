<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Storage;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'email',
        'name',
        'surname',
        'password',
        'roles',
        'avatar',
        'region',
        'reminders',
    ];

    protected $hidden = [
        'password', 'avatar', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'roles' => 'array',
        'reminders' => 'array',
    ];

    protected $appends = ['avatar_url', 'valid_roles'];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::url('avatars/' . $this->avatar . '?' . strtotime($this->updated_at));
        }

        return null;
    }

    public function getValidRolesAttribute()
    {
        return (object) array_reduce($this->getRoles(), function($carry, $item) {
            $carry[$item] = true;
            return $carry;
        }, []);
    }

    public function individual_quiz_player()
    {
        return $this->hasOne('App\IndividualQuizPlayer');
    }

    public function isAdmin()
    {
        return isset($this->roles['admin']);
    }

    public function isBlocked()
    {
        return isset($this->roles['blocked']);
    }

    public function getRoles()
    {
        if ($this->roles) {
            return array_keys(array_filter($this->roles, function ($roleValue) {
                if (
                    $roleValue === true ||
                    Carbon::now()->lessThanOrEqualTo(
                        Carbon::createFromFormat('Y-m-d', $roleValue)->endOfDay()
                    )
                ) {
                    return true;
                }
                return false;
            }));
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

    public function hasRegion()
    {
        return boolval($this->region);
    }
}
