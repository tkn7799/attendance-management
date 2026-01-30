@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_list.css') }}">
@endsection

@section('content')
<div class="staff-list__container">
    <div class="staff-list__header">
        <h1 class="staff-list__title">スタッフ一覧</h1>
    </div>

    <div class="staff-list__table-wrapper">
        <table class="staff-list__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="staff-name">{{ $user->name }}</td>
                    <td class="staff-email">{{ $user->email }}</td>
                    <td class="staff-detail">
                        <a href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection