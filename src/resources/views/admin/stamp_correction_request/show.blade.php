@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_show.css') }}">
@endsection

@section('content')
<div class="attendance-detail__container">
    <div class="attendance-detail__header">
        <h1 class="attendance-detail__title">勤怠詳細</h1>
    </div>

    <form action="{{ route('admin.correction.approve', ['attendance_correct_request_id' => $correction->id]) }}" method="post">
        @csrf
        <div class="attendance-detail__card">
            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span class="text-data">{{ $correction->user->name }}</span>
                </div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-content">
                    <span class="text-data">
                        {{ \Carbon\Carbon::parse($correction->attendance->date)->format('Y年') }}
                        <span class="date-spacer"></span>
                        {{ \Carbon\Carbon::parse($correction->attendance->date)->format('n月j日') }}
                    </span>
                </div>
            </div>

            {{-- 出勤・退勤 (修正後の値) --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-content">
                    {{-- 秒をカットして H:i 形式に --}}
                    <span class="text-data">{{ \Carbon\Carbon::parse($correction->revised_clock_in)->format('H:i') }}</span>
                    <span class="symbol">～</span>
                    <span class="text-data">{{ \Carbon\Carbon::parse($correction->revised_clock_out)->format('H:i') }}</span>
                </div>
            </div>

            {{-- 休憩 (修正後の値) --}}
            @foreach($correction->restCorrections as $index => $rest)
            <div class="detail-row">
                <div class="detail-label">休憩{{ $index === 0 ? '' : $index + 1 }}</div>
                <div class="detail-content">
                    <span class="text-data">{{ optional($rest->revised_start_time)->format('H:i') }}</span>
                    <span class="symbol">～</span>
                    <span class="text-data">{{ optional($rest->revised_end_time)->format('H:i') }}</span>
                </div>
            </div>
            @endforeach

            <div class="detail-row">
                @php $nextCount = count($correction->restCorrections); @endphp
                <div class="detail-label">休憩{{ $nextCount === 0 ? '' : $nextCount + 1 }}</div>
                <div class="detail-content">
                </div>
            </div>

            {{-- 備考 --}}
            <div class="detail-row no-border">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    <div class="text-textarea">{{ $correction->remarks }}</div>
                </div>
            </div>
        </div>

        <div class="form__footer">
            {{-- ステータスに応じてボタンを切り替え --}}
            @if($correction->status === 0)
                <button type="submit" class="approve-button">承認</button>
            @else
                <button type="button" class="approve-button is-approved" disabled>承認済み</button>
            @endif
        </div>
    </form>
</div>
@endsection