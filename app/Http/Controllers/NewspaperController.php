<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNewspaperRequest;
use App\Models\Image;
use App\Models\Newspaper;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class NewspaperController extends Controller
{
    private Newspaper $newspaperModel;
    private Image $imageModel;
    private $redis;

    public function __construct(Image $imageModel, Newspaper $newspaperModel)
    {
        $this->newspaperModel = $newspaperModel;
        $this->imageModel = $imageModel;
        $this->redis = Redis::connection();
    }

    public function getNewspaper($id)
    {
        $newspaper_from_redis = $this->redis->get('newspaper:' . $id);
        if (!$newspaper_from_redis) {
            $newspapers = $this->newspaperModel->getOneNewspaper($id);
            $this->redis->set('newspaper:' . $id, json_encode([
                'data' => $newspapers,
            ]));
            return response()->json(['data' => $newspapers], 200);
        }
        return response()->json(['response' => json_decode($newspaper_from_redis)], 200);
    }

    public function createNewspaper(CreateNewspaperRequest $request)
    {
        $newspaper = [];
        $newspaper['title'] = $request['title'];
        $newspaper['metadata'] = json_encode(['metadata' => $request['metadata']]);
        $newspaper['content'] = json_encode(['content' => $request['content']]);
        $image = $request->file('image');
        $index = $this->newspaperModel->createNewspaper($newspaper);

        $path_image = "images/" . $index . "/original/" . $image->getBasename();
        $path = Storage::disk('s3')->put($path_image, $image);

        $image_DB = [];
        $image_DB['order'] = 1;
        $image_DB['key'] = $path;
        $image_DB['newspaper_id'] = $index;
        $this->imageModel->createImage($image_DB);

        $newspaper['image_key'] = $path;
        $newspaper['id'] = $index;

        $this->redis->set('newspaper:' . $index, json_encode([
            'data' => $newspaper,
        ]));

        return response()->json(['response' => 'Create a newspaper success', 'newspaper'=> $newspaper], 201);
    }
}
