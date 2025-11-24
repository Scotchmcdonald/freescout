@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('System Update') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="text-center">
                        <h3>{{ __('Current Version') }}: {{ app()->version() }}</h3>
                        
                        @if($update_available ?? false)
                            <div class="alert alert-info">
                                {{ __('A new version is available!') }}
                            </div>
                        @else
                            <p class="text-muted">{{ __('You are running the latest version.') }}</p>
                        @endif

                        <hr>

                        <p>{{ __('This tool will run database migrations and clear caches. Use this after updating the application files.') }}</p>

                        <form action="{{ route('system.perform_update') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('{{ __('Are you sure you want to run the update script?') }}')">
                                {{ __('Run Update Script') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
