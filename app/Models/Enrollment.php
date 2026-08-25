<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = 'tbl_enrollments';

    protected $primaryKey = 'enrollment_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'course_id',
        'course_price_at_enrollment',
        'enrollment_at',
        'graduation_at',
        'scheduled_time',
        'notes',
    ];

    protected $casts = [
        'course_price_at_enrollment' => 'float',
        'enrollment_at' => 'datetime',
        'graduation_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function payments()
    {
        return $this->hasMany(CoursePayment::class, 'enrollment_id', 'enrollment_id');
    }
}