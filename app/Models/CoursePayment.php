<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePayment extends Model
{
    protected $table = 'tbl_course_payments';

    protected $primaryKey = 'course_payment_id';

    protected $fillable = [
        'enrollment_id',
        'amount',
        'payment_method',
        'reference_number',
        'paid_at',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    /**
     * enrollment_id on tbl_course_payments is a foreign key to
     * tbl_enrollments.enrollment_id — a payment belongs to a specific
     * course enrollment, since the same person could have separate
     * enrollments (and separate balances) over time.
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}