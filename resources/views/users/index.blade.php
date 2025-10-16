<!DOCTYPE html>
<html>
<head>
    <title>User Management | LinguaLab</title>
</head>
<body>
    <h1>User Management</h1>
    <a href="{{ route('users.create') }}">Create New User</a>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th colspan="2">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role }}</td>
                <td><a href="{{ route('users.edit', $user) }}">Edit</a></td>
                <td>
                    <form method="POST" action="{{ route('users.destroy', $user) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Really delete this user?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <a href="{{ route('dashboard') }}">Back to Dashboard</a>
</body>
</html>
