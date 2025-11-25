<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Comment;

class CommentController extends Controller
{
    public function index(Task $task)
    {
        return $task->comments()->with('user')->get();
    }

    public function store(Request $request, Task $task)
    {
        $data = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        return response()->json($comment);
    }
}