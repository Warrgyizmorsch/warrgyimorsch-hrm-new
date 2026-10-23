{{-- Shared look for the asset pages (dashboard, asset page, incidents, audits). Include once per page. --}}
<style>
    .am-wrap { font-family: 'Inter', sans-serif; }
    .am-subnav { display: inline-flex; flex-wrap: wrap; gap: 4px; background: #f1f5f9; padding: 5px; border-radius: 30px; border: 1px solid #e2e8f0; max-width: 100%; }
    .am-subnav a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 25px; font-size: 13px; font-weight: 600; color: #64748b; text-decoration: none; white-space: nowrap; transition: all .2s; }
    .am-subnav a:hover { color: #3858f9; }
    .am-subnav a.active { background: #fff; color: #3858f9; box-shadow: 0 4px 10px rgba(0,0,0,.05); }
    .am-subnav .am-count { background: #ef4444; color: #fff; border-radius: 10px; font-size: 10px; padding: 1px 6px; }

    .am-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .am-card-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
    .am-card-header h6 { margin: 0; font-weight: 700; color: #0f172a; font-size: 15px; }
    .am-card-body { padding: 16px 20px; }

    .am-kpi { padding: 16px 18px; height: 100%; display: flex; flex-direction: column; gap: 4px; text-decoration: none; color: inherit; transition: transform .15s, box-shadow .15s; }
    a.am-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,23,42,.08); }
    .am-kpi .am-kpi-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .03em; display: flex; align-items: center; gap: 6px; }
    .am-kpi .am-kpi-value { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .am-kpi .am-kpi-sub { font-size: 12px; color: #64748b; }

    .am-table { width: 100%; margin: 0; }
    .am-table thead th { background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .am-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; }
    .am-table tbody tr:last-child td { border-bottom: 0; }
    .am-table .num { text-align: right; font-variant-numeric: tabular-nums; }

    .am-badge { font-size: 11px; padding: 5px 10px; border-radius: 30px; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; white-space: nowrap; }
    .am-tag { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #3858f9; background: rgba(56,88,249,.08); padding: 2px 8px; border-radius: 6px; white-space: nowrap; }
    .am-muted { color: #64748b; }
    .am-empty { text-align: center; padding: 28px 12px; color: #94a3b8; font-size: 13px; }
    .am-link { color: #0f172a; font-weight: 600; text-decoration: none; }
    .am-link:hover { color: #3858f9; }

    .am-timeline { list-style: none; margin: 0; padding: 0; position: relative; }
    .am-timeline li { position: relative; padding: 0 0 18px 40px; }
    .am-timeline li::before { content: ''; position: absolute; left: 15px; top: 30px; bottom: 0; width: 2px; background: #e2e8f0; }
    .am-timeline li:last-child::before { display: none; }
    .am-timeline .am-dot { position: absolute; left: 0; top: 0; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; }

    .am-dl { display: grid; grid-template-columns: minmax(110px, 40%) 1fr; gap: 8px 12px; margin: 0; font-size: 13px; }
    .am-dl dt { color: #64748b; font-weight: 500; }
    .am-dl dd { margin: 0; color: #0f172a; font-weight: 600; word-break: break-word; }

    .premium-modal { border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,.15); }
    .premium-modal .modal-header { border-bottom: 1px solid #f1f5f9; padding: 20px 24px; }
    .premium-modal .modal-title { font-weight: 700; color: #0f172a; font-size: 18px; }
    .premium-modal .form-control, .premium-modal .form-select { border-radius: 8px; }

    @media (max-width: 575.98px) {
        .am-dl { grid-template-columns: 1fr; gap: 2px; }
        .am-dl dd { margin-bottom: 8px; }
        .am-kpi .am-kpi-value { font-size: 22px; }
    }
</style>
