<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'tbl_announcements';

    protected $primaryKey = 'announcement_id';

    // Reuses Eloquent's automatic timestamping, just pointed at the
    // project's existing column names instead of created_at/updated_at.
    const CREATED_AT = 'announcement_posted_at';
    const UPDATED_AT = 'announcement_updated_at';

    protected $fillable = [
        'announcement_title',
        'announcement_message',
        'announcement_recipients_id',
    ];

    protected $casts = [
        'announcement_recipients_id' => 'integer',
    ];
}