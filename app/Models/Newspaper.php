<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class Newspaper extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    static public function getOneNewspaper($id)
    {
        $newspaper = DB::table('newspapers')->select('newspapers.id', 'newspapers.content', 'newspapers.title', 'newspapers.metadata', 'newspapers.created_at', 'images.order', 'images.key')->where('newspapers.id', $id)->leftJoin('images', function ($query) {
            $query->on('newspapers.id', '=', 'images.newspaper_id');
        })->get();

        return [
            'newspaper_content' => $newspaper,
        ];
    }

    static public function createNewspaper(array $data){
        return DB::table('newspapers')->insertGetId($data);
    }
}
