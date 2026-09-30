<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['rules_fired' => 'array', 'llm_analysis' => 'array', 'llm_used' => 'boolean'];
}
