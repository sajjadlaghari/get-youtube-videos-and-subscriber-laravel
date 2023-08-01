<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\Youtube;
use Exception;
use Illuminate\Http\Request;

class YoutubeController extends Controller
{
    public function index()
    {
        $videos = Youtube::orderBy('created_at','DESC')->get();
        $subscribers = Subscriber::orderBy('created_at','DESC')->first();

        return view('home',['videos' => $videos,'subscribers' =>$subscribers]);
    }

    public function get_videos()
    {

        $channel_id = "UCMblgAicMZolovhIsvyNE2Q";
        $api_key = "AIzaSyBXsVM_joOuetTsu-QVTXhutozG9Q2E7Sk";
        $maxResult = 1000;

        $api_response = file_get_contents('https://www.googleapis.com/youtube/v3/channels?part=statistics&id='.$channel_id.'&fields=items/statistics/subscriberCount&key='.$api_key);
        $subscribersData = json_decode($api_response, true);

        $apiData = @file_get_contents('https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId=' . $channel_id . '&maxResults=' . $maxResult . '&key=' . $api_key . '');
        $channelName = json_decode($apiData);
        

        Subscriber::whereNotNull('id')->delete();
        $subscriber = new Subscriber();
        $subscriber->page_name = $channelName->items[0]->snippet->channelTitle;
        $subscriber->subscribers = $subscribersData['items'][0]['statistics']['subscriberCount'];
        $subscriber->save();

   
        try {

            if ($apiData) {
                $videoList = json_decode($apiData);
                Youtube::whereNotNull('id')->delete();
                foreach($videoList->items as $key => $item)
                {
                    $video = new Youtube();

                    $video->channelTitle   = $item->snippet->channelTitle;
                    $video->title          = $item->snippet->title;
                    $video->description    = $item->snippet->description;
                    $video->videoId        = $item->id->videoId ?? '';
                    $video->url            = 'https://www.youtube.com/watch?v='.$item->id->videoId;
                    $video->publishedAt    = $item->snippet->publishedAt;
                    $video->save();
                }
                return redirect()->route('home');
            } else {
                throw new Exception('Invalid API key or channel ID.');
            }
        } catch (Exception $e) {
            $apiError = $e->getMessage();
            return redirect()->route('home');

        }

    }
}
