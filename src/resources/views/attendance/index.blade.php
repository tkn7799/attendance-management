@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    {{-- ステータスバッジ --}}
    <div class="attendance__status">
        @if(!$attendance)
            <span class="status-tag">勤務外</span>
        @elseif($attendance->clock_out)
            <span class="status-tag">退勤済</span>
        @elseif($attendance->rests()->whereNull('end_time')->exists())
            <span class="status-tag">休憩中</span>
        @else
            <span class="status-tag">出勤中</span>
        @endif
    </div>

    {{-- 日時表示 --}}
    <div class="attendance__info">
        <p class="attendance__date" id="real-time-date">
            {{ \Carbon\Carbon::now()->isoFormat('YYYY年M月D日(ddd)') }}
        </p>
        <p class="attendance__time" id="real-time-time">
            {{ \Carbon\Carbon::now()->format('H:i') }}
        </p>
    </div>

    {{-- ボタンアクションエリア --}}
    <div class="attendance__action">
        {{-- 1. 勤務外の場合 --}}
        @if(!$attendance)
            <form action="{{ route('attendance.clock-in') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn--black">出勤</button>
            </form>

        {{-- 2. 退勤済の場合 --}}
        @elseif($attendance->clock_out)
            <p class="attendance__message">お疲れ様でした。</p>

        {{-- 3. 休憩中の場合 --}}
        @elseif($attendance->rests()->whereNull('end_time')->exists())
            <form action="{{ route('attendance.rest-end') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn--white">休憩戻</button>
            </form>

        {{-- 4. 出勤中の場合 --}}
        @else
            <div class="btn-group">
                <form action="{{ route('attendance.clock-out') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn--black">退勤</button>
                </form>
                <form action="{{ route('attendance.rest-start') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn--white">休憩入</button>
                </form>
            </div>
        @endif
    </div>
</div>

<script>
    function updateDateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('real-time-time').textContent = `${hours}:${minutes}`;

        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const date = now.getDate();
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const day = dayNames[now.getDay()];
        document.getElementById('real-time-date').textContent = `${year}年${month}月${date}日(${day})`;
    }
    setInterval(updateDateTime, 1000);
</script>
@endsection