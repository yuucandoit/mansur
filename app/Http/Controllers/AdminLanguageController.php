<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLanguageController extends Controller
{
    public function index(): View
    {
        $languages = Language::orderBy('name')->get();

        return view('admin.languages.index', compact('languages'));
    }

    public function store(Request $request): RedirectResponse
    {
        Language::create($this->validated($request));

        return back()->with('status', 'Language added.');
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $language->update($this->validated($request));

        return back()->with('status', 'Language updated.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return back()->with('status', 'Language deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:5',
            'name' => 'required|string|max:20',
        ]);
    }
}
