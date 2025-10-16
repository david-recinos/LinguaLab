<!DOCTYPE html>
<html>
<head>
    <title>Profile | LinguaLab</title>
</head>
<body>
    <h1>My Profile</h1>
    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        <div>
            <label>Name</label>
            <input type="text" name="name" required value="{{ old('name', $user->name) }}">
        </div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required value="{{ old('email', $user->email) }}">
        </div>
        <div>
            <label>Password (leave blank to keep current)</label>
            <input type="password" name="password">
        </div>
        <div>
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation">
        </div>
        <button type="submit">Update Profile</button>
    </form>
    <a href="{{ route('dashboard') }}">Back to Dashboard</a>
    @if ($errors->any())
        <div style="color:red;">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div style="color:green;">
            {{ session('status') }}
        </div>
    @endif
</body>
</html>
