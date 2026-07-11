<?php
/**
 * deploy.php - Single-file Git Deployment Tool for cPanel
 * Repository: NoorGee365/ng-com
 * Created: 2026-07-01
 * Fixed: Commit history display, git pull authentication, proper formatting
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ----- Configuration -----
$PASSWORD = "1234"; // default password
$TARGET_BRANCH = "main-ng"; // change as needed
$MAX_COMMITS = 50; // number of commits to list
date_default_timezone_set('Asia/Karachi'); // Karachi timezone for commit list display
// -------------------------

// Helpers
function get_git_path() {
    static $path = null;
    if ($path !== null) return $path;

    $common_paths = [
        'git',
        '/usr/bin/git',
        '/usr/local/bin/git',
        '/usr/local/cpanel/3rdparty/bin/git',
        '/bin/git',
        '/usr/sfw/bin/git'
    ];

    foreach ($common_paths as $p) {
        if (function_exists('shell_exec')) {
            $out = @shell_exec("$p --version 2>&1");
            if ($out && stripos($out, 'git version') !== false) {
                $path = $p;
                return $path;
            }
        }
    }
    
    $path = 'git';
    return $path;
}

function safe_shell($cmd) {
    // run command if shell_exec available
    if (!function_exists('shell_exec')) return "Error: shell_exec() is disabled on this host.";
    $git_path = get_git_path();
    // Automatically replace "git" at start or after shell delimiters with the correct path
    $cmd = preg_replace('/(?<=^|;|&&|\|\||&)\s*git\b/', ' ' . $git_path, $cmd);
    // Enforce running the command inside the directory of deploy.php
    $cwd = escapeshellarg(__DIR__);
    $cmd = "cd $cwd && " . $cmd;
    $out = shell_exec($cmd . " 2>&1");
    return $out === null ? "" : $out;
}

function highlight_output($s) {
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    // Highlight keywords (case-insensitive)
    $patterns = [
        '/\b(error|fatal|aborting)\b/i',
        '/\b(success|done|ok|pushed|merged)\b/i',
        '/Already up to date/i',
        '/Already up-to-date/i'
    ];
    $replacements = [
        '<span class="text-red-400">$0</span>',
        '<span class="text-green-400">$0</span>',
        '<span class="text-blue-400">$0</span>',
        '<span class="text-blue-400">$0</span>'
    ];
    return preg_replace($patterns, $replacements, $s);
}

// Authentication handling
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: deploy.php");
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === $PASSWORD) {
        $_SESSION['auth'] = true;
    } else {
        $error = "Invalid Password";
    }
}

$is_authenticated = isset($_SESSION['auth']) && $_SESSION['auth'] === true;

// Read git identity
$git_user_name = trim(safe_shell("git config user.name"));
$git_user_email = trim(safe_shell("git config user.email"));
$has_identity = (!empty($git_user_name) && !empty($git_user_email));

$output = "";
if ($is_authenticated && isset($_POST['action'])) {
    $action = $_POST['action'];
    if (!function_exists('shell_exec')) {
        $output = "Error: shell_exec() is disabled.";
    } else {
        switch ($action) {
            case 'setup_git':
                $new_name = isset($_POST['git_name']) ? escapeshellarg($_POST['git_name']) : escapeshellarg('Nooruddin');
                $new_email = isset($_POST['git_email']) ? escapeshellarg($_POST['git_email']) : escapeshellarg('admin@noorgee.pk');
                $output = safe_shell("git config --local user.name $new_name && git config --local user.email $new_email");
                $git_user_name = trim(safe_shell("git config user.name"));
                $git_user_email = trim(safe_shell("git config user.email"));
                $has_identity = (!empty($git_user_name) && !empty($git_user_email));
                break;
            case 'pull':
                // FIXED: Proper pull with fresh fetch and checkout
                $output = safe_shell("git fetch --prune origin && git checkout $TARGET_BRANCH && git pull --ff-only origin $TARGET_BRANCH");
                break;
            case 'force_pull':
                $output = safe_shell("git fetch --prune origin && git checkout $TARGET_BRANCH && git reset --hard origin/$TARGET_BRANCH && git clean -fd");
                break;
            case 'push':
                if (!$has_identity) {
                    $output = "Identity missing. Configure git user.name and user.email first.";
                } else {
                    $msg = !empty($_POST['commit_msg']) ? trim($_POST['commit_msg']) : ("Update: " . date('Y-m-d H:i:s'));
                    // Enforce short commit title guidance (<=10 words) in UI but accept any here
                    $desc = !empty($_POST['commit_desc']) ? trim($_POST['commit_desc']) : "";
                    $safe_msg = escapeshellarg($msg . ($desc ? "\n\n" . $desc : ""));
                    $output = safe_shell("git add . && git commit -m $safe_msg || echo 'No changes to commit' && git push origin $TARGET_BRANCH");
                }
                break;
            case 'revert_last':
                $output = safe_shell("git revert --no-edit HEAD && git push origin $TARGET_BRANCH");
                break;
            case 'restore_commit':
                $hash = $_POST['commit_hash'] ?? '';
                $hash = preg_replace('/[^A-Za-z0-9]/', '', $hash);
                if (!empty($hash)) {
                    // reset to commit and force push
                    $output = safe_shell("git reset --hard $hash && git push origin $TARGET_BRANCH --force");
                } else {
                    $output = "Invalid commit hash.";
                }
                break;
            case 'undo_local':
                $output = safe_shell("git reset --hard HEAD && git clean -fd");
                break;
        }
    }
}

// Commit history and last commit details
$commit_history = [];
$last_commit_title = "";
$last_commit_desc = "";
$last_commit_time = 0;

if ($is_authenticated) {
    $delimiter = "|||";
    $record_delim = "===END===";
    // FIXED: Proper format for git log with correct date and time format
    $format = "%H$delimiter%s$delimiter%b$delimiter%at$delimiter%ai$record_delim";
    $cmd = "git log -$MAX_COMMITS --format='$format' -- 2>&1";
    $history_raw = safe_shell($cmd);
    if ($history_raw && strpos($history_raw, 'fatal') === false) {
        $parts = explode($record_delim, $history_raw);
        foreach ($parts as $idx => $raw) {
            $raw = trim($raw);
            if (empty($raw)) continue;
            $p = explode($delimiter, $raw);
            $h = $p[0] ?? '';
            $s = $p[1] ?? '';
            $b = trim($p[2] ?? '');
            $t = intval($p[3] ?? 0);
            $ai = $p[4] ?? '';
            $commit_history[] = [
                'hash' => $h,
                'short' => substr($h,0,7),
                'subject' => $s,
                'body' => $b,
                'timestamp' => $t,
                'karachi' => date('D d-M-Y H:i:s', $t),
                'iso_date' => $ai
            ];
        }
    }
    if (!empty($commit_history)) {
        $last_commit_title = $commit_history[0]['subject'];
        $last_commit_desc = $commit_history[0]['body'];
        $last_commit_time = $commit_history[0]['timestamp'];
    }
}

// Status: compare local and remote
$status_message = "Unknown";
$status_color = "text-slate-400";
if ($is_authenticated) {
    safe_shell("git fetch --prune origin");
    $local_hash = trim(safe_shell("git rev-parse HEAD"));
    $remote_hash = trim(safe_shell("git rev-parse origin/$TARGET_BRANCH"));
    if (!empty($local_hash) && $local_hash === $remote_hash) {
        $status_message = "Applied";
        $status_color = "text-green-400";
    } else {
        $status_message = "Pending / Not Applied";
        $status_color = "text-orange-400";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>NG Deployer - deploy.php</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #071129; color: #e6eef8; font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
        .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.04); }
        .terminal-box { background: #000; border-left: 3px solid #2563eb; height: 160px; overflow-y: auto; }
        .input-field { background: rgba(15,23,42,0.6); border: 1px solid rgba(71,85,105,0.3); }
        .btn-action { transition: all .15s ease; }
        .btn-action:hover { transform: translateY(-2px); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 8px; }
        .commit-title { line-height: 1.4; font-size: 1rem; }
        .commit-desc { line-height: 1.45; font-size: .95rem; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-sm">
    <div class="w-full max-w-5xl glass rounded-2xl p-5 shadow-2xl border-t-2 border-blue-500">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-blue-300">NG Main site Deploy <span class="text-xs bg-blue-500/20 px-2 py-0.5 rounded text-blue-200">DEPLOY</span></h1>
                <div class="text-sm text-slate-300">Single-file Deploy Tool</div>
                <div class="ml-4 text-xs text-slate-400">Repo: <a href="https://github.com/NoorGee365/ng-com" class="text-blue-300 hover:underline">NoorGee365/ng-com</a></div>
            </div>

            <div class="flex items-center gap-3">
                <a href="index.html" class="text-slate-300 hover:text-white text-sm"><i class="fas fa-home mr-1"></i>Home</a>
                <a href="send_message.php" class="text-slate-300 hover:text-white text-sm"><i class="fas fa-envelope mr-1"></i>Message</a>
                <button onclick="location.reload()" class="text-slate-300 hover:text-white text-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                <div class="text-xs uppercase tracking-wider text-slate-400">Status: <span class="font-mono <?php echo $status_color; ?>"><?php echo $status_message; ?></span></div>
                <div class="text-xs uppercase tracking-wider text-slate-400">Branch: <span class="text-white font-mono"><?php echo htmlspecialchars($TARGET_BRANCH); ?></span></div>
            </div>
        </div>

        <?php if (!$is_authenticated): ?>
            <form method="POST" class="max-w-xs mx-auto">
                <?php if (!empty($error)): ?><div class="mb-2 text-sm text-red-400"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <input type="password" name="password" placeholder="Password" class="w-full input-field rounded-lg px-4 py-3 text-center text-base outline-none mb-2">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-lg font-bold text-base">Access</button>
            </form>
        <?php else: ?>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-5 space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <form method="POST"><input type="hidden" name="action" value="pull">
                            <button type="submit" class="w-full py-3 bg-green-600/10 hover:bg-green-600/20 border border-green-600/30 rounded-xl text-sm font-bold text-green-300 btn-action"><i class="fas fa-arrow-down mr-2"></i>Pull</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Force Pull will discard local changes. Continue?')"><input type="hidden" name="action" value="force_pull">
                            <button type="submit" class="w-full py-3 bg-blue-600/10 hover:bg-blue-600/20 border border-blue-600/30 rounded-xl text-sm font-bold text-blue-300 btn-action"><i class="fas fa-bolt mr-2"></i>Force Pull</button>
                        </form>
                    </div>

                    <div class="bg-slate-900/40 border border-slate-700/50 p-3 rounded-xl space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-blue-400 uppercase">Restore History</span>
                            <form method="POST" onsubmit="return confirm('Revert last commit?')"><input type="hidden" name="action" value="revert_last">
                                <button type="submit" class="text-[11px] bg-orange-500/20 hover:bg-orange-500/30 text-orange-400 px-2 py-1 rounded border border-orange-500/30">Revert Last</button>
                            </form>
                        </div>

                        <div class="space-y-2">
                            <select id="commit_select" class="w-full input-field rounded-lg px-2 py-2 text-sm outline-none" onchange="handleCommitSelect()">
                                <option value="">Select commit...</option>
                                <?php foreach ($commit_history as $i => $c): ?>
                                    <option value="<?php echo $c['hash']; ?>" data-subject="<?php echo htmlspecialchars($c['subject']); ?>" data-body="<?php echo htmlspecialchars($c['body']); ?>" data-date="<?php echo htmlspecialchars($c['karachi']); ?>">
                                        #<?php echo $i+1; ?> [<?php echo $c['short']; ?>] <?php echo $c['karachi']; ?> - <?php echo htmlspecialchars($c['subject']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div id="c_preview" class="hidden bg-slate-800/50 border border-slate-600/50 rounded-lg p-2 space-y-2">
                                <div class="text-xs text-slate-300">
                                    <div class="font-bold text-blue-400 mb-1" id="c_subject"></div>
                                    <div class="text-slate-400 text-[11px] mb-2" id="c_date"></div>
                                    <div class="text-slate-400 italic h-32 overflow-y-auto whitespace-pre-wrap leading-relaxed text-sm" id="c_desc"></div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="cancelRestore()" class="flex-1 bg-slate-700/50 hover:bg-slate-600/50 text-slate-200 py-2 rounded text-sm font-bold uppercase border border-slate-600/50">Cancel</button>
                                    <form method="POST" class="flex-1" id="restore_form">
                                        <input type="hidden" name="action" value="restore_commit">
                                        <input type="hidden" name="commit_hash" id="restore_hash">
                                        <button type="submit" onclick="return confirm('Restore to selected commit? This will reset working tree.')" class="w-full bg-red-600/20 hover:bg-red-600/30 text-red-300 py-2 rounded font-bold uppercase border border-red-600/30 text-sm">Restore</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <form method="POST" onsubmit="return confirm('Undo local changes? This will reset to HEAD.')" class="flex-1"><input type="hidden" name="action" value="undo_local">
                            <button type="submit" class="w-full bg-red-900/10 hover:bg-red-900/20 border border-red-900/30 py-3 rounded-lg text-sm text-red-400 font-bold uppercase">Undo Local</button>
                        </form>
                        <a href="?logout=1" class="flex-1 bg-slate-800 hover:bg-slate-700 py-3 rounded-lg text-sm text-slate-300 font-bold uppercase text-center flex items-center justify-center"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
                    </div>

                    <div class="mt-2 text-xs text-slate-400 space-y-1">
                        <div>Git Identity: <span class="text-slate-100 font-mono"><?php echo htmlspecialchars($git_user_name . ' <' . $git_user_email . '>'); ?></span></div>
                        <div>Git Path: <span class="text-slate-100 font-mono"><?php echo htmlspecialchars(get_git_path()); ?></span></div>
                        <div class="text-xxs text-slate-500">Note: When editing, write a commit title within ~10 words and detailed extended description. (Manus AI instruction)</div>
                    </div>

                    <div class="bg-slate-900/30 border border-slate-700/40 rounded-lg p-3">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-sm font-bold text-blue-300">Recent Commits (<?php echo count($commit_history); ?>)</div>
                            <div class="text-xs text-slate-400">Timezone: Karachi</div>
                        </div>
                        <div class="space-y-2 max-h-56 overflow-y-auto">
                            <?php foreach ($commit_history as $i => $c): ?>
                                <div class="p-2 rounded border border-slate-700/40 bg-slate-800/20">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="text-sm font-bold text-blue-200">#<?php echo $i+1; ?> [<?php echo $c['short']; ?>] <span class="text-slate-300"><?php echo htmlspecialchars($c['subject']); ?></span></div>
                                            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($c['karachi']); ?></div>
                                        </div>
                                        <div class="text-xs text-slate-400 font-mono">ID: <?php echo htmlspecialchars($c['short']); ?></div>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-200 whitespace-pre-wrap"><?php echo htmlspecialchars($c['body'] ?: '- No extended description -'); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-span-7 space-y-3">
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="push">
                        <div class="grid grid-cols-1 gap-2">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-blue-400 uppercase ml-1 flex justify-between">
                                    <span>Commit Highlight</span>
                                    <span id="time-remaining" class="text-slate-400 lowercase font-normal text-sm"></span>
                                </label>
                                <textarea name="commit_msg" rows="2" placeholder="e.g., feat: add new feature" class="w-full input-field rounded-lg px-3 py-3 text-base outline-none commit-title resize-none"><?php echo htmlspecialchars($last_commit_title); ?></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-blue-400 uppercase ml-1">Extended Description</label>
                                <textarea name="commit_desc" rows="10" placeholder="Provide more details about the changes..." class="w-full input-field rounded-lg px-3 py-3 text-base outline-none commit-desc resize-none"><?php echo htmlspecialchars($last_commit_desc); ?></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 py-3 rounded-xl font-bold text-base flex items-center justify-center gap-2 shadow-lg shadow-blue-900/50"><i class="fas fa-cloud-upload-alt"></i>Push Changes</button>
                            <button type="button" onclick="location.reload()" class="px-4 bg-slate-700 hover:bg-slate-600 py-3 rounded-xl text-sm text-slate-200">Refresh</button>
                        </div>
                    </form>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">Terminal Output</label>
                        <pre class="terminal-box p-3 rounded-xl text-xs font-mono leading-relaxed whitespace-pre-wrap"><?php
                            if ($output) {
                                echo highlight_output($output);
                            } else {
                                echo '<span class="text-slate-600">Waiting for action...</span>';
                            }
                        ?></pre>
                    </div>



                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const lastUpdate = <?php echo json_encode($last_commit_time); ?>;
        function updateTime() {
            if (!lastUpdate) return;
            const now = Math.floor(Date.now() / 1000);
            const diff = now - lastUpdate;
            let timeStr = "";
            if (diff < 60) timeStr = diff + "s ago";
            else if (diff < 3600) timeStr = Math.floor(diff / 60) + "m ago";
            else if (diff < 86400) timeStr = Math.floor(diff / 3600) + "h ago";
            else timeStr = Math.floor(diff / 86400) + "d ago";
            const el = document.getElementById('time-remaining');
            if (el) el.innerText = "Last update: " + timeStr;
        }
        setInterval(updateTime, 10000);
        updateTime();

        function handleCommitSelect() {
            const select = document.getElementById('commit_select');
            const preview = document.getElementById('c_preview');
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) { preview.classList.add('hidden'); return; }
            const subj = opt.getAttribute('data-subject');
            const body = opt.getAttribute('data-body');
            const date = opt.getAttribute('data-date');
            const hash = opt.value;
            document.getElementById('c_subject').innerText = subj;
            document.getElementById('c_date').innerText = 'Date: ' + date + ' | Hash: ' + hash.substring(0,7);
            document.getElementById('c_desc').innerText = body || 'No extended description.';
            document.getElementById('restore_hash').value = hash;
            preview.classList.remove('hidden');
        }
        function cancelRestore(){
            document.getElementById('commit_select').value = '';
            document.getElementById('c_preview').classList.add('hidden');
        }
    </script>
</body>
</html>
