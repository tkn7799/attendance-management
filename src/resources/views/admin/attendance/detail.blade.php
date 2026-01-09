@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail__container">
    {{-- ページタイトル --}}
    <div class="attendance-detail__header">
        <h1 class="attendance-detail__title">勤怠詳細</h1>
    </div>

    {{-- 詳細・修正フォーム --}}
    <form class="attendance-detail__form" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="post">
        @csrf
        <div class="attendance-detail__card">
            {{-- 名前（テキスト表示） --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span class="text-data">{{ $attendance->user->name }}</span>
                </div>
            </div>

            {{-- 日付（年/月/日の分割表示） --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-content">
                    <span class="text-data">
                        {{ $attendance->date->format('Y年') }}
                        <span class="date-spacer"></span>
                        {{ $attendance->date->format('n月j日') }}
                    </span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-content">
                    <input type="time" name="clock_in" class="input-time" value="{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}">
                    <span class="symbol">～</span>
                    <input type="time" name="clock_out" class="input-time" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
                </div>
            </div>

            {{-- 休憩（1回目） --}}
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-content">
                    <input type="time" name="rests[0][start]" class="input-time" value="{{ isset($attendance->rests[0]) ? \Carbon\Carbon::parse($attendance->rests[0]->start_time)->format('H:i') : '' }}">
                    <span class="symbol">～</span>
                    <input type="time" name="rests[0][end]" class="input-time" value="{{ isset($attendance->rests[0]->end_time) ? \Carbon\Carbon::parse($attendance->rests[0]->end_time)->format('H:i') : '' }}">
                </div>
            </div>

            {{-- 休憩2（2回目） --}}
            <div class="detail-row">
                <div class="detail-label">休憩2</div>
                <div class="detail-content">
                    <input type="time" name="rests[1][start]" class="input-time" value="{{ isset($attendance->rests[1]) ? \Carbon\Carbon::parse($attendance->rests[1]->start_time)->format('H:i') : '' }}">
                    <span class="symbol">～</span>
                    <input type="time" name="rests[1][end]" class="input-time" value="{{ isset($attendance->rests[1]->end_time) ? \Carbon\Carbon::parse($attendance->rests[1]->end_time)->format('H:i') : '' }}">
                </div>
            </div>

            {{-- 備考 --}}
            <div class="detail-row no-border">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    <textarea name="remarks" class="input-textarea">{{ $attendance->remarks }}</textarea>
                </div>
            </div>
        </div>

        {{-- 修正ボタン --}}
        <div class="form__button">
            <button type="submit" class="submit-button">修正</button>
        </div>
    </form>
</div>
@endsection