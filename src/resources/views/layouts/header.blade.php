<header class="header">
    <div class="header__logo">
        <img class="logo-image" src="{{ asset("img/logo.svg") }}" alt="No Image" />
    </div>
    @unless (request()->routeIs("login", "admin.login", "register.*", "verification.*"))
        {{-- ログイン時のみナビとログアウト表示 --}}
        @auth
            <nav class="nav">
                {{-- 管理者と一般ユーザーでナビを切り替え --}}
                @if (auth()->user()->role === "admin")
                    {{-- 管理者用ナビ --}}
                    <a class="nav__link" href="{{ route("admin.attendances.index") }}">勤怠一覧</a>
                    <a class="nav__link" href="{{ route("admin.staff.index") }}">スタッフ一覧</a>
                    <a class="nav__link" href="{{ route("requests.index") }}">申請一覧</a>

                    <form class="nav__logout-form" action="{{ route("logout") }}" method="POST">
                        @csrf
                        <button class="nav__logout-btn" type="submit">ログアウト</button>
                    </form>
                @else
                    {{-- スタッフ用ナビ --}}
                    <a class="nav__link" href="{{ route("attendance.create") }}">勤怠</a>
                    <a class="nav__link" href="{{ route("attendances.index") }}">勤怠一覧</a>
                    <a class="nav__link" href="{{ route("requests.index") }}">申請</a>

                    <form class="nav__logout-form" action="{{ route("logout") }}" method="POST">
                        @csrf
                        <button class="nav__logout-btn" type="submit">ログアウト</button>
                    </form>
                @endif
            </nav>
        @endauth
    @endunless
</header>
