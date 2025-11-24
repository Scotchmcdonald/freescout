@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Failed Jobs') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Connection') }}</th>
                                    <th>{{ __('Queue') }}</th>
                                    <th>{{ __('Exception') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($failedJobs as $job)
                                    <tr>
                                        <td>{{ $job->failed_at }}</td>
                                        <td>{{ $job->connection }}</td>
                                        <td>{{ $job->queue }}</td>
                                        <td title="{{ $job->exception }}">{{ Str::limit($job->exception, 50) }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary retry-job" data-id="{{ $job->uuid }}">{{ __('Retry') }}</button>
                                            <button class="btn btn-sm btn-danger delete-job" data-id="{{ $job->uuid }}">{{ __('Delete') }}</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">{{ __('No failed jobs found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $failedJobs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.retry-job').forEach(button => {
        button.addEventListener('click', function() {
            const uuid = this.dataset.id;
            fetch(`/system/failed-jobs/${uuid}/retry`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      location.reload();
                  } else {
                      alert(data.message);
                  }
              });
        });
    });

    document.querySelectorAll('.delete-job').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure?')) return;
            const uuid = this.dataset.id;
            fetch(`/system/failed-jobs/${uuid}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      location.reload();
                  } else {
                      alert(data.message);
                  }
              });
        });
    });
});
</script>
@endpush
@endsection
