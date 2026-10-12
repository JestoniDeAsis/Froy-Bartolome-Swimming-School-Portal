<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of tbl_skill_assessments: the coach's assessment of ONE enrollment.
 * The per-skill Pass/Failed marks live in tbl_skill_assessment_results.
 *
 * ready_to_graduate lets a coach mark a student as eligible to graduate even
 * when not every skill was passed.
 */
class SkillAssessment extends Model
{
    protected $table = 'tbl_skill_assessments';

    protected $primaryKey = 'skill_assessment_id';

    // assessed_at is set by the database default; updated_at is set in the controller.
    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'assessed_by',
        'ready_to_graduate',
        'updated_at',
    ];

    protected $casts = [
        'ready_to_graduate' => 'boolean',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    /** The coach who last saved this assessment. */
    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by', 'user_id');
    }

    public function results()
    {
        return $this->hasMany(SkillAssessmentResult::class, 'skill_assessment_id', 'skill_assessment_id');
    }
}