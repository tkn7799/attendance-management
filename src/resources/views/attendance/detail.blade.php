@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail__container">
    <div class="attendance-detail__header">
        <h1 class="attendance-detail__title">勤怠詳細</h1>
    </div>

    <form class="attendance-detail__form" action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="post">
        @csrf
        <div class="attendance-detail__card">
            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span class="text-data">{{ $attendance->user->name }}</span>
                </div>
            </div>

            {{-- 日付 --}}
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
                    @if($isPending)
                        <span class="text-data">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</span>
                        <span class="symbol">～</span>
                        <span class="text-data">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</span>
                    @else
                        {{-- nameを revised_... に変更 --}}
                        <input type="time" name="revised_clock_in" class="input-time" value="{{ old('revised_clock_in', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}">
                        <span class="symbol">～</span>
                        <input type="time" name="revised_clock_out" class="input-time" value="{{ old('revised_clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}">
                        
                        {{-- エラー表示 --}}
                        @error('revised_clock_in')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            {{-- 休憩1 --}}
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-content">
                    @if($isPending)
                        <span class="text-data">{{ isset($attendance->rests[0]) ? \Carbon\Carbon::parse($attendance->rests[0]->start_time)->format('H:i') : '' }}</span>
                        <span class="symbol">～</span>
                        <span class="text-data">{{ isset($attendance->rests[0]->end_time) ? \Carbon\Carbon::parse($attendance->rests[0]->end_time)->format('H:i') : '' }}</span>
                    @else
                        {{-- nameを配列形式 rest_start[] に変更 --}}
                        <input type="time" name="rest_start[]" class="input-time" value="{{ old('rest_start.0', isset($attendance->rests[0]) ? \Carbon\Carbon::parse($attendance->rests[0]->start_time)->format('H:i') : '') }}">
                        <span class="symbol">～</span>
                        <input type="time" name="rest_end[]" class="input-time" value="{{ old('rest_end.0', isset($attendance->rests[0]->end_time) ? \Carbon\Carbon::parse($attendance->rests[0]->end_time)->format('H:i') : '') }}">
                        
                        @error('rest_start.0') <p class="error-message">{{ $message }}</p> @enderror
                        @error('rest_end.0') <p class="error-message">{{ $message }}</p> @enderror
                    @endif
                </div>
            </div>

            {{-- 休憩2（任意） --}}
            @if(!$isPending || (isset($attendance->rests[1])))
            <div class="detail-row">
                <div class="detail-label">休憩2</div>
                <div class="detail-content">
                    @if($isPending)
                        <span class="text-data">{{ \Carbon\Carbon::parse($attendance->rests[1]->start_time)->format('H:i') }}</span>
                        <span class="symbol">～</span>
                        <span class="text-data">{{ \Carbon\Carbon::parse($attendance->rests[1]->end_time)->format('H:i') }}</span>
                    @else
                        {{-- 休憩2枚目 --}}
                        <input type="time" name="rest_start[]" class="input-time" value="{{ old('rest_start.1', isset($attendance->rests[1]) ? \Carbon\Carbon::parse($attendance->rests[1]->start_time)->format('H:i') : '') }}">
                        <span class="symbol">～</span>
                        <input type="time" name="rest_end[]" class="input-time" value="{{ old('rest_end.1', isset($attendance->rests[1]) ? \Carbon\Carbon::parse($attendance->rests[1]->end_time)->format('H:i') : '') }}">
                        
                        @error('rest_start.1') <p class="error-message">{{ $message }}</p> @enderror
                        @error('rest_end.1') <p class="error-message">{{ $message }}</p> @enderror
                    @endif
                </div>
            </div>
            @endif

            {{-- 備考 --}}
            <div class="detail-row no-border">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    @if($isPending)
                        <span class="text-data">{{ $attendance->remarks }}</span>
                    @else
                        <textarea name="remarks" class="input-textarea">{{ old('remarks', $attendance->remarks) }}</textarea>
                        @error('remarks')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>
        </div>

        {{-- 下部アクションエリア --}}
        <div class="form__footer">
            @if($isPending)
                <p class="pending-message">* 承認待ちのため修正はできません。</p>
            @else
                <button type="submit" class="submit-button">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection