@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list__container">
    {{-- ページタイトル --}}
    <div class="attendance-list__header">
        <h1 class="attendance-list__title">勤怠一覧</h1>
    </div>

    {{-- 月次ナビゲーション --}}
    <div class="attendance-list__nav">
        {{-- 前月リンク --}}
        <a href="{{ route('attendance.list', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="nav-link">← 前月</a>

        {{-- 現在の月表示 --}}
        <div class="current-month">
            <img src="{{ asset('img/climg.png') }}" alt="カレンダー" class="calendar-icon">
            <span>{{ $month->format('Y/m') }}</span>
        </div>

        {{-- 翌月リンク --}}
        <a href="{{ route('attendance.list', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="nav-link">翌月 →</a>
    </div>

    {{-- 勤怠一覧テーブル --}}
    <div class="attendance-table-container">
        <table class="attendance-table">
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
                @foreach($period as $date)
                    @php
                        $dateString = $date->toDateString();
                        $attendance = $attendances->get($dateString);
                    @endphp
                    <tr>
                        <td>{{ $date->format('m/d') }}({{ $date->isoFormat('ddd') }})</td>
                        <td>
                            {{ ($attendance && !is_null($attendance->clock_in)) ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}
                        </td>

                        <td>
                            {{ ($attendance && !is_null($attendance->clock_out)) ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}
                        </td>

                        <td>{{ ($attendance && !is_null($attendance->clock_in)) ? $attendance->total_rest_duration : '' }}</td>
                        <td>{{ ($attendance && !is_null($attendance->clock_in)) ? $attendance->total_work_duration : '' }}</td>

                        <td>
                            @if($date->lte(now()))
                                @if($attendance)
                                    <a href="{{ route('attendance.detail', ['id' => $attendance->id]) }}" class="detail-link">詳細</a>
                                @else
                                <a href="{{ route('attendance.detail', ['date' => $dateString]) }}" class="detail-link">詳細</a>
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