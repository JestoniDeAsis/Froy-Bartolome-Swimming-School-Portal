<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'tbl_user_profile';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id', 'first_name', 'middle_name', 'last_name', 'name_suffix',
        'birthdate', 'sex', 'phone_number', 'address',
        'parent_guardian_fullname', 'parent_guardian_relationship', 'parent_guardian_phone_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}