<?php

namespace Viancen\LaravelDbLogger\Models;

use Illuminate\Database\Eloquent\Model;

class LogEntry extends Model
{
    public $timestamps = false; // we set created_at ourselves

    protected $fillable = [
        'level', 'channel', 'message', 'context', 'extra',
        'request_id', 'ip_address', 'user_agent', 'user_id', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'context' => 'array',
        'extra' => 'array',
        'request_id' => 'string', // UUID
    ];

    //Override getTable() method
    public function getTable()
    {
        return config('db-logger.table', 'logs');
    }
}
