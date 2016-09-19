<?php

namespace Kacademy\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class BadgeCategory extends Eloquent {

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'badge_categories';
}