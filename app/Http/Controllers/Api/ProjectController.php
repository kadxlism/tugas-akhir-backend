<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::with(['client', 'clientData'])->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'client_id' => 'nullable|exists:users,id', // User ID (optional for backward compatibility)
                'client_table_id' => 'nullable|exists:clients,id', // Client table ID
                'status' => 'nullable|in:active,completed,archived',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'description' => 'nullable|string',
            ]);

            // Build data array explicitly to avoid unexpected fields
            $data = [
                'name' => $validated['name'],
                'client_id' => $validated['client_id'] ?? Auth::id(),
                'client_table_id' => $validated['client_table_id'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'description' => $validated['description'] ?? null,
            ];

            // If client_table_id is provided, auto-fill from client data
            if (!empty($data['client_table_id'])) {
                $client = Client::find($data['client_table_id']);
                if ($client && empty($data['name']) && $client->company_name) {
                    $data['name'] = $client->company_name . ' - Project';
                }
            }

            $project = Project::create($data);

            return response()->json($project->load(['client', 'clientData']), 201);
        } catch (\Throwable $e) {
            \Log::error('Error creating project', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Gagal membuat project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Project $project)
    {
        return $project->load(['client', 'clientData', 'tasks']);
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'client_id' => 'nullable|exists:users,id',
            'client_table_id' => 'nullable|exists:clients,id',
            'status' => 'sometimes|in:active,completed,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $project->update($request->all());
        return response()->json($project->load(['client', 'clientData']));
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Get projects by client_table_id
     */
    public function getByClient($clientId)
    {
        $projects = Project::where('client_table_id', $clientId)
            ->with(['client', 'clientData', 'tasks'])
            ->get();

        return response()->json($projects);
    }
}
