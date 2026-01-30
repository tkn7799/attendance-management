@extends('layouts.app')

@section('css')
{{-- 一般ユーザー用と共通のスタイルを使用 --}}
<link rel="stylesheet" href="{{ asset('css/application_list.css') }}">
@endsection

@section('content')
<div class="application-list__container">
    <h1 class="application-list__title">修正申請一覧</h1>

    {{-- タブメニュー --}}
    <div class="tab-menu">
        <a href="{{ route('admin.correction.list', ['tab' => 'pending']) }}"
           class="tab-link {{ $status === 'pending' ? 'is-active' : '' }}">承認待ち</a>
        <a href="{{ route('admin.correction.list', ['tab' => 'approved']) }}"
           class="tab-link {{ $status === 'approved' ? 'is-active' : '' }}">承認済み</a>
    </div>

    {{-- 白いテーブルボックス --}}
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
                    {{-- 管理者用なので申請者の名前を表示 --}}
                    <td>{{ $application->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($application->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ $application->remarks }}</td>
                    <td>{{ $application->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('admin.correction.show_approve', ['attendance_correct_request_id' => $application->id]) }}" class="detail-link">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection