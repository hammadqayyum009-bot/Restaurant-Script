<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page()]);
    }

    public function store(Request $request)
    {
        $page = Page::create($this->validated($request));
        $this->activity->created($page, 'page "'.$page->title.'"');

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->validated($request, $page));
        $this->activity->updated($page, 'page "'.$page->title.'"');

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $this->activity->deleted('page "'.$page->title.'"', $page);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);

        return $data;
    }
}
