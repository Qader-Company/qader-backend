<?php

namespace App\Modules\V1\HelpRequests\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpRequestNote extends Model
{
    public $timestamps = false;

    protected $fillable = ['help_request_id', 'admin_id', 'body', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function helpRequest(): BelongsTo
    {
        return $this->belongsTo(HelpRequest::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
