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
            {{-- カレンダーアイコンが必要な場合はここに<img>を追加してください --}}
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
                {{-- 修正ポイント：$attendancesではなく、$datePeriod（1ヶ月分の日付）をループ --}}
                @foreach($datePeriod as $date)
                    @php
                        $dateString = $date->toDateString();
                        // その日のデータがあれば取得、なければnull
                        $attendance = $attendances->get($dateString);
                    @endphp
                    <tr>
                        {{-- 日付表示 --}}
                        <td class="table-date">
                            {{ $date->isoFormat('MM/DD(ddd)') }}
                        </td>

                        {{-- 出勤・退勤 --}}
                        <td>{{ ($attendance && $attendance->clock_in) ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                        <td>{{ ($attendance && $attendance->clock_out) ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>

                        {{-- 休憩・合計 --}}
                        <td>{{ $attendance->total_rest_duration ?? '' }}</td>
                        <td>{{ $attendance->total_work_duration ?? '' }}</td>

                        <td>
                            {{-- 未来の日付でなければ詳細を表示 --}}
                            @if(!$date->isFuture())
                                @if($attendance)
                                    <a href="{{ route('attendance.detail', ['id' => $attendance->id]) }}" class="detail-link">詳細</a>
                                @else
                                    {{-- データがない過去日の場合、日付をIDとして渡す（Controller側で日付を受け取れるようにする） --}}
                                    <a href="{{ route('attendance.detail', ['id' => $dateString]) }}" class="detail-link">詳細</a>
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