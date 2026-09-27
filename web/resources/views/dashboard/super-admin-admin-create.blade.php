@extends('layouts.app')

@section('title', 'Add Municipality Admin - Super Admin')
@section('header', 'Add Municipal Admin')

@section('content')
<style>
    .admin-create-page { --tourism-teal: #0f766e; --tourism-ink: #173f43; }
    .admin-create-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-create-page .scope-note { background: #effaf7; border: 1px solid #c8e5da; color: var(--tourism-ink); border-radius: 8px; }
    .admin-create-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .admin-create-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
</style>
<div class="admin-create-page">
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-1">Add Municipal Admin</h5>
                <small class="text-muted">Create an account for one municipality in Pangasinan 2nd District.</small>
            </div>
            <div class="card-body">
                @include('dashboard.partials.municipality-admin-form', [
                    'municipalityOptions' => $municipalities,
                    'formPrefix' => 'create-admin',
                ])
            </div>
        </div>
    </div>
</div>
</div>
@endsection
