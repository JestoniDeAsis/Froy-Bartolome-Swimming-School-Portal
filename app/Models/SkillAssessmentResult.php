<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of tbl_skill_assessment_results: 'pass' or 'failed' for one skill.
 */
class SkillAssessmentResult extends Model
{
    public const PASS = 'pass';
    public const FAILED = 'failed';

    protected $table = 'tbl_skill_assessment_results';

    protected $primaryKey = 'skill_assessment_result_id';

    public $timestamps = false;

    protected $fillable = [
        'skill_assessment_id',
        'course_skill_id',
        'result',
    ];

    public function assessment()
    {
        return $this->belongsTo(SkillAssessment::class, 'skill_assessment_id', 'skill_assessment_id');
    }

    public function skill()
    {
        // Archived skills still keep their old results.
        return $this->belongsTo(CourseSkill::class, 'course_skill_id', 'course_skill_id')->withTrashed();
    }
}