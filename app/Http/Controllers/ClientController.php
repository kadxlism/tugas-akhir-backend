<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource with pagination.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 5); // Default 5 items per page
        $page = $request->get('page', 1);
        $search = $request->get('search', '');

        $query = Client::orderBy('created_at', 'desc'); // Klien terbaru di atas

        // Tambahkan search jika ada
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('owner', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('package', 'LIKE', "%{$search}%");
            });
        }

        $clients = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $clients->items(),
            'current_page' => $clients->currentPage(),
            'last_page' => $clients->lastPage(),
            'per_page' => $clients->perPage(),
            'total' => $clients->total(),
            'from' => $clients->firstItem(),
            'to' => $clients->lastItem(),
            'search' => $search, // Return search term untuk frontend
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'package' => 'required|string|max:100',
            'deadline' => 'required|date',
            'dp' => 'required|string|max:100',
            'category' => 'required|string|max:100',
        ]);

        $client = Client::create($data);
        return response()->json($client, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'owner' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'package' => 'sometimes|string|max:100',
            'deadline' => 'sometimes|date',
            'dp' => 'sometimes|string|max:100',
            'category' => 'sometimes|string|max:100',
        ]);

        $client->update($data);
        return response()->json($client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}
