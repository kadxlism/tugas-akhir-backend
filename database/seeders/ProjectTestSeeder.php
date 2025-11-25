<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;

class ProjectTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users by role
        $admin = User::where('role', 'admin')->first();
        $pm = User::where('role', 'pm')->first();
        $team = User::where('role', 'team')->first();
        $client = User::where('role', 'client')->first();

        if (!$admin || !$pm || !$team || !$client) {
            $this->command->error('Please run RoleTestSeeder first');
            return;
        }

        // Create projects
        $project1 = Project::create([
            'name' => 'E-commerce Website',
            'client_id' => $client->id,
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(30),
            'description' => 'A comprehensive e-commerce platform with modern features',
        ]);

        $project2 = Project::create([
            'name' => 'Mobile App Development',
            'client_id' => $client->id,
            'status' => 'in_progress',
            'start_date' => now()->subDays(15),
            'end_date' => now()->addDays(45),
            'description' => 'Cross-platform mobile application for iOS and Android',
        ]);

        $project3 = Project::create([
            'name' => 'Data Analytics Dashboard',
            'client_id' => $client->id,
            'status' => 'completed',
            'start_date' => now()->subDays(60),
            'end_date' => now()->subDays(10),
            'description' => 'Real-time analytics dashboard for business intelligence',
        ]);

        // Assign users to projects
        $project1->users()->attach([$pm->id, $team->id]);
        $project2->users()->attach([$pm->id, $team->id]);
        $project3->users()->attach([$pm->id, $team->id]);

        // Create tasks for projects
        $this->createTasksForProject($project1, $team);
        $this->createTasksForProject($project2, $team);
        $this->createTasksForProject($project3, $team);
    }

    private function createTasksForProject(Project $project, User $teamMember)
    {
        $tasks = [
            [
                'title' => 'Project Planning',
                'description' => 'Define project requirements and create detailed plan',
                'status' => 'done',
                'priority' => 'high',
                'due_date' => now()->subDays(20),
            ],
            [
                'title' => 'Database Design',
                'description' => 'Design and implement database schema',
                'status' => 'done',
                'priority' => 'high',
                'due_date' => now()->subDays(15),
            ],
            [
                'title' => 'Frontend Development',
                'description' => 'Develop user interface components',
                'status' => 'in_progress',
                'priority' => 'medium',
                'due_date' => now()->addDays(10),
            ],
            [
                'title' => 'Backend API',
                'description' => 'Implement REST API endpoints',
                'status' => 'in_progress',
                'priority' => 'medium',
                'due_date' => now()->addDays(15),
            ],
            [
                'title' => 'Testing',
                'description' => 'Perform comprehensive testing',
                'status' => 'todo',
                'priority' => 'high',
                'due_date' => now()->addDays(20),
            ],
            [
                'title' => 'Deployment',
                'description' => 'Deploy application to production',
                'status' => 'todo',
                'priority' => 'medium',
                'due_date' => now()->addDays(25),
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::create([
                'project_id' => $project->id,
                'assigned_to' => $teamMember->id,
                ...$taskData,
            ]);
        }
    }
}
