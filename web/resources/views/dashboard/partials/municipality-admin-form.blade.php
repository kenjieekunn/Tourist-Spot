<div class="scope-note p-3 mb-3"><i class="fas fa-lock me-2"></i><strong>2nd District scope locked</strong><div class="small mt-1">Only municipalities covered by the Pangasinan 2nd District admin system are available. Municipality assignment stays fixed after creation.</div></div>
<form action="{{ route('super-admin.admins.store') }}" method="POST" class="row g-3">
    @csrf
    <div class="col-md-6">
        <label for="{{ $formPrefix }}-name" class="form-label">Full Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="{{ $formPrefix }}-name" name="name" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $formPrefix }}-login" class="form-label">Email/Username</label>
        <input type="text" class="form-control @error('login') is-invalid @enderror" id="{{ $formPrefix }}-login" name="login" value="{{ old('login') }}" placeholder="admin@example.com or admin_username" required>
        @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $formPrefix }}-municipality" class="form-label">Municipality <span class="badge rounded-pill text-bg-light border"><i class="fas fa-lock me-1"></i>2nd District</span></label>
        <select class="form-select @error('municipality_id') is-invalid @enderror" id="{{ $formPrefix }}-municipality" name="municipality_id" required>
            @if($municipalityOptions->isEmpty())
                <option value="">All municipalities already have an admin</option>
            @else
                <option value="">Select Municipality</option>
                @foreach($municipalityOptions as $municipality)
                    <option value="{{ $municipality->id }}" @selected((string) old('municipality_id', $selectedMunicipalityId) === (string) $municipality->id)>{{ $municipality->name }}</option>
                @endforeach
            @endif
        </select>
        @error('municipality_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($municipalityOptions->isEmpty())<div class="form-text text-warning">No duplicate municipality admin can be created.</div>@endif
    </div>
    <div class="col-12"><div class="alert alert-info mb-0"><i class="fas fa-key me-2"></i>A secure temporary password will be generated automatically. The admin can change it after signing in.</div></div>
    <div class="col-md-6">
        <label for="{{ $formPrefix }}-max-staff" class="form-label">Maximum Staff Accounts</label>
        <input type="number" class="form-control @error('max_staff_accounts') is-invalid @enderror" id="{{ $formPrefix }}-max-staff" name="max_staff_accounts" value="{{ old('max_staff_accounts', 5) }}" min="0" max="10000" required>
        <div class="form-text">Set how many staff accounts this admin may create. Use 0 to allow none.</div>
        @error('max_staff_accounts')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
        <button type="submit" class="btn btn-tourism"><i class="fas fa-user-plus me-1"></i> Create 2nd District Admin</button>
    </div>
</form>