@extends('layouts.app')

@section('title', 'Mailbox Permissions - '.$mailbox->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3">
            @include('mailboxes._partials.settings_nav')
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Mailbox Permissions</h4>
                </div>
                <div class="card-body">
                    <p>Manage user access to this mailbox.</p>

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('mailboxes.permissions.update', $mailbox) }}">
                        @csrf
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Access Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @php
                                        $userMailbox = $user->mailboxes->firstWhere('id', $mailbox->id);
                                        $currentAccess = $userMailbox?->pivot->access;
                                    @endphp
                                    <tr>
                                        <td>{{ $user->getFullName() }}</td>
                                        <td>
                                            <select name="permissions[{{ $user->id }}]" class="form-control">
                                                <option value="">No Access</option>
                                                <option value="10" @selected($currentAccess == 10)>View Only</option>
                                                <option value="20" @selected($currentAccess == 20)>View and Reply</option>
                                                <option value="30" @selected($currentAccess == 30)>Full Access (Admin)</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-primary">Save Permissions</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
