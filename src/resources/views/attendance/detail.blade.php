@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
@php
    $mode = request()->query('mode');
    $rests = $attendance->rests;
    $pendingCorrection = isset($attendance->id)
        ? $attendance->corrections()->where('status', 0)->with('restCorrections')->first()
        : null;

        $pendingRestCorrections = $pendingCorrection ? $pendingCorrection->restCorrections : collect();

        $isPendingMode = ($mode === 'pending' && $pendingCorrection);

        $displayClockIn = $isPendingMode ? $pendingCorrection->revised_clock_in : $attendance->clock_in;
        $displayClockOut = $isPendingMode ? $pendingCorrection->revised_clock_out : $attendance->clock_out;
        $displayRemarks = $isPendingMode ? ($pendingCorrection->remarks ?? '') : ($attendance->remarks ?? '');

        $actualRestCount = $isPendingMode ? $pendingRestCorrections->count() : $rests->count();

@endphp

<div class="attendance-detail__container">
    <div class="attendance-detail__header">
        <h1 class="attendance-detail__title">勤怠詳細</h1>
    </div>

    <form action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="post" class="{{ $isPendingMode ? 'mode-pending' : '' }}">
        @csrf

        @if(!$attendance->id)
            <input type="hidden" name="date" value="{{ $attendance->date }}">
        @endif

        <div class="attendance-detail__card">
            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content is-name">
                    <span class="text-data">{{ Auth::user()->name }}</span>
                </div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-content is-date">
                    <span class="text-data">
                        <span>{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span>{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                    </span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-content">
                    <input type="time" name="clock_in" class="input-field"
                            value="{{ old('clock_in', $displayClockIn ? \Carbon\Carbon::parse($displayClockIn)->format('H:i') : '') }}">
                    @error('clock_in')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                    <span class="symbol">～</span>
                    <input type="time" name="clock_out" class="input-field"
                            value="{{ old('clock_out', $displayClockOut ? \Carbon\Carbon::parse($displayClockOut)->format('H:i') : '') }}">
                    @error('clock_out')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 休憩時間（既存データ + 1行の空欄） --}}
            @php
                $displayRests = $isPendingMode ? $pendingRestCorrections : $rests;

                $maxIndex = count($displayRests);
            @endphp

            @for ($i = 0; $i <= $maxIndex; $i++)
                @php
                    $startTime = '';
                    $endTime = '';

                    if (isset($displayRests[$i])) {
                        $currentRest = $displayRests[$i];
                        $sKey = $isPendingMode ? 'revised_start_time' : 'start_time';
                        $eKey = $isPendingMode ? 'revised_end_time' : 'end_time';

                        $startTime = $currentRest->$sKey ? \Carbon\Carbon::parse($currentRest->$sKey)->format('H:i') : '';
                        $endTime = $currentRest->$eKey ? \Carbon\Carbon::parse($currentRest->$eKey)->format('H:i') : '';
                    }
                @endphp

            <div class="detail-row">
                <div class="detail-label">休憩{{ $i === 0 ? '' : $i + 1 }}</div>
                <div class="detail-content">
                    <input type="time" name="rests[{{ $i }}][start]" class="input-field"
                           value="{{ old("rests.$i.start", $startTime) }}"
                           {{ $isPendingMode ? 'readonly' : '' }}>
                    @error("rests.$i.start")
                        <p class="error-message">{{ $message }}</p>
                    @enderror

                    <span class="symbol">～</span>
                    <input type="time" name="rests[{{ $i }}][end]" class="input-field"
                           value="{{ old("rests.$i.end", $endTime) }}"
                           {{ $isPendingMode ? 'readonly' : '' }}>
                    @error("rests.$i.end")
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endfor

            {{-- 備考 --}}
            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    <textarea name="remarks" class="text-textarea">{{ old('remarks', $displayRemarks) }}</textarea>
                    @error('remarks')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form__footer">
            {{-- 修正申請中（承認待ち）のデータがあるか確認 --}}
            @if($pendingCorrection)
                <p class="error-text">*承認待ちのため修正はできません。</p>
            @else
                <button type="submit" class="approve-button">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection
