<?php

namespace App\Http\Controllers;

use App\Models\Email_Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = Email_Template::orderBy('name')->get();

        return view('admin.email-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.email-templates.form', ['template' => new Email_Template()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Email_Template::create($this->validated($request));

        return redirect()->route('admin.email-templates.index')
            ->with('status', 'Email template created.');
    }

    public function edit(Email_Template $emailTemplate): View
    {
        return view('admin.email-templates.form', ['template' => $emailTemplate]);
    }

    public function update(Request $request, Email_Template $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($this->validated($request));

        return redirect()->route('admin.email-templates.index')
            ->with('status', 'Email template updated.');
    }

    public function destroy(Email_Template $emailTemplate): RedirectResponse
    {
        $emailTemplate->delete();

        return back()->with('status', 'Email template deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'     => 'required|string|max:50',
            'details'  => 'nullable|string|max:200',
            'slug'     => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'html'     => 'nullable|string',
        ]);
    }
}
