<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'tbl_courses';

    protected $primaryKey = 'course_id';

    // No created_at/updated_at columns on tbl_courses.
    public $timestamps = false;

    protected $fillable = [
        'course_name',
        'course_session_count',
        'course_price',
    ];

    protected $casts = [
        'course_session_count' => 'integer',
        'course_price'         => 'float',
    ];

    /**
     * Active (non-archived) categories only.
     */
    public function activeCategories()
    {
        return $this->hasMany(CourseCategory::class, 'course_id', 'course_id');
    }

    /**
     * Every category, including archived ones — the edit modal shows
     * archived categories grayed out rather than hiding them.
     */
    public function allCategories()
    {
        return $this->hasMany(CourseCategory::class, 'course_id', 'course_id')->withTrashed();
    }
}