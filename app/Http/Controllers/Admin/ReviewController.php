<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    public function index(Request $request)
    {
        $query = Review::query();

        if ($request->query('filter') === 'pending') {
            $query->where('is_approved', false);
        }

        return view('admin.reviews.index', [
            'reviews' => $query->latest()->paginate(20)->withQueryString(),
            'filter' => $request->query('filter'),
            'pendingCount' => Review::where('is_approved', false)->count(),
        ]);
    }

    public function approve(Review $review)
    {
        $review->update(['is_approved' => ! $review->is_approved]);
        $this->activity->log('status',
            ($review->is_approved ? 'Published' : 'Hid').' review by '.$review->name, $review);

        return back()->with('success', $review->is_approved ? 'Review published.' : 'Review hidden.');
    }

    public function destroy(Review $review)
    {
        $this->activity->deleted('review by '.$review->name, $review);
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
