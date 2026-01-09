@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/application_list.css') }}">
@endsection

@section('content')
<div class="application-list__container">
    {{-- ページタイトル --}}
    <div class="application-list__header">
        <h1 class="application-list__title">申請一覧</h1>
    </div>

    {{-- タブメニュー --}}
    <div class="application-tabs">
        <a href="{{ route('attendance.application', ['status' => 'pending']) }}" 
           class="tab-item {{ $status === 'pending' ? 'is-active' : '' }}">承認待ち</a>
        <a href="{{ route('attendance.application', ['status' => 'approved']) }}" 
           class="tab-item {{ $status === 'approved' ? 'is-active' : '' }}">承認済み</a>
    </div>

    {{-- 申請一覧テーブル --}}
    <div class="application-table-container">
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
                    <td>{{ $application->status_label }}</td>
                    <td>{{ $application->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($application->target_date)->format('Y/m/d') }}</td>
                    <td>{{ $application->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('attendance.application.detail', ['id' => $application->id]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection