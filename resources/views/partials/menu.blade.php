<nav class="menu">
    <ul>
        <li><a href="{{ url('/') }}">Dashboard</a></li>
        <li><a href="{{ route('admin.aeropuertos') }}">Aeropuertos</a></li>
    </ul>
</nav>
<style>
    .menu {
        background-color: #4c51bf;
        padding: 10px;
    }
    .menu ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 10px;
    }
    .menu a {
        color: #fff;
        text-decoration: none;
        font-weight: bold;
        padding: 6px 12px;
        border-radius: 4px;
    }
    .menu a:hover {
        background-color: #667eea;
    }
</style>
