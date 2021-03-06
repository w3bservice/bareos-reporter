<?php
/**
 * Bareos Reporter
 * Application for managing Bareos Backup Email Reports
 *
 * Directors Model
 *
 * @license The MIT License (MIT) See: LICENSE file
 * @copyright Copyright (c) 2016 Matt Clinton
 * @author Matt Clinton <matt@laralabs.uk>
 * @website http://www.laralabs.uk/
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Directors extends Model
{
    /**
     * The table associated to model
     *
     * @var string
     */
    protected $table = 'directors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'director_name', 'ip_address', 'director_port', 'catalog_id'
    ];

    public static function find($id) {

        $directors = Directors::where('id', $id)->get();

        foreach($directors as $director)
        {
            return $director;
        }
    }

    public static function getDirectorName($id)
    {
        $director = Directors::find($id);

        if($director != null) {
            return $director->director_name;
        }else{
            return '';
        }
    }
}
