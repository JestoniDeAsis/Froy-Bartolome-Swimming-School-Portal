<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseCategory extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_course_categories';

    protected $primaryKey = 'course_category_id';

    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'course_category_name',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /** Active skills only. */
    public function skills()
    {
        return $this->hasMany(CourseSkill::class, 'course_category_id', 'course_category_id');
    }

    /** Every skill, including archived ones. */
    public function allSkills()
    {
        return $this->hasMany(CourseSkill::class, 'course_category_id', 'course_category_id')->withTrashed();
    }
}