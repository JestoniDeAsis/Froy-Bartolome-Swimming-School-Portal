<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSkill extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_course_skills';

    protected $primaryKey = 'course_skill_id';

    public $timestamps = false;

    protected $fillable = [
        'course_category_id',
        'course_skill_name',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id', 'course_category_id');
    }
}