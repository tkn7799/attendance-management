@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/application_list.css') }}">
@endsection

@section('content')
<div class="application-list__container">
    <h1 class="application-list__title">申請一覧</h1>

    <div class="tab-menu">
        <a href="{{ route('attendance.application', ['tab' => 'pending']) }}"
           class="tab-link {{ $status === 'pending' ? 'is-active' : '' }}">承認待ち
        </a>

        <a href="{{ route('attendance.application', ['tab' => 'approved']) }}"
           class="tab-link {{ $status === 'approved' ? 'is-active' : '' }}">承認済み
        </a>
    </div>

    <div class="table-wrapper">
        <table class="application-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $application)
                <tr>
                    <td>{{ $application->status === 0 ? '承認待ち' : '承認済み' }}</td>
                    <td>{{ Auth::user()->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($application->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ $application->remarks }}</td>
                    <td>{{ $application->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('attendance.detail', ['id' => $application->attendance_id, 'mode' => 'pending']) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection