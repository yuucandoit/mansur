<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::orderBy('name')->get();

        return view('admin.settings.index', compact('settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Setting::create($data);

        return back()->with('status', 'Setting created.');
    }

    public function update(Request $request, Setting $setting): RedirectResponse
    {
        $data = $this->validated($request);

        $setting->update($data);

        return back()->with('status', 'Setting updated.');
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        $setting->delete();

        return back()->with('status', 'Setting deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'  => 'required|string|max:50',
            'slug'  => 'required|string|max:50',
            'type'  => 'required|string|in:string,text,boolean,number,json',
            'value' => 'nullable|string',
        ]);
    }
}
