<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | LinguaLab</title>
</head>
<body>
    <h1>Welcome, {{ Auth::user()->name }}</h1>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
    <ul>
        <li><a href="{{ route('profile') }}">My Profile</a></li>
        @can('admin')
        <li><a href="{{ route('users.index') }}">User Management</a></li>
        @endcan
    </ul>
</body>
</html>
