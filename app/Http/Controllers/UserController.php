<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the users (admin only).
     */
    public function index()
    {
        $this->authorize('admin-only');

        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user (admin only).
     */
    public function create()
    {
        $this->authorize('admin-only');
        return view('users.create');
    }

    /**
     * Store a newly created user in storage (admin only).
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('admin-only');

        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // Assign role to the new user
        if (isset($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user (admin or self).
     */
    public function edit(User $user)
    {
        $this->authorize('update-user', $user);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage (admin or self).
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update-user', $user);

        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update role if admin and role is provided
        if (Auth::user()->isAdmin() && $request->has('role')) {
            $user->syncRoles([$request->get('role')]);
        }

        $user->save();

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage (admin only).
     */
    public function destroy(User $user)
    {
        $this->authorize('admin-only');
        if(Auth::id() == $user->id){
            return redirect()->back()->with('error', 'You cannot delete your own account while logged in.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    /**
     * Display the logged in user's profile.
     */
    public function profile()
    {
        return view('users.profile', ['user' => Auth::user()]);
    }
}
