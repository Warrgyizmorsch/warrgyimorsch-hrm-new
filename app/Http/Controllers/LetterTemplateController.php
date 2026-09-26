<?php

namespace App\Http\Controllers;

use App\Models\LetterTemplate;
use Illuminate\Http\Request;

class LetterTemplateController extends Controller
{
    public function index()
    {
        $templates = LetterTemplate::orderBy('type')->orderBy('title')->get();

        return view('master.letter-templates', [
            'templates' => $templates,
            'totalCount' => LetterTemplate::count(),
            'activeCount' => LetterTemplate::where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        LetterTemplate::create($data);

        return redirect()->route('master.letter-templates')->with('success', 'Letter template added successfully!');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $template = LetterTemplate::findOrFail($id);
        $template->update($data + ['is_active' => $request->has('is_active')]);

        return redirect()->route('master.letter-templates')->with('success', 'Letter template updated successfully!');
    }

    public function destroy($id)
    {
        LetterTemplate::findOrFail($id)->delete();

        return redirect()->route('master.letter-templates')->with('success', 'Letter template deleted successfully!');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(LetterTemplate::TYPES)),
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
    }
}
