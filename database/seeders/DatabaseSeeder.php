<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\Project;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create a default user if not exists
        $user = User::firstOrCreate(
            ['email' => 'admin@protask.local'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        // Workspaces
        $ws1 = Workspace::create(['name' => 'Design & Development', 'slug' => 'design-dev', 'user_id' => $user->id, 'description' => 'Frontend, UI/UX, and full-stack projects']);
        $ws2 = Workspace::create(['name' => 'Marketing', 'slug' => 'marketing', 'user_id' => $user->id, 'description' => 'Marketing campaigns and content']);

        // Projects
        $projects = [
            ['title' => 'Restaurant Web App', 'workspace_id' => $ws1->id, 'color' => '#6366f1', 'description' => 'Full stack restaurant ordering system'],
            ['title' => 'Mobile Banking UI', 'workspace_id' => $ws1->id, 'color' => '#8b5cf6', 'description' => 'Redesign of mobile banking UI'],
            ['title' => 'Q2 Social Campaign', 'workspace_id' => $ws2->id, 'color' => '#ec4899', 'description' => 'Social media and email campaigns'],
        ];

        $columnColors = ['#ef4444','#f59e0b','#8b5cf6','#10b981'];
        $defaultLists = [
            ['title' => 'To Do',      'is_done' => 0],
            ['title' => 'In Progress','is_done' => 0],
            ['title' => 'Review',     'is_done' => 0],
            ['title' => 'Done',       'is_done' => 1],
        ];

        foreach ($projects as $pd) {
            $pd['user_id'] = $user->id;
            $project = Project::create($pd);

            foreach ($defaultLists as $li => $ld) {
                $list = BoardList::create([
                    'title'      => $ld['title'],
                    'project_id' => $project->id,
                    'user_id'    => $user->id,
                    'order'      => $li,
                    'is_done'    => $ld['is_done'],
                ]);

                // Sample tasks per list
                $sampleTasks = [
                    'To Do' => ['Wireframe homepage', 'Set up DB schema', 'Configure CI/CD'],
                    'In Progress' => ['Build login page', 'API integration', 'Write unit tests'],
                    'Review' => ['Code review PR #12', 'QA testing round'],
                    'Done' => ['Project kickoff', 'Requirements gathering'],
                ];

                $tasks = $sampleTasks[$ld['title']] ?? [];
                foreach ($tasks as $ti => $ttitle) {
                    Task::create([
                        'title'      => $ttitle,
                        'list_id'    => $list->id,
                        'project_id' => $project->id,
                        'user_id'    => $user->id,
                        'order'      => $ti + 1,
                        'slug'       => Str::slug($ttitle) . '-' . time() . $ti,
                        'priority'   => ['low', 'medium', 'high'][rand(0, 2)],
                        'due_date'   => now()->addDays(rand(1, 30)),
                        'is_done'    => $ld['is_done'],
                    ]);
                }
            }
        }
    }
}
