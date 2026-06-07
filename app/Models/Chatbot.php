<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\FormatsDates;

class Chatbot extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */

    use FormatsDates;
    protected $table = 'chatbot';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'role',
        'content',
    ];

    /**
     * Get the user that owns the chatbot message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
