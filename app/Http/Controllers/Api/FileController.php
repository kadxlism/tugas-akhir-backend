<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(Project $project)
    {
        return $project->files()->get();
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xlsx,png,jpg,jpeg,zip',
        ]);

        $path = $request->file('file')->store('project_files');

        $file = $project->files()->create([
            'uploaded_by' => $request->user()->id,
            'filename' => $request->file('file')->getClientOriginalName(),
            'url' => $path,
            'uploaded_at' => now(),
        ]);

        return response()->json($file);
    }
}
