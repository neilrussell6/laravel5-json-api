<?php namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Neilrussell6\Laravel5JsonApi\Traits\Validatable;

class User extends Authenticatable
{
    use Notifiable, Validatable;

    protected $fillable     = ['name', 'email', 'password'];
    protected $hidden       = ['password', 'remember_token'];

    public $type = 'users';
    public $rules = [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/|confirmed',
    ];
    public $available_includes = ['projects', 'tasks'];
    public $default_includes = ['projects'];

    public function projects ($fields = [])
    {
        return $this->belongsToMany('App\Models\Project')->select($fields);
    }

    public function tasks ($fields = [])
    {
        return $this->belongsToMany('App\Models\Task')->select($fields);
    }
}
