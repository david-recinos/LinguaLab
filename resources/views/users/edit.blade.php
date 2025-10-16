<!DOCTYPE html>
<html>
<head>
    <title>Edit User | LinguaLab</title>
</head>
<body>
    <h1>Edit User</h1>
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        <div>
            <label>Name</label>
            <input type="text" name="name" required value="{{ old('name', $user->name) }}">
        </div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required value="{{ old('email', $user->email) }}">
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password">
        </div>
        <div>
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation">
        </div>
        <div>
            <label>Role</label>
            <select name="role" required>
                <option value="user"{{ old('role', $user->role) === 'user' ? ' selected' : '' }}>User</option>
                <option value="admin"{{ old('role', $user->role) === 'admin' ? ' selected' : '' }}>Admin</option>
            </select>
        </div>
        <button type="submit">Update</button>
    </form>
    <a href="{{ route('users.index') }}">Back</a>
    @if ($errors->any())
        <div style="color:red;">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</body>
</html>
