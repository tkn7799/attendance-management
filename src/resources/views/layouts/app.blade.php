<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>勤怠管理システム</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  @yield('css')
</head>

<body>
  @php
    $routeName = Route::currentRouteName();
  @endphp


  <header class="header">
    <div class="header__logo">
      @if(Auth::check() && Auth::user()->role === 1)
          <a href="{{ route('admin.attendance.list') }}"><img src="{{ asset('img/auth-header.png') }}" alt="ロゴ"></a>
      @else
          <a href="{{ route('attendance.index') }}"><img src="{{ asset('img/auth-header.png') }}" alt="ロゴ"></a>
      @endif
    </div>

      @if(!in_array($routeName, ['login', 'admin.login','register', 'verification.notice']))
          <nav>
          <ul class="header-nav">
            @if(Auth::check())
                @if(Auth::user()->role === 1)
                    <li class="header-nav__item">
                      <a class="header-nav__link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                    </li>
                    <li class="header-nav__item">
                      <a class="header-nav__link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                    </li>
                    <li class="header-nav__item">
                      <a class="header-nav__link" href="{{ route('admin.correction.list') }}">申請一覧</a>
                    </li>
                @else
                    <li class="header-nav__item">
                      <a class="header-nav__link" href="{{ route('attendance.index') }}">勤怠</a>
                    </li>
                    <li class="header-nav__item">
                      <a class="header-nav__link" href="{{ route('attendance.list') }}">勤怠一覧</a>
                    </li>
                    <li class="header-nav__item">
                      <a class="header-nav__link" href="{{ route('attendance.application') }}">申請</a>
                    </li>
                @endif

                <li class="header-nav__item">
                  <form class="form" action="{{ route('logout') }}" method="post">
                  @csrf
                  <button class="header-nav__button">ログアウト</button>
                  </form>
                </li>

            @endif
          </ul>
        </nav>
      @endif
  </header>

  <main>
    @yield('content')
  </main>
</body>

</html>