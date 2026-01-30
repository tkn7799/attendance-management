@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail__container">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <div class="attendance-detail__header">
        <h1 class="attendance-detail__title">勤怠詳細</h1>
    </div>

    {{-- 保存・更新処理へのフォーム --}}
    <form action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="post">
        @csrf

        {{-- 新規作成(勤怠データがない)場合、必要な情報を隠しデータで送る --}}
        @if(!$attendance->id)
            <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            <input type="hidden" name="date" value="{{ request('date') }}">
        @else
            <input type="hidden" name="date" value="{{ $attendance->date }}">
        @endif

        <div class="attendance-detail__card">
            {{-- 名前行 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span class="text-data">{{ $attendance->user->name }}</span>
                </div>
            </div>

            {{-- 日付行 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-content">
                    <span class="text-data">
                        {{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}
                        <span style="margin: 0 20px;"></span>
                        {{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}
                    </span>
                </div>
            </div>

            {{-- 出勤・退勤 入力行 --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-content">
                    @if($isPending)
                        <span class="text-data">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '--:--' }}</span>
                        <span class="symbol">～</span>
                        <span class="text-data">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '--:--' }}</span>
                    @else
                    <input type="time" name="clock_in" class="input-field"
                           value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}">
                    <span class="symbol">～</span>
                    <input type="time" name="clock_out" class="input-field"
                           value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}">
                    @error('clock_out')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                    @endif
                </div>
            </div>

            {{-- 休憩時間（1件目を代表で表示、複数ある場合はループ） --}}
            @php
                $rests = $attendance->rests;
                $restCount = count($rests);
                $loopCount = $isPending ? $restCount : $restCount + 1;
            @endphp

            @for ($i = 0; $i <= $restCount; $i++)
                <div class="detail-row">
                    <div class="detail-label">休憩{{ $i === 0 ? '' : $i + 1 }}</div>
                    <div class="detail-content">
                        @if($isPending)
                            @if(isset($rests[$i]))
                                <span class="text-data">{{ \Carbon\Carbon::parse($rests[$i]->start_time)->format('H:i') }}</span>
                                <span class="symbol">～</span>
                                <span class="text-data">{{ \Carbon\Carbon::parse($rests[$i]->end_time)->format('H:i') }}</span>
                            @endif
                        @else
                            <input type="time" name="rests[{{ $i }}][start]" class="input-field"
                                   value="{{ old("rests.$i.start", isset($rests[$i]) ? \Carbon\Carbon::parse($rests[$i]->start_time)->format('H:i') : '') }}">
                            <span class="symbol">～</span>
                            <input type="time" name="rests[{{ $i }}][end]" class="input-field"
                                   value="{{ old("rests.$i.end", isset($rests[$i]) ? \Carbon\Carbon::parse($rests[$i]->end_time)->format('H:i') : '') }}">
                            @error("rests.$i.end")
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>
            @endfor

            {{-- 備考 --}}
            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    @if($isPending)
                        <div class="text-data-area">
                            {{ old('remarks', $attendance->attendanceCorrection->remarks ?? '') }}
                        </div>
                    @else
                        <textarea name="remarks" class="text-textarea">{{ old('remarks', $attendance->remarks ?? $attendance->attendanceCorrection->remarks ?? '') }}</textarea>
                        @error('remarks')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>
        </div>

        <div class="form__footer">
            @if($isPending)
                <p class="error-text">承認待ちのため修正はできません。</p>
            @else
                <button type="submit" class="approve-button">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection