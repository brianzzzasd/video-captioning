<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;
use Str;

class VideoCaptionController extends Controller
{
    /**
     * Process uploading of video
     *
     * @return array
     */
    public function uploadVideo()
    {
    	$path = Storage::putFile('public', request()->file('video'));

    	return response()->json([
    		'media_path' => str_replace('public', 'storage', $path),
    	]);
    }

    /**
     * Process video subtitling
     *
     * @return array
     */
    public function processVideo()
    {
    	$subtitles = request()->get('subtitles');
    	$videoPath = storage_path() . '/app/public/' . request()->get('fileName');
    	$burn = false; // Will use it later
        $hashedFileName = Str::random(10) . '.mp4';
		$path = storage_path() . '/' . $hashedFileName; 

    	$content = '';

    	foreach ($subtitles as $subtitle) {
    		$content .= $subtitle['order'] . "\r\n";
    		$content .= $subtitle['start'] . ' --> ' . $subtitle['end'] . "\r\n";
    		$content .= $subtitle['text'] . "\r\n\r\n";
    	}

    	$subtitlePath = storage_path() . '/' . Str::random(12) . '.srt';
    	$subtitleAss = storage_path() . '/' . Str::random(12) . '.ass';
    	$file = fopen($subtitlePath, 'w');
    	fwrite($file, $content);
    	fclose($file);

    	if (env('OS_TYPE') == "MAC") {
    		// Burn
    		shell_exec('ffmpeg -i ' . $subtitlePath . ' ' . $subtitleAss);
    		shell_exec('ffmpeg -i ' . $videoPath . ' -vf ass=' . $subtitleAss . ' ' . $path);

    		// shell_exec('ffmpeg -i ' . $videoPath . ' -i ' . $subtitlePath . ' -c copy -c:s mov_text ' . $hashedFileName); // Non Burn
    	}

    	return response()->json([
    		'response' => request()->all(),
            'hashed_file_name' => $hashedFileName,
    	]);
    }


    public function downloadFile()
    {
        $fileName = storage_path() . '/' . request()->get('fileName');

        $headers = ['Content-Type' => 'video/mp4'];

        return response()->download($fileName, 'subbed-video.mp4', $headers);
    }
}
