<?php

namespace App\Repositories\NewsOffer;

use App\Models\Backend\NewsOffer;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\Auth;

class NewsOfferRepository implements NewsOfferInterface
{
    // get all NewsOffer
    public function all()
    {
        return NewsOffer::with('upload')->orderByDesc('id')->paginate(10);
    }

    // get single row in NewsOffer
    public function get($id)
    {
        return NewsOffer::with('upload')->find($id);
    }

    // All request data store in NewsOffer tabel.
    public function store($request)
    {

        try {
            $news_offer = new NewsOffer;
            $news_offer->author = Auth::user()->id;
            $news_offer->title = $request->title;
            $news_offer->description = $request->description;
            $news_offer->file = $this->file('', $request->file);
            $news_offer->status = $request->status;
            $news_offer->date = $request->date;
            $news_offer->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // All request data update in NewsOffer tabel.
    public function update($id, $request)
    {
        try {

            $news_offer = NewsOffer::find($id);
            $news_offer->author = Auth::user()->id;
            $news_offer->title = $request->title;
            $news_offer->description = $request->description;
            if (isset($request->file) && $request->file != null) {
                $news_offer->file = $this->file($news_offer->file, $request->file);
            }
            $news_offer->status = $request->status;
            $news_offer->date = $request->date;
            $news_offer->save();

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    // Delete single row in NewsOffer Model
    public function delete($id)
    {
        try {
            $news_offer = NewsOffer::with('upload')->find($id);
            Upload::destroy($news_offer->upload->id);
            if (file_exists($news_offer->upload->original)) {
                unlink($news_offer->upload->original);
            }
            $news_offer->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Image Store in Upload Model
    public function file($file_id, $file)
    {
        try {
            $file_name = '';
            if (! blank($file)) {
                // Validate file
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                $maxSize = 2048; // 2MB in KB

                $extension = strtolower($file->getClientOriginalExtension());
                $size = $file->getSize() / 1024; // Size in KB

                if (! in_array($extension, $allowedExtensions)) {
                    throw new \Exception('Invalid file type. Allowed: '.implode(', ', $allowedExtensions));
                }

                if ($size > $maxSize) {
                    throw new \Exception('File size too large. Max: '.$maxSize.'KB');
                }

                $destinationPath = public_path('uploads/news_offers');
                $profileImage = date('YmdHis').'.'.$extension;
                $file->move($destinationPath, $profileImage);
                $file_name = 'uploads/news_offers/'.$profileImage;
            }

            if (blank($file_id)) {
                $upload = new Upload;
            } else {
                $upload = Upload::find($file_id);
                if (file_exists($upload->original)) {
                    unlink($upload->original);
                }
            }
            $upload->original = $file_name;
            $upload->save();

            return $upload->id;

        } catch (\Exception $e) {
            return false;
        }
    }
}
