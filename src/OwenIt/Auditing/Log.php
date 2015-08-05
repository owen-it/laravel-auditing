<?php

namespace OwenIt\Revisionable;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    public $table = 'logs';

    public function log()
    {
        return $this->morphTo();
    }

    public function oldValue()
    {
        return $this->getValue('old');
    }

    public function newValue()
    {
        return $this->getValue('new');
    }
}
