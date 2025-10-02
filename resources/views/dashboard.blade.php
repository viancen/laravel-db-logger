<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Logs Dashboard</title>
    <link rel="stylesheet" href="{{ asset('vendor/db-logger/css/logs-dashboard.css') }}">
</head>
<body>
<div id="logs-app">
    <div class="card pad">
        <h1>📋 Logs Dashboard</h1>
        <div class="hint">
            Filter en doorzoek logs • Dubbelklik op een rij voor details • <code class="kbd">Ctrl</code>+klik voor meervoudige selectie
        </div>

        <form id="filters" onsubmit="return false">
            <div class="filters-grid">
                <div class="form-group">
                    <input type="datetime-local" id="f-from" class="form-control" placeholder=" " value="{{ $defaults['from'] }}">
                    <label class="form-label">Van</label>
                </div>
                <div class="form-group">
                    <input type="datetime-local" id="f-to" class="form-control" placeholder=" " value="{{ $defaults['to'] }}">
                    <label class="form-label">Tot</label>
                </div>
                <div class="form-group">
                    <div class="custom-multiselect" id="levels-select">
                        <button type="button" class="form-control multiselect-trigger">
                            <span class="multiselect-value">Selecteer levels...</span>
                            <svg class="multiselect-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M3 5L6 8L9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <div class="multiselect-dropdown">
                            @foreach($levels as $num => $name)
                                <label class="multiselect-option">
                                    <input type="checkbox"
                                           name="levels[]"
                                           value="{{ $num }}"
                                            {{ in_array((string)$num,$defaults['levels']) ? 'checked' : '' }}>
                                    <span>{{ $num }} — {{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <label class="form-label">Level</label>
                </div>
                <div class="form-group">
                    <input type="text" id="f-channel" class="form-control" placeholder=" ">
                    <label class="form-label">Channel</label>
                </div>
                <div class="form-group">
                    <input type="search" id="f-q" class="form-control" placeholder=" ">
                    <label class="form-label">Zoeken</label>
                </div>
                <div class="form-group">
                    <input type="text" id="f-user" class="form-control" placeholder=" ">
                    <label class="form-label">User ID</label>
                </div>
                <div class="form-group">
                    <input type="text" id="f-rid" class="form-control" placeholder=" ">
                    <label class="form-label">Request ID</label>
                </div>
                <div class="form-group">
                    <input type="text" id="f-ip" class="form-control" placeholder=" ">
                    <label class="form-label">IP Address</label>
                </div>
                <div class="form-group">
                    <select id="f-per" class="form-control">
                        @foreach([25,50,100,200] as $pp)
                            <option value="{{ $pp }}" {{ $pp==$defaults['per_page'] ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Per pagina</label>
                </div>
                <div class="form-group">
                    <select id="f-sort" class="form-control">
                        @foreach(['created_at'=>'Datum','level'=>'Level','channel'=>'Channel','id'=>'ID'] as $k=>$v)
                            <option value="{{ $k }}" {{ $k==$defaults['sort'] ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Sorteer op</label>
                </div>
                <div class="form-group">
                    <select id="f-dir" class="form-control">
                        <option value="desc" {{ $defaults['dir']==='desc' ? 'selected' : '' }}>Nieuwste eerst</option>
                        <option value="asc" {{ $defaults['dir']==='asc' ? 'selected' : '' }}>Oudste eerst</option>
                    </select>
                    <label class="form-label">Richting</label>
                </div>
            </div>

            <div class="actions-bar">
                <button type="button" class="btn" id="btn-reset">Reset</button>
                <button type="button" class="btn primary" id="btn-apply">Toepassen</button>
                <label class="checkbox-label">
                    <input id="f-auto" type="checkbox"/>
                    Auto-refresh (10s)
                </label>
                <button type="button" class="btn" id="btn-export">Export JSON</button>
                <div class="spacer"></div>
                <span id="meta" class="small">—</span>
            </div>
        </form>
    </div>

    <div class="card pad" style="margin-top:12px">
        <div class="table-container">
            <table id="grid">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tijd</th>
                    <th>Level</th>
                    <th>Channel</th>
                    <th>Message</th>
                    <th>User</th>
                    <th>Request</th>
                    <th>IP</th>
                </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="pagination">
            <button class="btn" id="prev">← Vorige</button>
            <span id="pageinfo" class="small">—</span>
            <button class="btn" id="next">Volgende →</button>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="log-modal" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Log Details</h2>
            <button class="modal-close" id="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<script>
    window.logsConfig = {
        defaults: @json($defaults),
        apiUrl: "{{ route('db-logger.data') }}"
    };
</script>
<script src="{{ asset('vendor/db-logger/js/logs.js') }}"></script>
</body>
</html>