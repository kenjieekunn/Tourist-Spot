@extends('layouts.app')

@section('title', 'Archive - Super Admin')
@section('header', 'Archive')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Archived records</h2>
        <div class="text-muted small">{{ $archives->total() }} total</div>
    </div>
</div>

<nav class="nav nav-tabs mb-4" aria-label="Archive record type">
    @foreach($archiveTypes as $type => $label)
        <a class="nav-link {{ $selectedType === $type ? 'active' : '' }}"
            href="{{ route('super-admin.archive', ['type' => $type]) }}"
            @if($selectedType === $type) aria-current="page" @endif>
            {{ $label }}
        </a>
    @endforeach
</nav>

@if($archiveUnavailable)
    <div class="alert alert-warning" role="alert">
        Archive storage is not available on this server yet. Run <code>php artisan migrate --force</code> from the Laravel app directory, then reload this page.
    </div>
@else
@forelse($archives as $archive)
    @php
        $recordLabel = match ($archive->record_type) {
            'tourist_spot' => 'Deleted tourist spot',
            'municipality_admin' => 'Municipality admin account',
            'municipality_staff' => 'Staff account',
            default => 'Archived record',
        };
    @endphp
    <article class="border-bottom py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div class="min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h3 class="h6 mb-0">{{ $archive->record_name }}</h3>
                    <span class="badge text-bg-secondary">{{ $recordLabel }}</span>
                </div>
                <div class="small text-muted">
                    @if($archive->municipality_name)
                        {{ $archive->municipality_name }}
                    @endif
                    @if($archive->record_type === 'tourist_spot' && data_get($archive->data, 'category'))
                        <span>{{ $archive->municipality_name ? ' | ' : '' }}{{ ucfirst(data_get($archive->data, 'category')) }}</span>
                    @endif
                    @if(in_array($archive->record_type, ['municipality_admin', 'municipality_staff'], true) && data_get($archive->data, 'email'))
                        <span>{{ $archive->municipality_name ? ' | ' : '' }}{{ data_get($archive->data, 'email') }}</span>
                    @endif
                </div>
                @if($archive->record_type === 'tourist_spot' && data_get($archive->data, 'address'))
                    <div class="small mt-2">{{ data_get($archive->data, 'address') }}</div>
                @endif
            </div>
            <div class="small text-muted text-md-end">
                <div>Deleted {{ optional($archive->archived_at)->format('M d, Y h:i A') }}</div>
                @if($archive->archived_by_name)
                    <div>By {{ $archive->archived_by_name }}</div>
                @endif
            </div>
        </div>
    </article>
@empty
    <div class="text-center text-muted py-5 border rounded">
        <i class="fas fa-box-open fa-2x mb-3" aria-hidden="true"></i>
        <div>No archived records in this category.</div>
    </div>
@endforelse

<div class="mt-3">{{ $archives->links() }}</div>
@endif
@endsection