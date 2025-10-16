<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
    public function store(Request $request)
    {
        $this->authorize('admin-only');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,user'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
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
    public function update(Request $request, User $user)
    {
        $this->authorize('update-user', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($request->has('password') && $validated['password']) {
            $user->password = Hash::make($validated['password']);
        }
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (Auth::user()->isAdmin() && $request->has('role')) {
            $user->role = $request->get('role');
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
