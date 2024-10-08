<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function pilotRating()
    {
        return $this->belongsTo(PilotRating::class);
    }
}
