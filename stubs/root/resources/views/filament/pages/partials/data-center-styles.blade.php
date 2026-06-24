@once
    <style>
        .df-page {
            display: grid;
            gap: 1rem;
        }

        .df-toolbar {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .df-muted {
            color: rgb(148 163 184);
            font-size: .875rem;
        }

        .df-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .df-card {
            background: rgb(15 23 42 / .82);
            border: 1px solid rgb(148 163 184 / .16);
            border-radius: .75rem;
            box-shadow: 0 1px 0 rgb(255 255 255 / .03) inset;
            min-width: 0;
        }

        .df-card-pad {
            padding: 1rem;
        }

        .df-card-heading {
            align-items: center;
            border-bottom: 1px solid rgb(148 163 184 / .14);
            display: flex;
            justify-content: space-between;
            padding: .875rem 1rem;
        }

        .df-card-title {
            color: rgb(248 250 252);
            font-size: .95rem;
            font-weight: 650;
        }

        .df-card-body {
            padding: 1rem;
        }

        .df-metric {
            display: grid;
            gap: .55rem;
            min-height: 7.25rem;
        }

        .df-metric-label {
            color: rgb(148 163 184);
            font-size: .78rem;
            font-weight: 650;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .df-metric-value {
            color: rgb(248 250 252);
            font-size: 2rem;
            font-weight: 720;
            line-height: 1;
        }

        .df-metric-note {
            color: rgb(148 163 184);
            font-size: .875rem;
        }

        .df-select {
            background: rgb(15 23 42);
            border: 1px solid rgb(148 163 184 / .28);
            border-radius: .5rem;
            color: rgb(226 232 240);
            font-size: .875rem;
            min-height: 2.25rem;
            padding: .35rem 2rem .35rem .75rem;
        }

        .df-table-wrap {
            overflow-x: auto;
        }

        .df-table {
            border-collapse: collapse;
            min-width: 100%;
            text-align: left;
        }

        .df-table th {
            border-bottom: 1px solid rgb(148 163 184 / .16);
            color: rgb(148 163 184);
            font-size: .75rem;
            font-weight: 700;
            padding: .6rem .75rem;
        }

        .df-table td {
            border-bottom: 1px solid rgb(148 163 184 / .1);
            color: rgb(226 232 240);
            font-size: .875rem;
            padding: .75rem;
            vertical-align: top;
        }

        .df-table tr:last-child td {
            border-bottom: 0;
        }

        .df-table .df-number {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            text-align: right;
            white-space: nowrap;
        }

        .df-list {
            display: grid;
            gap: .75rem;
        }

        .df-row {
            align-items: center;
            display: flex;
            gap: .75rem;
            justify-content: space-between;
            min-width: 0;
        }

        .df-row-label {
            color: rgb(226 232 240);
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .df-row-value {
            color: rgb(248 250 252);
            flex: 0 0 auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-weight: 650;
        }

        .df-bar-track {
            background: rgb(30 41 59);
            border-radius: 999px;
            height: .45rem;
            overflow: hidden;
        }

        .df-bar-fill {
            background: rgb(245 158 11);
            border-radius: inherit;
            height: 100%;
        }

        .df-trend {
            align-items: end;
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(var(--df-days), minmax(0, 1fr));
            min-height: 14rem;
        }

        .df-trend-item {
            display: grid;
            gap: .5rem;
            min-width: 0;
        }

        .df-trend-bar {
            align-items: end;
            background: rgb(30 41 59 / .72);
            border-radius: .5rem;
            display: flex;
            height: 9rem;
            padding: .25rem;
        }

        .df-trend-fill {
            background: rgb(245 158 11);
            border-radius: .35rem;
            width: 100%;
        }

        .df-trend-meta {
            color: rgb(148 163 184);
            font-size: .7rem;
            text-align: center;
        }

        .df-tabs {
            border-bottom: 1px solid rgb(148 163 184 / .16);
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .df-tab {
            border-bottom: 2px solid transparent;
            color: rgb(148 163 184);
            padding: .65rem .85rem;
        }

        .df-tab-active {
            border-color: rgb(245 158 11);
            color: rgb(251 191 36);
        }

        .df-empty {
            color: rgb(148 163 184);
            padding: 2rem 1rem;
            text-align: center;
        }

        @media (max-width: 900px) {
            .df-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .df-grid {
                grid-template-columns: 1fr;
            }

            .df-trend {
                grid-template-columns: repeat(7, minmax(2.25rem, 1fr));
                overflow-x: auto;
            }
        }
    </style>
@endonce
