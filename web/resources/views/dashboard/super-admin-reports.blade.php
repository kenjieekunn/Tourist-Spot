@extends('layouts.app')

@section('title', 'Reports - Super Admin')
@section('header', '')

@section('content')
@php
    $reportTitles = ['management' => 'Tourist Spot Management Report', 'verification' => 'Tourist Spot Verification Report', 'reviews' => 'Tourist Reviews Report'];
    $reportTitle = $reportTitles[$reportType];
    $reportIcon = ['management' => 'fa-chart-column', 'verification' => 'fa-clipboard-check', 'reviews' => 'fa-star'][$reportType];
    $chartMax = max(1, $municipalities->max('tourist_spots_count'));
@endphp
<style>
    .reports-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .reports-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .reports-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .reports-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .report-stat { border-top: 3px solid var(--tourism-teal); }
    .report-stat .stat-icon { color: var(--tourism-teal); font-size: 1.15rem; }
    .official-report { color: #1f2937; background: #fff; }
    .official-report-header { border-bottom: 3px solid #0f766e; padding: 1.5rem; text-align: center; }
    .official-report-branding { align-items: center; display: grid; grid-template-columns: 1fr minmax(0, 3fr) 1fr; gap: 1rem; }
    .report-logo { height: 78px; object-fit: contain; width: 78px; }
    .report-logo:first-child { justify-self: start; }
    .report-logo:last-child { justify-self: end; }
    .official-report-copy { min-width: 0; }
    .report-mark { color: #0f766e; font-size: 2rem; }
    .official-report-header h1, .official-report-header h2, .official-report-header p { margin: 0; }
    .official-report-header h1 { color: #173f43; font-size: 1.35rem; letter-spacing: .04em; text-transform: uppercase; }
    .official-report-header h2 { font-size: 1.1rem; margin-top: .45rem; text-transform: uppercase; }
    .report-period { margin-top: .8rem !important; font-weight: 600; }
    .report-section-title { border-bottom: 2px solid #0f766e; color: #0f766e; font-size: .95rem; font-weight: 800; letter-spacing: .08em; margin: 1.25rem 1.5rem .75rem; padding-bottom: .35rem; text-transform: uppercase; }
    .report-summary { margin: 0 1.5rem; width: calc(100% - 3rem); }
    .report-summary td { border: 0; padding: .28rem 0; }
    .report-summary td:last-child { font-weight: 700; text-align: right; }
    .report-detail-table { margin: 0 1.5rem; width: calc(100% - 3rem); }
    .report-detail-table th, .report-detail-table td { border: 1px solid #9ca3af; padding: .5rem .6rem; vertical-align: top; }
    .report-detail-table th { background: #e6f5f1; color: #173f43; font-size: .78rem; text-transform: uppercase; }
    .municipality-divider td { background: #eef8f5; color: #0f766e; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .report-chart { margin: 0 1.5rem 1rem; }
    .chart-row { align-items: center; display: flex; gap: .6rem; margin: .35rem 0; }
    .chart-label { flex: 0 0 8rem; font-size: .78rem; }
    .chart-track { background: #e5efec; flex: 1; height: .55rem; }
    .chart-fill { background: linear-gradient(90deg, #0f766e, #3f7d42); height: 100%; }
    .report-signoff { display: flex; gap: 3rem; margin: 2rem 1.5rem 1.5rem; page-break-inside: avoid; }
    .report-signoff div { flex: 1; }
    .report-signoff p { margin: .55rem 0; }
    .report-signature-value { text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 2px; }
    .report-footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: .75rem; margin: 1.5rem; padding-top: .5rem; text-align: center; }
    .report-preview-empty { border: 1px dashed #9bd2c7; border-radius: 10px; color: #55736e; padding: 4rem 1rem; text-align: center; }
    .filter-section + .filter-section { border-top: 1px solid #e3ece8; margin-top: 1.25rem; padding-top: 1.25rem; }
    .filter-section-title { color: var(--tourism-ink); font-size: .78rem; font-weight: 800; margin-bottom: .85rem; text-transform: uppercase; }
    .filter-fields { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .filter-field { min-width: 0; }
    .date-range-control { border: 1px solid #ced4da; border-radius: .375rem; display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); overflow: hidden; }
    .date-range-control input { border: 0; border-radius: 0; min-width: 0; }
    .date-range-control input:focus { box-shadow: inset 0 0 0 1px var(--tourism-teal); z-index: 1; }
    .date-range-separator { align-items: center; background: #f4f8f6; color: #64736f; display: flex; padding: 0 .55rem; }
    .date-presets { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .55rem; }
    .date-preset { background: #f4f8f6; border: 1px solid #dce9e5; border-radius: 4px; color: #285b55; font-size: .75rem; padding: .25rem .5rem; }
    .date-preset:hover, .date-preset:focus { background: #e4f2ed; border-color: #9bc9bd; }
    .report-filter-actions { align-items: flex-end; display: flex; flex-direction: column; grid-column: 1 / -1; }
    .report-filter-actions .form-check { align-self: flex-start; }
    .report-filter-actions .btn { min-width: 190px; }
    .active-filter-list { display: flex; flex-wrap: wrap; gap: .5rem; }
    .active-filter-chip { align-items: center; background: #e9f5f1; border: 1px solid #c6e2d9; border-radius: 999px; color: #20564e; display: inline-flex; font-size: .82rem; gap: .5rem; max-width: 100%; padding: .3rem .4rem .3rem .75rem; }
    .active-filter-chip span { overflow-wrap: anywhere; }
    .active-filter-chip button { align-items: center; background: transparent; border: 0; border-radius: 50%; color: inherit; display: inline-flex; height: 1.35rem; justify-content: center; padding: 0; width: 1.35rem; }
    .active-filter-chip button:hover, .active-filter-chip button:focus { background: #cde8de; }
    @media (max-width: 575.98px) { .filter-fields { grid-template-columns: minmax(0, 1fr); } .report-filter-actions { align-items: stretch; } .report-filter-actions .btn { width: 100%; } }
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        body * { visibility: hidden !important; }
        #official-report, #official-report * { visibility: visible !important; }
        #official-report { left: 0 !important; position: absolute !important; top: 0 !important; }
        .sidebar, .report-controls, .navbar-custom, .main-col > header, .main-col > .alert, .sidebar-overlay, .sidebar-toggle { display: none !important; }
        .main-col, .main-content, .reports-page { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .official-report { border: 0 !important; border-radius: 0 !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; }
        .official-report-header { padding: .5rem 0 1rem; }
        .report-section-title, .report-summary, .report-detail-table, .report-chart, .report-signoff, .report-footer { margin-left: 0; margin-right: 0; }
        .report-footer { bottom: 0; left: 0; margin: 0; position: fixed; right: 0; }
        .report-page-number::after { content: 'Page ' counter(page) ' of ' counter(pages); }
        .municipality-divider { page-break-after: avoid; }
        tr { page-break-inside: avoid; }
    }
</style>

<div class="reports-page">
    <div class="report-controls card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('super-admin.reports') }}" id="reportForm">
                <input type="hidden" name="generated" value="1">
                <section class="filter-section" aria-labelledby="filterResultsTitle">
                    <h2 class="filter-section-title" id="filterResultsTitle">Filter Results</h2>
                    <div class="filter-fields">
                        <div class="filter-field"><label for="spotName" class="form-label">Tourist Spot Name</label><input type="search" name="spot_name" id="spotName" value="{{ $spotName }}" class="form-control" placeholder="Search spot name"></div>
                        <div class="filter-field"><label for="municipalitySearch" class="form-label">Municipality</label><input type="search" id="municipalitySearch" class="form-control" list="municipalityOptions" value="{{ optional($municipalities->firstWhere('id', $municipalityId))->name }}" placeholder="Type to search municipalities" autocomplete="off"><input type="hidden" name="municipality_id" id="municipalityId" value="{{ $municipalityId }}"><datalist id="municipalityOptions">@foreach($municipalities as $municipality)<option value="{{ $municipality->name }}" data-id="{{ $municipality->id }}"></option>@endforeach</datalist></div>
                        <div class="filter-field"><label for="reportStatus" class="form-label">Status</label><select name="status" id="reportStatus" class="form-select"><option value="all" @selected($status === 'all')>All Statuses</option><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Verified/Approved</option></select></div>
                    </div>
                </section>
                <section class="filter-section" aria-labelledby="reportOptionsTitle">
                    <h2 class="filter-section-title" id="reportOptionsTitle">Report Options</h2>
                    <div class="filter-fields">
                        <div class="filter-field"><label for="reportType" class="form-label">Report Type</label><select name="report_type" id="reportType" class="form-select"><option value="management" @selected($reportType === 'management')>All Reports</option><option value="verification" @selected($reportType === 'verification')>Verification Report</option><option value="reviews" @selected($reportType === 'reviews')>Reviews Report</option></select><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="include_chart" value="1" id="includeChart" @checked(request()->boolean('include_chart'))><label class="form-check-label" for="includeChart">Include summary chart</label></div></div>
                        <div class="filter-field"><label class="form-label" for="dateFrom">Date Range</label><div class="date-range-control" role="group" aria-label="Date range"><input type="date" name="date_from" id="dateFrom" value="{{ $dateFrom }}" class="form-control" aria-label="Start date"><span class="date-range-separator" aria-hidden="true">to</span><input type="date" name="date_to" id="dateTo" value="{{ $dateTo }}" class="form-control" aria-label="End date"></div><div class="date-presets" aria-label="Date range presets"><button type="button" class="date-preset" data-date-preset="7">Last 7 days</button><button type="button" class="date-preset" data-date-preset="month">This month</button><button type="button" class="date-preset" data-date-preset="year">This year</button></div></div>
                        <div class="filter-field report-filter-actions"><button type="submit" class="btn btn-tourism" id="generateReport"><i class="fas fa-file-lines me-1"></i> Generate Report</button></div>
                    </div>
                </section>
            </form>
        </div>
    </div>

    @php
        $activeFilters = [];
        if ($spotName !== '') $activeFilters[] = ['name' => 'spot_name', 'label' => 'Tourist Spot: ' . $spotName];
        if ($municipalityId > 0) $activeFilters[] = ['name' => 'municipality_id', 'label' => 'Municipality: ' . optional($municipalities->firstWhere('id', $municipalityId))->name];
        if ($status !== 'all') $activeFilters[] = ['name' => 'status', 'label' => 'Status: ' . ($status === 'approved' ? 'Verified/Approved' : 'Pending')];
        if ($dateFrom !== '' || $dateTo !== '') $activeFilters[] = ['name' => 'date_range', 'label' => 'Date Range: ' . ($dateFrom ?: 'Any date') . ' to ' . ($dateTo ?: 'Any date')];
    @endphp
    @if(count($activeFilters))
        <div class="report-controls mb-3" aria-label="Active filters"><div class="active-filter-list">
            @foreach($activeFilters as $filter)
                <div class="active-filter-chip"><span>{{ $filter['label'] }}</span><button type="button" data-clear-filter="{{ $filter['name'] }}" aria-label="Remove {{ $filter['label'] }}"><i class="fas fa-times" aria-hidden="true"></i></button></div>
            @endforeach
        </div></div>
    @endif

    @if(!$hasGeneratedReport)
        <div class="report-preview-empty report-controls"><i class="fas fa-file-circle-plus fa-2x mb-3" style="color: var(--tourism-teal);"></i><h5>Choose filters, then generate a report preview</h5><p class="mb-0">The official report will appear here after generation.</p></div>
    @else
        <div class="report-controls d-flex justify-content-end mb-3"><button type="button" class="btn btn-tourism" onclick="printReport()"><i class="fas fa-print me-1"></i> Print / Export PDF</button></div>
        <div id="official-report" class="card official-report">
            <header class="official-report-header"><div class="official-report-branding"><img class="report-logo" src="{{ asset('assets/report-logos/provincialtourismlogo.png') }}" alt="Provincial Tourism logo"><div class="official-report-copy"><div class="report-mark"><i class="fas {{ $reportIcon }}"></i></div><p>REPUBLIC OF THE PHILIPPINES</p><p>PROVINCE OF PANGASINAN</p><p>TOURISM OFFICE / TOURISM SYSTEM</p><h1>{{ $reportTitle }}</h1><h2>2nd District of Pangasinan</h2><p class="report-period">Report Period: {{ $reportPeriod }}</p>@if($spotName)<p class="report-period">Tourist Spot: {{ $spotName }}</p>@endif</div><img class="report-logo" src="{{ asset('assets/report-logos/pangasinanlogo.png') }}" alt="Pangasinan logo"></div></header>

            @if($reportType === 'management')
                <div class="report-section-title">Summary</div><table class="report-summary"><tbody><tr><td>Total Tourist Spots</td><td>{{ $totalSpots }}</td></tr><tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr><tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr><tr><td>Active</td><td>{{ $activeSpots }}</td></tr><tr><td>Municipalities with 0 spots</td><td>{{ $emptyMunicipalities }}</td></tr></tbody></table>
                @if(request()->boolean('include_chart'))<div class="report-section-title">Spots by Municipality</div><div class="report-chart">@foreach($municipalities as $municipality)<div class="chart-row"><span class="chart-label">{{ $municipality->name }}</span><span class="chart-track"><span class="chart-fill d-block" style="width: {{ ($municipality->tourist_spots_count / $chartMax) * 100 }}%"></span></span><strong>{{ $municipality->tourist_spots_count }}</strong></div>@endforeach</div>@endif
                <div class="report-section-title">Detailed Report</div><table class="report-detail-table"><thead><tr><th>No.</th><th>Municipality / Tourist Spot</th><th>Status</th><th>Date Added</th><th>Date Verified</th></tr></thead><tbody>@php $reportNumber = 0; @endphp @forelse($touristSpots as $municipalityName => $spots)<tr class="municipality-divider"><td colspan="5">{{ $municipalityName }} ({{ $spots->count() }} spots)</td></tr>@foreach($spots as $spot)@php $reportNumber++; @endphp<tr><td>{{ $reportNumber }}</td><td><strong>{{ $spot->name }}</strong><br><small>{{ $spot->address ?: 'No address provided' }}</small></td><td>{{ $hasVerificationStatus ? ucfirst($spot->verification_status ?? 'pending') : (in_array($spot->status, ['open', 'active']) ? 'Active' : ucfirst($spot->status ?? 'Unknown')) }}</td><td>{{ optional($spot->created_at)->format('M d, Y') }}</td><td>{{ $spot->verification_status === 'approved' ? optional($spot->updated_at)->format('M d, Y') : 'Pending' }}</td></tr>@endforeach @empty<tr><td colspan="5" class="text-center py-4">No tourist spots have been added</td></tr>@endforelse</tbody></table>
            @elseif($reportType === 'verification')
                <div class="report-section-title">Verification Summary</div><table class="report-summary"><tbody><tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr><tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr><tr><td>Municipalities Covered</td><td>{{ $totalMunicipalities }}</td></tr></tbody></table>
                <div class="report-section-title">Verification Details</div><table class="report-detail-table"><thead><tr><th>No.</th><th>Municipality / Tourist Spot</th><th>Submitted By</th><th>Date Added</th><th>Status</th></tr></thead><tbody>@php $verificationNumber = 0; @endphp @forelse($verificationSpots as $spot)@php $verificationNumber++; $submitter = $spot->creator?->name ?? $spot->municipality?->admins?->first()?->name ?? 'Unassigned municipality admin'; @endphp<tr><td>{{ $verificationNumber }}</td><td><strong>{{ $spot->municipality?->name ?? 'Unknown Municipality' }}</strong><br>{{ $spot->name }}</td><td>{{ $submitter }}</td><td>{{ optional($spot->created_at)->format('M d, Y') }}</td><td>{{ $hasVerificationStatus && $spot->verification_status === 'approved' ? 'Verified' : 'Pending' }}</td></tr>@empty<tr><td colspan="5" class="text-center py-4">No verification records found</td></tr>@endforelse</tbody></table>
            @else
                <div class="report-section-title">Review Summary</div><table class="report-summary"><tbody><tr><td>Total Reviews</td><td>{{ $reportReviews->count() }}</td></tr><tr><td>Approved Reviews</td><td>{{ $reportReviews->where('status', 'approved')->count() }}</td></tr><tr><td>Pending Reviews</td><td>{{ $reportReviews->where('status', 'pending')->count() }}</td></tr></tbody></table><div class="report-section-title">Review Details</div><table class="report-detail-table"><thead><tr><th>No.</th><th>Tourist Spot</th><th>Municipality</th><th>Reviewer</th><th>Date</th><th>Status</th></tr></thead><tbody>@forelse($reportReviews as $review)<tr><td>{{ $loop->iteration }}</td><td>{{ $review->touristSpot?->name ?? 'Unknown Spot' }}</td><td>{{ $review->touristSpot?->municipality?->name ?? 'Unknown Municipality' }}</td><td>{{ $review->user_name ?: 'Visitor' }}</td><td>{{ optional($review->created_at)->format('M d, Y') }}</td><td>{{ ucfirst($review->status ?? 'pending') }}</td></tr>@empty<tr><td colspan="6" class="text-center py-4">No reviews found</td></tr>@endforelse</tbody></table>
            @endif

            <div class="report-signoff"><div><p>Prepared by: <span class="report-signature-value">Provincial Tourism and Cultural Affairs Office</span></p><p>Date: <span class="report-signature-value">{{ now()->format('F j, Y') }}</span></p></div><div><p>Approved by: ____________________________________</p><p>Date: __________________________________________</p></div></div>
            <footer class="report-footer"><span class="report-page-number"></span></footer>
        </div>
    @endif
</div>

<script>
    const reportForm = document.getElementById('reportForm');
    const municipalitySearch = document.getElementById('municipalitySearch');
    const municipalityId = document.getElementById('municipalityId');
    const municipalityOptions = Array.from(document.querySelectorAll('#municipalityOptions option'));
    municipalitySearch.addEventListener('input', function () {
        const match = municipalityOptions.find(option => option.value.toLowerCase() === municipalitySearch.value.trim().toLowerCase());
        municipalityId.value = match ? match.dataset.id : '0';
        municipalitySearch.setCustomValidity(municipalitySearch.value.trim() && !match ? 'Choose a municipality from the suggestions.' : '');
    });
    reportForm.addEventListener('submit', function () {
        const button = document.getElementById('generateReport');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Generating...';
    });
    document.querySelectorAll('[data-date-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
            const end = new Date();
            const start = new Date(end);
            const preset = button.dataset.datePreset;
            if (preset === '7') start.setDate(start.getDate() - 6);
            if (preset === 'month') start.setDate(1);
            if (preset === 'year') start.setMonth(0, 1);
            const formatDate = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
            document.getElementById('dateFrom').value = formatDate(start);
            document.getElementById('dateTo').value = formatDate(end);
        });
    });
    document.querySelectorAll('[data-clear-filter]').forEach(function (button) {
        button.addEventListener('click', function () {
            const filter = button.dataset.clearFilter;
            if (filter === 'date_range') {
                document.getElementById('dateFrom').value = '';
                document.getElementById('dateTo').value = '';
            } else {
                const field = reportForm.elements[filter];
                field.value = filter === 'municipality_id' ? '0' : (filter === 'status' ? 'all' : '');
                if (filter === 'municipality_id') municipalitySearch.value = '';
            }
            reportForm.requestSubmit();
        });
    });
    function printReport() { window.print(); }
</script>
@endsection
