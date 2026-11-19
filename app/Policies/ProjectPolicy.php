<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->team !== null && $user->belongsToTeam($project->team);
    }

    public function updateGraph(User $user, Project $project): bool
    {
        return $this->view($user, $project)
            && ($user->ownsTeam($project->team) || $user->hasTeamRole($project->team, 'admin'));
    }
}
