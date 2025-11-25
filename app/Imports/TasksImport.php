<?php

namespace App\Imports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\ToModel;

class TasksImport implements ToModel
{
    public function model(array $row)
    {
        return new Task([
            'title'         => $row[0],
            'due_date'      => $row[1],
            'status'        => $row[2],
            'project_id'    => $row[3],
            'assigned_user' => $row[4],
        ]);
    }
}
