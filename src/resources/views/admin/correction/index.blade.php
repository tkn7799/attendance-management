@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/correction_list.css') }}">
@endsection

@section('content')
<div class="correction-list__container">
    <h1 class="correction-list__title">申請一覧</h1>

    <div class="tab-menu">
        {{-- クエリパラメータなどで active クラスを切り替える想定 --}}
        <a href="?tab=pending" class="tab-item {{ request('tab') != 'approved' ? 'tab-item--active' : '' }}">承認待ち</a>
        <a href="?tab=approved" class="tab-item {{ request('tab') == 'approved' ? 'tab-item--active' : '' }}">承認済み</a>
    </div>

    <table class="correction-table">
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
            @foreach($requests as $req)
            <tr>
                <td>{{ $req->status == 0 ? '承認待ち' : '承認済み' }}</td>
                <td>{{ $req->user->name }}</td>
                <td>{{ $req->attendance->date->format('Y/m/d') }}</td>
                <td>{{ $req->remarks }}</td>
                <td>{{ $req->created_at->format('Y/m/d') }}</td>
                <td>
                    <a href="{{ route('admin.correction.show', $req->id) }}" class="detail-link">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection