<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class News extends Model
{
    use HasFactory;
    use Searchable;

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'sourceId',
        'sourceName',
        'author',
        'title',
        'description',
        'summary',
        'url',
        'urlToImage',
        'publishedAt',
        'content',
    ];

    public function toSearchableArray()
    {
        return [
            'sourceName' => $this->sourceName,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
