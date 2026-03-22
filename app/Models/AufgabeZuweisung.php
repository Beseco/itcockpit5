<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AufgabeZuweisung extends Model
{
    protected $table = 'aufgaben_zuweisungen';

    protected $fillable = ['aufgabe_id', 'gruppe_id', 'admin_user_id', 'stellvertreter_user_id'];

    public function aufgabe()
    {
        return $this->belongsTo(Aufgabe::class);
    }

    public function gruppe()
    {
        return $this->belongsTo(Gruppe::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function stellvertreter()
    {
        return $this->belongsTo(User::class, 'stellvertreter_user_id');
    }
}
