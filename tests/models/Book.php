<?php

use Mpociot\Couchbase\Eloquent\Model as Eloquent;

class Book extends Eloquent
{
    protected $table = 'books';
    protected static $unguarded = true;
    protected $primaryKey = 'title';

    public function author()
    {
        return $this->belongsTo( User::class, 'author_id');
    }
}
