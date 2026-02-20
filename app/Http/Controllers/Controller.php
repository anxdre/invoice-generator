<?php

namespace App\Http\Controllers;

use Dentro\Yalr\Attributes\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    #[Post('picture/upload', name: 'picture.upload')]
    function upload(Request $request){
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        $file = $request->file('image');
        $filename = time().'.'.$file->getClientOriginalExtension();

        $file->move(public_path('uploads/image/'.Auth::id().'/'), $filename);

        $url = asset('uploads/image/'.Auth::id().'/'.$filename);

        return response()->json([
            'status' => true,
            'message' => 'Upload berhasil',
            'url' => $url
        ]);
    }
}
