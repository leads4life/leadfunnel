<?php
// Password protected lead viewer
session_start();

$API_PASSWORD = 'leads4life123'; // Must match submit_lead.php
// Leads storage — must match submit_lead.php. Lives outside the web root by
// default so it can't be downloaded via URL. Override with LEADS_FILE env var.
$LEADS_FILE = getenv('LEADS_FILE') ?: '/var/www/leads_store/leads_data.json';

function renderLeadCell($value) {
    $safeValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    return '<span class="lead-expandable" data-fulltext="' . $safeValue . '">' . $safeValue . '</span>';
}

// Handle login
$authenticated = false;
if (isset($_SESSION['leads_authenticated']) && $_SESSION['leads_authenticated'] === true) {
    $authenticated = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $API_PASSWORD) {
        $_SESSION['leads_authenticated'] = true;
        $authenticated = true;
    } else {
        $error = 'Invalid password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: view_leads.php');
    exit;
}

?><!DOCTYPE html>
<html>
<head>
    <title>Leads Viewer - Cheerful Agents</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 24px;
            background: linear-gradient(135deg, #f6fbff 0%, #eef7ff 100%);
            color: #18324a;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(24, 50, 74, 0.12);
            border: 1px solid rgba(24, 50, 74, 0.08);
        }
        h1 {
            color: #18324a;
            margin: 0 0 12px;
            font-size: 2rem;
        }
        .subheading {
            color: #5b748b;
            margin-bottom: 24px;
        }
        .login-card {
            max-width: 460px;
            margin: 28px auto 0;
            padding: 24px;
            border-radius: 16px;
            background: linear-gradient(145deg, #ffffff, #f4f8ff);
            box-shadow: 0 10px 30px rgba(24, 50, 74, 0.08);
            border: 1px solid #e5eef8;
        }
        .login-form input {
            width: 100%;
            padding: 12px 14px;
            margin: 10px 0 14px;
            border: 1px solid #dbe7f2;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 14px;
            background: #fbfdff;
        }
        .login-form button,
        .logout-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #2f80ed 0%, #1f63c7 100%);
            color: white;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 8px 18px rgba(31, 99, 199, 0.2);
        }
        .login-form button:hover,
        .logout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(31, 99, 199, 0.28);
        }
        .logout-btn {
            width: auto;
            margin-bottom: 18px;
        }
        .error { color: #d32f2f; margin-bottom: 20px; }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            table-layout: fixed;
            overflow: hidden;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(24, 50, 74, 0.06);
        }
        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e8eef5;
            vertical-align: top;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 0;
        }
        th {
            background: linear-gradient(135deg, #2f80ed 0%, #1f63c7 100%);
            color: white;
            font-weight: 600;
            font-size: 0.92rem;
        }
        tbody tr:nth-child(even) { background-color: #f8fbff; }
        tbody tr:hover { background-color: #edf6ff; }
        .lead-row {
            cursor: pointer;
        }
        .lead-expandable {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #314b61;
        }
        .lead-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }
        .lead-modal[hidden] {
            display: none;
        }
        .lead-modal-content {
            background: white;
            width: min(760px, 100%);
            max-height: 82vh;
            overflow: auto;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.25);
            position: relative;
            border: 1px solid #e5eef8;
        }
        .lead-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            border: none;
            background: transparent;
            font-size: 24px;
            cursor: pointer;
        }
        .lead-modal-body {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }
        .lead-field {
            background: #f8fbff;
            border: 1px solid #e3edf8;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .lead-field-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6b84a0;
            margin-bottom: 4px;
        }
        .lead-field-value {
            font-size: 0.98rem;
            color: #274257;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .logout-btn:hover { background-color: #da190b; }
        .no-leads {
            text-align: center;
            color: #6f8091;
            padding: 48px 20px;
            background: #f8fbff;
            border-radius: 14px;
            border: 1px dashed #d9e8f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Cheerful Agents - Leads Viewer</h1>
        <p class="subheading">Review incoming leads at a glance, then open any row for the full detail view.</p>

        <?php if (!$authenticated): ?>
            <div class="login-card">
                <div class="login-form">
                <?php if (isset($error)): ?>
                    <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label for="password"><strong>Enter Password</strong></label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required autofocus>
                    <button type="submit">Login</button>
                </form>
                </div>
            </div>
        <?php else: ?>
            <button class="logout-btn" onclick="window.location.href='?logout=1'">Logout</button>

            <?php
            if (file_exists($LEADS_FILE)) {
                $json_data = file_get_contents($LEADS_FILE);
                $leads = json_decode($json_data, true);

                if ($leads && count($leads) > 0):
            ?>
                <p><strong>Total Leads: <?php echo count($leads); ?></strong></p>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th><th>Source</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Buying</th>
                            <th>Selling</th>
                            <th>Buy Location</th>
                            <th>Sell Address</th>
                            <th>Property Type</th>
                            <th>Value</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($leads) as $lead): ?>
                        <tr class="lead-row" data-fulltext='{"timestamp":"<?php echo addslashes((string) ($lead['timestamp'] ?? '')); ?>","name":"<?php echo addslashes((string) ($lead['name'] ?? '')); ?>","email":"<?php echo addslashes((string) ($lead['email'] ?? '')); ?>","phone":"<?php echo addslashes((string) ($lead['phone'] ?? '')); ?>","is_buying":"<?php echo addslashes((string) ($lead['is_buying'] ?? '')); ?>","is_selling":"<?php echo addslashes((string) ($lead['is_selling'] ?? '')); ?>","buy_location":"<?php echo addslashes((string) ($lead['buy_location'] ?? '')); ?>","sell_address":"<?php echo addslashes((string) ($lead['sell_address'] ?? '')); ?>","property_type":"<?php echo addslashes((string) ($lead['property_type'] ?? '')); ?>","value":"<?php echo addslashes((string) ($lead['value'] ?? '')); ?>","notes":"<?php echo addslashes((string) ($lead['notes'] ?? '')); ?>","source":"<?php echo addslashes((string) ($lead['page_source'] ?? 'funnel')); ?>"}'>
                            <td><?php echo renderLeadCell($lead['timestamp'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['page_source'] ?? 'funnel'); ?></td>
                            <td><?php echo renderLeadCell($lead['name'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['email'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['phone'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['is_buying'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['is_selling'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['buy_location'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['sell_address'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['property_type'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['value'] ?? ''); ?></td>
                            <td><?php echo renderLeadCell($lead['notes'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-leads">
                    <p>No leads yet. Check back soon!</p>
                </div>
            <?php endif; ?>
            <?php } else { ?>
                <div class="no-leads">
                    <p>No leads yet. Check back soon!</p>
                </div>
            <?php } ?>
        <?php endif; ?>
    </div>

    <div id="lead-modal" class="lead-modal" hidden>
        <div class="lead-modal-content">
            <button class="lead-modal-close" type="button" aria-label="Close">×</button>
            <h3>Lead Details</h3>
            <div id="lead-modal-body" class="lead-modal-body"></div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('lead-modal');
            var modalBody = document.getElementById('lead-modal-body');
            var closeButton = document.querySelector('.lead-modal-close');

            function closeModal() {
                modal.hidden = true;
            }

            if (closeButton) {
                closeButton.addEventListener('click', closeModal);
            }

            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            document.querySelectorAll('.lead-row').forEach(function (row) {
                row.addEventListener('dblclick', function () {
                    if (modalBody) {
                        var raw = row.getAttribute('data-fulltext') || '{}';
                        var data = {};
                        try {
                            data = JSON.parse(raw);
                        } catch (error) {
                            data = {};
                        }

                        var fields = [
                            ['Timestamp', data.timestamp],
                            ['Name', data.name],
                            ['Email', data.email],
                            ['Phone', data.phone],
                            ['Buying', data.is_buying],
                            ['Selling', data.is_selling],
                            ['Buy Location', data.buy_location],
                            ['Sell Address', data.sell_address],
                            ['Property Type', data.property_type],
                            ['Value', data.value],
                            ['Notes', data.notes]
                        ];

                        modalBody.innerHTML = '';
                        fields.forEach(function (field) {
                            var value = field[1];
                            if (value === undefined || value === null || value === '') {
                                value = '—';
                            }
                            var fieldWrapper = document.createElement('div');
                            fieldWrapper.className = 'lead-field';
                            fieldWrapper.innerHTML = '<span class="lead-field-label">' + field[0] + '</span><div class="lead-field-value">' + (String(value).replace(/</g, '&lt;').replace(/>/g, '&gt;')) + '</div>';
                            modalBody.appendChild(fieldWrapper);
                        });
                    }
                    if (modal) {
                        modal.hidden = false;
                    }
                });
            });
        })();
    </script>
</body>
</html>
