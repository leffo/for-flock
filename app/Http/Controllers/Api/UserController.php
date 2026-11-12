<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function current(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(Request $request, User $user): UserResource
    {
        $user->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        return new UserResource($user);
    }
}
