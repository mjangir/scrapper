<?php

namespace Kacademy\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Carbon\Carbon;

class Subject extends Eloquent {

	/**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $fillable = ['title','slug','ka_url'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = true;

    /**
     * Class constructor
     *
     * @return void
     */
   
    /**
     * Children
     *
     * @return collection
     */
    public function children()
    {        
        return $this->hasMany(Subject::class,'parent_id');
    }

    /**
     * Parents
     *
     * @return collection
     */
    public function parent()
    {
        return $this->belongsTo(Subject::class,'parent_id');
    }
}