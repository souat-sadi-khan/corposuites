<?php

namespace App\Services;

use App\Models\ProjectTeamMember;

class ProjectTeamMemberService
{
    public function create(array $data): ProjectTeamMember
    {
        return ProjectTeamMember::create($data);
    }

    public function update(ProjectTeamMember $member, array $data): ProjectTeamMember
    {
        $member->update($data);

        return $member->fresh();
    }

    public function delete(ProjectTeamMember $member): bool
    {
        return $member->delete();
    }
}
