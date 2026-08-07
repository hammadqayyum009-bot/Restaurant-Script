<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ThrottlesPublicSubmissions;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ThrottlesPublicSubmissions;

    public function store(Request $request)
    {
        if (! config('shop.reviews_enabled')) {
            return back()->with('error', 'Reviews are turned off at the moment.')->withFragment('reviews');
        }

        $this->ensurePublicSubmissionIsNotRateLimited($request, 'name', 'reviews');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:600'],
        ]);

        $data['is_approved'] = (bool) config('shop.reviews_auto_approve');

        Review::create($data);

        return back()->with(
            'success',
            $data['is_approved']
                ? 'Thank you for your review!'
                : 'Thank you! Your review will appear once we have checked it.'
        )->withFragment('reviews');
    }
}
