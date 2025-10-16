<!DOCTYPE html>
<html>
<head>
    <title>Create User | LinguaLab</title>
</head>
<body>
    <h1>Create User</h1>
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div>
            <label>Name</label>
            <input type="text" name="name" required value="{{ old('name') }}">
        </div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required value="{{ old('email') }}">
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>
        </div>
        <div>
            <label>Role</label>
            <select name="role" required>
                <option value="user"{{ old('role') === 'user' ? ' selected' : '' }}>User</option>
                <option value="admin"{{ old('role') === 'admin' ? ' selected' : '' }}>Admin</option>
            </select>
        </div>
        <button type="submit">Create</button>
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
