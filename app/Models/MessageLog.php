<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $guarded = [];
    public function registration() { return $this->belongsTo(Registration::class); }
    public function automation() { return $this->belongsTo(Automation::class); }
}
