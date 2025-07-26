<?php

namespace App\Repositories\User;

use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function userAuthFormattedPermissions($user_id)
    {
        $permissions = DB::table('users as u')
            ->where('u.id', $user_id)
            ->join('user_permissions as up', 'u.id', '=', 'up.user_id')
            ->join('permissions as p', function ($join) {
                $join->on('up.permission', '=', 'p.id')
                    ->where('up.view', true);
            })
            ->leftJoin('user_permission_subs as ups', function ($join) {
                $join->on('up.id', '=', 'ups.user_permission_id')
                    ->where('ups.view', true);
            })
            ->join('sub_permissions as sp', function ($join) {
                $join->on('ups.sub_permission_id', '=', 'sp.id');
            })
            ->select(
                'up.id as user_permission_id',
                'p.id',
                'p.group_by',
                'p.name as permission',
                'p.icon',
                'p.priority',
                'up.view',
                'up.edit',
                'up.delete',
                'up.add',
                'up.visible',
                'sp.name as sub_permission',
                'ups.sub_permission_id as sub_permission_id',
                'ups.is_category as sub_is_category',
                'ups.add as sub_add',
                'ups.delete as sub_delete',
                'ups.edit as sub_edit',
                'ups.view as sub_view'
            )
            ->orderBy('p.priority')  // Optional: If you want to order by priority, else remove
            ->get();

        // Transform data to match desired structure (for example, if you need nested `sub` permissions)
        $formattedPermissions = $permissions->groupBy('user_permission_id')->map(function ($group) {
            $subPermissions = $group->filter(function ($item) {
                return $item->sub_permission_id !== null; // Filter for permissions that have sub-permissions
            });

            $permission = $group->first(); // Get the first permission for this group

            $permission->view = (bool) $permission->view;
            $permission->edit = (bool) $permission->edit;
            $permission->delete = (bool) $permission->delete;
            $permission->add = (bool) $permission->add;
            $permission->visible = (bool) $permission->visible;
            if ($subPermissions->isNotEmpty()) {
                $permission->sub = $subPermissions->sortBy('sub_permission_id')->map(function ($sub) {
                    return [
                        'id' => $sub->sub_permission_id,
                        'name' => $sub->sub_permission,
                        'is_category' => (bool) $sub->sub_is_category,
                        'add' => (bool) $sub->sub_add,
                        'delete' => (bool) $sub->sub_delete,
                        'edit' => (bool) $sub->sub_edit,
                        'view' => (bool) $sub->sub_view,
                    ];
                })->values();
            } else {
                $permission->sub = [];
            }
            // If there are no sub-permissions, remove the unwanted fields
            unset($permission->sub_permission);
            unset($permission->sub_permission_id);
            unset($permission->sub_is_category);
            unset($permission->sub_add);
            unset($permission->sub_delete);
            unset($permission->sub_edit);
            unset($permission->sub_view);

            return $permission;
        })->values();

        return $formattedPermissions;
    }
}
