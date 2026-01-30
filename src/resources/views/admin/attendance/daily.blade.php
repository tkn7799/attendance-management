@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list__container">
    {{-- ページタイトル --}}
    <div class="attendance-list__header">
        <h1 class="attendance-list__title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
    </div>

    {{-- 日付ナビゲーション --}}
    <div class="attendance-list__nav">
        {{-- 前日リンク --}}
        <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="nav-link">← 前日</a>

        {{-- 現在の日付表示 --}}
        <div class="current-date">
            <img src="{{ asset('img/climg.png') }}" alt="カレンダー" class="calendar-icon">
            <span>{{ $date->format('Y/m/d') }}</span>
        </div>

        {{-- 翌日リンク --}}
        <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="nav-link">翌日 →</a>
    </div>

    {{-- 勤怠一覧テーブル --}}
    <div class="attendance-table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    @php
                        $attendance = $user->attendances->first();
                    @endphp
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ ($attendance && $attendance->clock_in) ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $attendance && $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    <td>{{ $attendance ? $attendance->total_rest_duration : '' }}</td>
                    <td>{{ $attendance ? $attendance->total_work_duration : '' }}</td>
                    <td>
                        @if($date->lte(now()))
                            @if($attendance)
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
</div>
@endsection
