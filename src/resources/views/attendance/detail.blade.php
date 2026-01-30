@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
@php
    $rests = $attendance->rests;
    $pendingCorrection = isset($attendance->id)
        ? $attendance->corrections()->where('status', 0)->with('restCorrections')->first()
        : null;

        $pendingRestCorrections = $pendingCorrection ? $pendingCorrection->restCorrections : collect();

        $actualRestCount = $pendingRestCorrections->isNotEmpty()
            ? $pendingRestCorrections->count()
            : $rests->count();

        $isPending = $pendingCorrection ? true : false;
@endphp

<div class="attendance-detail__container">
    <div class="attendance-detail__header">
        <h1 class="attendance-detail__title">勤怠詳細</h1>
    </div>

    <form action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="post">
        @csrf

        @if(!$attendance->id)
            <input type="hidden" name="date" value="{{ $attendance->date }}">
        @endif

        <div class="attendance-detail__card">
            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span class="text-data">{{ Auth::user()->name }}</span>
                </div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-content">
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
                            value="{{ old('clock_in', $pendingCorrection->revised_clock_in ?? ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')) }}">
                    <span class="symbol">～</span>
                    <input type="time" name="clock_out" class="input-field"
                            value="{{ old('clock_out', $pendingCorrection->revised_clock_out ?? ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')) }}">
                    @error('clock_out')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 休憩時間（既存データ + 1行の空欄） --}}
            @for ($i = 0; $i <= $actualRestCount; $i++)
            <div class="detail-row">
                <div class="detail-label">休憩{{ $i === 0 ? '' : $i + 1 }}</div>
                <div class="detail-content">
                    @php
                        $startTime = '';
                        $endTime = '';
                        if ($pendingRestCorrections->has($i)) {
                            $startTime = \Carbon\Carbon::parse($pendingRestCorrections[$i]->revised_start_time)->format('H:i');
                            $endTime = \Carbon\Carbon::parse($pendingRestCorrections[$i]->revised_end_time)->format('H:i');
                        } elseif (isset($rests[$i])) {
                            $startTime = \Carbon\Carbon::parse($rests[$i]->start_time)->format('H:i');
                            $endTime = \Carbon\Carbon::parse($rests[$i]->end_time)->format('H:i');
                        }
                    @endphp

                    <input type="time" name="rests[{{ $i }}][start]" class="input-field"
                           value="{{ old("rests.$i.start", $startTime) }}">
                    <span class="symbol">～</span>
                    <input type="time" name="rests[{{ $i }}][end]" class="input-field"
                           value="{{ old("rests.$i.end", $endTime) }}">
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
                    <textarea name="remarks" class="text-textarea">{{ old('remarks', $pendingCorrection->remarks ?? $attendance->latestCorrection->remarks ?? '') }}</textarea>
                    @error('remarks')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form__footer">
            {{-- 修正申請中（承認待ち）のデータがあるか確認 --}}
            @php
                $isPending = $attendance->corrections()->where('status', 0)->exists();
            @endphp

            @if($isPending)
                <p class="error-text">承認待ちのため修正はできません。</p>
            @else
                <button type="submit" class="approve-button">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection
