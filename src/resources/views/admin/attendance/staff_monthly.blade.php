@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_monthly.css') }}">
@endsection

@section('content')
<div class="monthly-attendance__container">
    <div class="monthly-attendance__header">
        <h1 class="monthly-attendance__title">{{ $user->name }}さんの勤怠</h1>
    </div>

    {{-- 月選択ナビゲーション --}}
    <div class="month-nav">
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth]) }}" class="month-nav__link">← 前月</a>
        <div class="month-nav__current">
            <img src="{{ asset('img/climg.png') }}" alt="カレンダー" class="calendar-icon">
            <span>{{ $currentMonth->format('Y/m') }}</span>
        </div>
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}" class="month-nav__link">翌月 →</a>
    </div>

    <div class="monthly-attendance__table-wrapper">
        <table class="monthly-attendance__table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendance)
                @php
                    $date = \Carbon\Carbon::parse($attendance->date);
                    $dayOfWeek = ['日','月','火','水','木','金','土'][$date->dayOfWeek];
                @endphp

                <tr>
                    {{-- 日付表示 --}}
                    <td>{{ $date->format('m/d') }}({{ $dayOfWeek }})</td>
                    <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    <td>
                        @if($attendance->clock_in && $attendance->clock_out)
                            {{ $attendance->total_rest_duration }}
                        @endif
                    </td>
                    <td>
                        @if($attendance->clock_in && $attendance->clock_out)
                            {{ $attendance->total_work_duration }}
                        @endif
                    </td>

                    {{-- 詳細リンク --}}
                    <td>
                        @if($date->lte(now()))
                            @if($attendance->id)
                                <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}" class="detail-link">詳細</a>
                            @else
                                <a href="{{ route('admin.attendance.detail', ['user_id' => $user->id, 'date' => $date->toDateString()]) }}" class="detail-link">詳細</a>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CSV出力ボタン --}}
    <div class="csv-export">
        <a href="{{ route('admin.attendance.staff.csv', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}" class="csv-export__button">CSV出力</a>
    </div>
</div>
@endsection