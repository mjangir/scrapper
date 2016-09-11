<?php

namespace Kacademy\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Carbon\Carbon;

class SkillGroup extends Eloquent {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'skill_groups';

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
	public $timestamps = false;
}