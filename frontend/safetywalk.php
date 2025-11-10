<?php
require_once __DIR__ . '/config.php';
$apiBase = getApiBaseUrl();

if (isset($_GET['proxy'])) {
    $resource = trim((string) $_GET['proxy'], '/');
    $allowedResources = ['safety-walks'];
    $resourceKey = explode('/', $resource)[0];

    if (!in_array($resourceKey, $allowedResources, true)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['detail' => 'Unsupported proxy resource.']);
        exit;
    }

    $queryParams = $_GET;
    unset($queryParams['proxy']);

    $apiUrl = rtrim($apiBase, '/') . '/' . $resource;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($queryParams)) {
        $apiUrl .= '?' . http_build_query($queryParams);
    }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $headers = ['Accept: application/json'];
    $rawInput = file_get_contents('php://input');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $rawInput !== false && $rawInput !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawInput);
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $apiResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 502;
    curl_close($ch);

    header('Content-Type: application/json');
    http_response_code($statusCode);

    if ($apiResponse === false) {
        echo json_encode([
            'detail' => 'Failed to reach safety walks API.',
            'error' => $curlError,
        ]);
    } else {
        echo $apiResponse;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aegiz Safety Walks</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <?php outputJsConfig(); ?>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: "#3b82f6",
            accent: "#ff4d4f",
            slate: {
              950: "#0B1120"
            }
          },
          fontFamily: {
            sans: ["Inter", "system-ui", "sans-serif"]
          },
          boxShadow: {
            card: "0 20px 60px rgba(31, 41, 55, 0.12)"
          }
        }
      }
    };
  </script>
  <script>
    const API_BASE = (window.API_CONFIG && window.API_CONFIG.baseUrl) || <?php echo json_encode($apiBase, JSON_UNESCAPED_SLASHES); ?>;
    const proxyBaseUrl = new URL(window.location.href);
    proxyBaseUrl.hash = "";

    const buildProxyUrl = (resource, params) => {
      const url = new URL(proxyBaseUrl);
      url.searchParams.set("proxy", resource.replace(/^\/+/, ""));
      if (params) {
        for (const [key, value] of params.entries()) {
          url.searchParams.append(key, value);
        }
      }
      return url.toString();
    };

    const requestViaProxy = async (resource, options = {}, params) => {
      const url = buildProxyUrl(resource, params);
      const response = await fetch(url, {
        ...options,
        headers: {
          Accept: "application/json",
          ...(options.headers || {})
        }
      });
      if (!response.ok) {
        const errorText = await response.text().catch(() => "");
        throw new Error(`Proxy request failed (${response.status}): ${errorText}`);
      }
      return response;
    };
  </script>
</head>

<body class="bg-slate-100 font-sans text-slate-900 selection:bg-brand/10 selection:text-brand">
  <div class="min-h-screen flex">
    <aside class="hidden lg:flex w-20 xl:w-24 flex-col items-center gap-6 py-8 border-r border-slate-200 bg-white">
      <div class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-accent to-orange-400 text-white text-xl font-black tracking-tight shadow-card">
        A
      </div>
      <nav class="flex flex-col gap-4 text-brand font-semibold">
        <a href="index.php" class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" title="Incidents">!</a>
        <a href="safetywalk.php" class="flex size-12 items-center justify-center rounded-2xl bg-brand/10" title="Safety Walks">👟</a>
        <a href="audit.php" class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" title="Audits">📋</a>
        <a href="users.php" class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" title="Users">👤</a>
      </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white border-b border-slate-200">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-3">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-accent to-orange-400 text-white text-lg font-extrabold">
              A
            </div>
            <div>
              <p class="text-lg font-semibold tracking-tight">AEGIZ</p>
              <p class="text-sm text-slate-500">Safety Walks</p>
            </div>
          </div>
          <div class="flex flex-1 items-center gap-2 sm:max-w-xl">
            <div class="relative flex-1">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">🔍</span>
              <input id="searchInput" type="search" placeholder="Search Audits, Incidents, Safety Walks" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-12 text-sm focus:border-brand focus:ring-brand/30">
            </div>
            <kbd class="hidden rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-400 sm:inline-flex">Ctrl /</kbd>
          </div>
          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-700">EUSS</p>
              <p class="text-xs text-slate-400">All branches</p>
            </div>
            <div class="size-12 rounded-full bg-[url('https://i.pravatar.cc/96?img=18')] bg-cover bg-center"></div>
            <div>
              <p class="text-sm font-semibold text-slate-700">Dany Madona</p>
              <p class="text-xs text-slate-400">COO</p>
            </div>
          </div>
        </div>
      </header>

      <section class="flex-1 overflow-y-auto bg-slate-100">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6">
          <div class="border-b border-slate-200">
            <div class="flex items-center gap-8">
              <button class="relative pb-4 text-sm font-semibold text-slate-400 hover:text-slate-600 after:absolute after:left-0 after:bottom-0 after:h-1 after:w-full after:rounded-full after:bg-transparent">Overview</button>
              <button class="relative pb-4 text-sm font-semibold text-slate-900 after:absolute after:left-0 after:bottom-0 after:h-1 after:w-full after:rounded-full after:bg-brand">Safety Walks</button>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" id="statusCards">
            <article data-status="all" class="rounded-2xl border border-purple-200 bg-purple-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-purple-400/40 status-card">
              <div class="flex items-center justify-between text-purple-700 text-sm font-semibold">
                <span>Total</span>
                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold">Total</span>
              </div>
              <p id="summary-total" class="mt-3 text-3xl font-bold text-purple-900">0</p>
            </article>
            <article data-status="pending" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-rose-400/40 status-card">
              <div class="flex items-center justify-between text-rose-600 text-sm font-semibold">
                <span>Pending</span>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold">05</span>
              </div>
              <p id="summary-pending" class="mt-3 text-3xl font-bold text-rose-700">0</p>
            </article>
            <article data-status="in_progress" class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-sky-400/40 status-card">
              <div class="flex items-center justify-between text-sky-600 text-sm font-semibold">
                <span>In Progress</span>
                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-bold">05</span>
              </div>
              <p id="summary-in-progress" class="mt-3 text-3xl font-bold text-sky-700">0</p>
            </article>
            <article data-status="completed" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-emerald-400/40 status-card">
              <div class="flex items-center justify-between text-emerald-600 text-sm font-semibold">
                <span>Completed</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold">05</span>
              </div>
              <p id="summary-completed" class="mt-3 text-3xl font-bold text-emerald-700">0</p>
            </article>
          </div>

          <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
              <label class="flex items-center gap-2">
                <span class="font-semibold text-slate-600">Filters:</span>
                <select id="modeFilter" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium focus:border-brand focus:ring-brand/30">
                  <option value="">All Modes</option>
                  <option value="Conversational">Conversational</option>
                  <option value="Guided">Guided</option>
                  <option value="Virtual">Virtual</option>
                </select>
              </label>
              <label class="flex items-center gap-2">
                <span>Status</span>
                <select id="statusFilter" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium focus:border-brand focus:ring-brand/30">
                  <option value="">All</option>
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </select>
              </label>
              <label class="flex items-center gap-2">
                <span>Period</span>
                <select id="periodFilter" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium focus:border-brand focus:ring-brand/30">
                  <option value="">This Month</option>
                  <option value="week">This Week</option>
                  <option value="month">This Month</option>
                  <option value="year">This Year</option>
                </select>
              </label>
              <div class="ml-auto flex items-center gap-3">
                <button id="viewList" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 md:inline-flex">☰</button>
                <button id="viewGrid" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 md:inline-flex">▦</button>
                <button id="addSafetyWalkBtn" class="inline-flex items-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 focus:ring-2 focus:ring-accent/30 focus:outline-none">Add Safety Walk</button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                  <tr>
                    <th class="px-4 py-3 text-left">Site</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Mode</th>
                    <th class="px-4 py-3 text-left">Reported By</th>
                    <th class="px-4 py-3 text-left">Date &amp; Time</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                  </tr>
                </thead>
                <tbody id="safetyWalkTableBody" class="divide-y divide-slate-100">
                  <tr>
                    <td colspan="7" class="px-6 py-14 text-center text-slate-400">
                      Loading safety walks...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
              <span id="resultsInfo" class="text-sm text-slate-500"></span>
              <div class="flex items-center gap-3 text-sm text-slate-500">
                <label class="flex items-center gap-2">
                  Rows per page
                  <select id="pageSizeSelect" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium focus:border-brand focus:ring-brand/30">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                  </select>
                </label>
                <div class="flex items-center gap-3">
                  <button id="prevPage" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">&lt;</button>
                  <span id="pageInfo" class="min-w-[72px] text-center">1 / 1</span>
                  <button id="nextPage" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">&gt;</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div id="safetyWalkModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 backdrop-blur">
    <div class="relative flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-slate-200">
      <header class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
        <div>
          <h2 class="text-xl font-semibold text-slate-900" id="modalTitle">New Safety Walk</h2>
          <p class="text-sm text-slate-500">Capture safety walk details, findings, and checklist responses</p>
        </div>
        <button id="modalClose" class="size-9 rounded-full bg-slate-100 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand/40" aria-label="Close modal">✕</button>
      </header>

      <ol id="modalStepper" class="flex gap-6 border-b border-slate-200 px-6 py-4 text-sm font-semibold text-slate-400">
        <li data-step="1" class="step-crumb text-brand after:hidden before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-brand before:text-xs before:font-bold before:leading-none before:text-white">Details</li>
        <li data-step="2" class="step-crumb flex items-center gap-3 before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-slate-200 before:text-xs before:font-bold before:text-slate-600">Findings</li>
        <li data-step="3" class="step-crumb flex items-center gap-3 before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-slate-200 before:text-xs before:font-bold before:text-slate-600">Submit</li>
      </ol>

      <form id="safetyWalkForm" class="flex max-h-[70vh] flex-1 flex-col overflow-hidden">
        <div class="flex-1 overflow-y-auto px-6 py-6">
          <div class="form-step flex flex-col gap-6" data-step-index="0">
            <div class="grid gap-5 md:grid-cols-2">
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Date
                <input type="date" id="walk_date" name="walk_date" required class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Time
                <input type="time" id="walk_time" name="walk_time" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Site
                <input type="text" id="site" name="site" required placeholder="Site name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Area
                <input type="text" id="area" name="area" placeholder="Area" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Audit Mode
                <select id="mode" name="mode" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="Conversational">Conversational</option>
                  <option value="Guided">Guided</option>
                  <option value="Virtual">Virtual</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Contact
                <input type="text" id="contact" name="contact" placeholder="Admin User" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Is Virtual
                <select id="is_virtual" name="is_virtual" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="false">No</option>
                  <option value="true">Yes</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Status
                <select id="status" name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Reported By
                <input type="text" id="reported_by" name="reported_by" placeholder="Sara Andrews" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Reporter Role
                <input type="text" id="reported_by_role" name="reported_by_role" placeholder="Safety Audit Manager" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
            </div>
            <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
              Comments
              <textarea id="comments" name="comments" rows="4" placeholder="What is this safety walk about?" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-brand focus:ring-brand/30"></textarea>
            </label>
          </div>

          <div class="form-step hidden flex-col gap-6" data-step-index="1">
            <div class="space-y-4">
              <p class="text-sm font-semibold text-slate-700">Findings</p>
              <div id="findingsContainer" class="space-y-4">
                <!-- Findings injected here -->
              </div>
              <button type="button" id="addFindingBtn" class="inline-flex items-center rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-500 hover:bg-slate-50">
                + Add Finding
              </button>
            </div>

            <div class="space-y-4">
              <p class="text-sm font-semibold text-slate-700">Checklist Questions</p>
              <div id="responsesContainer" class="space-y-3">
                <!-- Responses injected here -->
              </div>
              <button type="button" id="addResponseBtn" class="inline-flex items-center rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-500 hover:bg-slate-50">
                + Add Question
              </button>
            </div>
          </div>

          <div class="form-step hidden flex-col gap-6" data-step-index="2">
            <div class="grid gap-5 md:grid-cols-3">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-base font-semibold text-slate-800">Details</h3>
                <dl class="mt-4 grid gap-3 text-sm">
                  <?php
                  $detailFields = [
                    'walk_date' => 'Date',
                    'walk_time' => 'Time',
                    'site' => 'Site',
                    'area' => 'Area',
                    'mode' => 'Mode',
                    'contact' => 'Contact',
                    'is_virtual' => 'Virtual',
                    'status' => 'Status',
                    'reported_by' => 'Reported By',
                    'reported_by_role' => 'Reporter Role',
                    'comments' => 'Comments'
                  ];
                  foreach ($detailFields as $field => $label): ?>
                    <div>
                      <dt class="text-xs uppercase tracking-wide text-slate-400"><?= htmlspecialchars($label) ?></dt>
                      <dd class="font-semibold text-slate-700" data-summary="<?= htmlspecialchars($field) ?>">—</dd>
                    </div>
                  <?php endforeach; ?>
                </dl>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                <h3 class="text-base font-semibold text-slate-800">Findings</h3>
                <div class="mt-4 space-y-3 text-sm" data-summary="findings">
                  <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Good Practice</p>
                    <p class="text-slate-700">—</p>
                  </div>
                </div>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-3">
                <h3 class="text-base font-semibold text-slate-800">Checklist</h3>
                <div class="mt-4 space-y-3 text-sm" data-summary="responses">
                  <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Question</p>
                    <p class="text-slate-700">—</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <footer class="flex items-center justify-between gap-3 border-t border-slate-200 bg-white px-6 py-5">
          <button type="button" id="modalCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand/30">Cancel</button>
          <div class="ml-auto flex items-center gap-3">
            <button type="button" id="modalBack" class="hidden rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand/30">Back</button>
            <button type="button" id="modalNext" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand/90 focus:outline-none focus:ring-2 focus:ring-brand/30">Next</button>
            <button type="submit" id="modalSubmit" class="hidden rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 focus:outline-none focus:ring-2 focus:ring-accent/30">Save</button>
          </div>
        </footer>
      </form>
    </div>
  </div>

  <script>
    let currentPage = 1;
    let selectedStatus = 'all';
    const form = document.getElementById("safetyWalkForm");
    const modal = document.getElementById("safetyWalkModal");
    const modalClose = document.getElementById("modalClose");
    const modalCancel = document.getElementById("modalCancel");
    const modalNext = document.getElementById("modalNext");
    const modalBack = document.getElementById("modalBack");
    const modalSubmit = document.getElementById("modalSubmit");
    const addSafetyWalkBtn = document.getElementById("addSafetyWalkBtn");
    const findingsContainer = document.getElementById("findingsContainer");
    const responsesContainer = document.getElementById("responsesContainer");
    const addFindingBtn = document.getElementById("addFindingBtn");
    const addResponseBtn = document.getElementById("addResponseBtn");
    const stepElements = Array.from(document.querySelectorAll(".form-step"));
    const stepperItems = Array.from(document.querySelectorAll("#modalStepper li"));
    const statusCards = Array.from(document.querySelectorAll(".status-card"));
    let currentStepIndex = 0;

    const defaultFinding = () => ({
      finding_type: "good_practice",
      description: "",
      photos: [],
      signature_url: ""
    });

    const defaultResponse = () => ({
      category: "",
      position: 1,
      question: "",
      answer: "",
      score: null
    });

    const formStateDefaults = () => ({
      walk_date: "",
      walk_time: "",
      site: "",
      area: "",
      mode: "Conversational",
      contact: "",
      is_virtual: false,
      comments: "",
      status: "pending",
      reported_by: "",
      reported_by_role: "",
      findings: [defaultFinding()],
      responses: [defaultResponse()]
    });

    let formState = formStateDefaults();

    function renderFindings() {
      findingsContainer.innerHTML = "";
      formState.findings.forEach((finding, index) => {
        const wrapper = document.createElement("div");
        wrapper.className = "rounded-xl border border-slate-200 bg-white p-4 space-y-3";
        wrapper.innerHTML = `
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-600">Finding ${index + 1}</span>
            <button type="button" data-index="${index}" class="remove-finding text-xs font-semibold text-rose-500 hover:text-rose-700">Remove</button>
          </div>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Type
            <select data-field="finding_type" data-index="${index}" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-brand focus:ring-brand/30">
              <option value="good_practice" ${finding.finding_type === "good_practice" ? "selected" : ""}>Good Practice</option>
              <option value="point_of_improvement" ${finding.finding_type === "point_of_improvement" ? "selected" : ""}>Point of Improvement</option>
            </select>
          </label>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Description
            <textarea data-field="description" data-index="${index}" rows="3" placeholder="What is this finding about?" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-brand focus:ring-brand/30">${finding.description || ""}</textarea>
          </label>
          <div class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            <span>Signature</span>
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 flex flex-col gap-3">
              ${finding.signature_url ? `<img src="${finding.signature_url}" alt="Signature" class="h-20 w-20 rounded-lg object-contain border border-slate-200 bg-white">` : `<p class="text-xs text-slate-400">Upload signature (PNG/JPG up to 5 MB)</p>`}
              <div class="flex items-center gap-3">
                <input type="file" accept="image/*" data-field="signature_file" data-index="${index}" class="hidden" id="signature-input-${index}">
                <label for="signature-input-${index}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">Upload</label>
                ${finding.signature_url ? `<button type="button" data-clear-signature="${index}" class="text-xs font-semibold text-rose-500 hover:text-rose-700">Remove</button>` : ""}
              </div>
            </div>
          </div>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Photos (comma separated URLs)
            <input type="text" data-field="photos" data-index="${index}" placeholder="https://example.com/photo1.jpg, https://..." class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-brand focus:ring-brand/30" value="${finding.photos.join(", ")}">
          </label>
        `;
        findingsContainer.appendChild(wrapper);
      });
      findingsContainer.querySelectorAll(".remove-finding").forEach((btn) => {
        btn.addEventListener("click", () => {
          const index = Number(btn.dataset.index);
          formState.findings.splice(index, 1);
          if (!formState.findings.length) {
            formState.findings.push(defaultFinding());
          }
          renderFindings();
        });
      });
      findingsContainer.querySelectorAll(".remove-finding").forEach((btn) => {
        btn.addEventListener("click", () => {
          const index = Number(btn.dataset.index);
          formState.findings.splice(index, 1);
          if (!formState.findings.length) {
            formState.findings.push(defaultFinding());
          }
          renderFindings();
        });
      });
      findingsContainer.querySelectorAll("[data-clear-signature]").forEach((btn) => {
        btn.addEventListener("click", () => {
          const index = Number(btn.dataset.clearSignature);
          formState.findings[index].signature_url = "";
          renderFindings();
        });
      });
      findingsContainer.querySelectorAll("[data-field]").forEach((input) => {
        const field = input.dataset.field;
        const index = Number(input.dataset.index);
        if (field === "signature_file") {
          input.addEventListener("change", (event) => {
            const file = event.target.files && event.target.files[0];
            if (!file) {
              return;
            }
            if (file.size > 5 * 1024 * 1024) {
              alert("Signature file must be 5 MB or smaller.");
              event.target.value = "";
              return;
            }
            const reader = new FileReader();
            reader.onload = () => {
              formState.findings[index].signature_url = reader.result;
              renderFindings();
            };
            reader.readAsDataURL(file);
          });
          return;
        }
        input.addEventListener("input", (event) => {
          if (field === "photos") {
            formState.findings[index].photos = event.target.value.split(",").map((value) => value.trim()).filter(Boolean);
          } else if (field === "finding_type") {
            formState.findings[index].finding_type = event.target.value;
          } else {
            formState.findings[index][field] = event.target.value;
          }
        });
      });
    }

    function renderResponses() {
      responsesContainer.innerHTML = "";
      formState.responses.forEach((response, index) => {
        const wrapper = document.createElement("div");
        wrapper.className = "rounded-xl border border-slate-200 bg-white p-4 space-y-3";
        wrapper.innerHTML = `
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-600">Question ${index + 1}</span>
            <button type="button" data-index="${index}" class="remove-response text-xs font-semibold text-rose-500 hover:text-rose-700">Remove</button>
          </div>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Category
            <input type="text" data-field="category" data-index="${index}" placeholder="Category" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-brand focus:ring-brand/30" value="${response.category || ""}">
          </label>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Question
            <textarea data-field="question" data-index="${index}" rows="2" placeholder="Question text" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-brand focus:ring-brand/30">${response.question || ""}</textarea>
          </label>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Answer
            <textarea data-field="answer" data-index="${index}" rows="2" placeholder="Type or dictate answer" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-brand focus:ring-brand/30">${response.answer || ""}</textarea>
          </label>
          <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
            Score (%)
            <input type="number" data-field="score" data-index="${index}" min="0" max="100" step="0.1" placeholder="80" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-brand focus:ring-brand/30" value="${response.score !== null && response.score !== undefined ? response.score : ""}">
          </label>
        `;
        responsesContainer.appendChild(wrapper);
      });
      responsesContainer.querySelectorAll(".remove-response").forEach((btn) => {
        btn.addEventListener("click", () => {
          const index = Number(btn.dataset.index);
          formState.responses.splice(index, 1);
          if (!formState.responses.length) {
            formState.responses.push(defaultResponse());
          }
          renderResponses();
        });
      });
      responsesContainer.querySelectorAll("[data-field]").forEach((input) => {
        input.addEventListener("input", (event) => {
          const field = event.target.dataset.field;
          const index = Number(event.target.dataset.index);
          let value = event.target.value;
          if (field === "score") {
            value = value === "" ? null : Number(value);
          }
          formState.responses[index][field] = value;
        });
      });
    }

    function openModal() {
      form.reset();
      formState = formStateDefaults();
      renderFindings();
      renderResponses();
      modal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
      goToStep(0, true);
    }

    function closeModal() {
      modal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }

    function goToStep(index, force = false) {
      if (!force && !collectStepData(currentStepIndex)) {
        return;
      }
      currentStepIndex = Math.max(0, Math.min(index, stepElements.length - 1));
      stepElements.forEach((step, idx) => {
        step.classList.toggle("hidden", idx !== currentStepIndex);
      });
      stepperItems.forEach((item, idx) => {
        const baseClass = "flex items-center gap-3 before:flex before:size-7 before:items-center before:justify-center before:rounded-full";
        if (idx < currentStepIndex) {
          item.className = `${baseClass} text-emerald-600 before:bg-emerald-500 before:text-white before:content-['✓']`;
        } else if (idx === currentStepIndex) {
          item.className = `${baseClass} text-brand before:bg-brand before:text-white before:content-[attr(data-step)]`;
        } else {
          item.className = `${baseClass} text-slate-400 before:bg-slate-200 before:text-slate-600 before:content-[attr(data-step)]`;
        }
      });
      modalBack.classList.toggle("hidden", currentStepIndex === 0);
      modalNext.classList.toggle("hidden", currentStepIndex === stepElements.length - 1);
      modalSubmit.classList.toggle("hidden", currentStepIndex !== stepElements.length - 1);
      if (currentStepIndex === stepElements.length - 1) {
        populateSummary();
      }
    }

    function collectStepData(stepIndex) {
      if (stepIndex === 0) {
        const data = new FormData(form);
        formState.walk_date = data.get("walk_date") || "";
        formState.walk_time = data.get("walk_time") || "";
        formState.site = data.get("site") || "";
        formState.area = data.get("area") || "";
        formState.mode = data.get("mode") || "";
        formState.contact = data.get("contact") || "";
        formState.is_virtual = (data.get("is_virtual") || "false") === "true";
        formState.comments = data.get("comments") || "";
        formState.status = data.get("status") || "pending";
        formState.reported_by = data.get("reported_by") || "";
        formState.reported_by_role = data.get("reported_by_role") || "";
        if (!formState.walk_date || !formState.site) {
          alert("Please provide required fields (Date and Site).");
          return false;
        }
      }
      return true;
    }

    function populateSummary() {
      const summaryMap = {
        walk_date: formState.walk_date,
        walk_time: formState.walk_time || "—",
        site: formState.site,
        area: formState.area || "—",
        mode: formState.mode || "—",
        contact: formState.contact || "—",
        is_virtual: formState.is_virtual ? "Yes" : "No",
        status: prettifyStatus(formState.status),
        reported_by: formState.reported_by || "—",
        reported_by_role: formState.reported_by_role || "—",
        comments: formState.comments || "—"
      };
      Object.entries(summaryMap).forEach(([key, value]) => {
        const target = document.querySelector(`[data-summary="${key}"]`);
        if (target) {
          target.textContent = value;
        }
      });
      const findingsSummary = document.querySelector('[data-summary="findings"]');
      findingsSummary.innerHTML = "";
      formState.findings.forEach((finding, index) => {
        const wrapper = document.createElement("div");
        wrapper.className = "rounded-xl border border-slate-200 bg-white p-3 space-y-2";
        wrapper.innerHTML = `
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">${index + 1}. ${prettifyStatus(finding.finding_type)}</p>
          <p class="text-sm text-slate-700">${finding.description || "—"}</p>
          ${finding.photos.length ? `<div class="flex gap-2">${finding.photos.map((url) => `<img src="${url}" alt="photo" class="h-12 w-12 rounded-lg object-cover border border-slate-200">`).join("")}</div>` : ""}
          ${finding.signature_url ? `<div class="flex items-center gap-2"><span class="text-xs text-slate-400">Signature:</span><img src="${finding.signature_url}" alt="Signature" class="h-12 w-12 rounded-lg object-contain border border-slate-200 bg-white"></div>` : ""}
        `;
        findingsSummary.appendChild(wrapper);
      });
      const responsesSummary = document.querySelector('[data-summary="responses"]');
      responsesSummary.innerHTML = "";
      formState.responses.forEach((response, index) => {
        const wrapper = document.createElement("div");
        wrapper.className = "rounded-xl border border-slate-200 bg-white p-3 space-y-2";
        wrapper.innerHTML = `
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">${index + 1}. ${response.category || "Checklist"}</p>
          <p class="text-sm font-semibold text-slate-700">${response.question || "—"}</p>
          <p class="text-sm text-slate-600">${response.answer || "—"}</p>
          ${response.score !== null && response.score !== undefined ? `<p class="text-xs text-slate-500">Score: ${response.score}%</p>` : ""}
        `;
        responsesSummary.appendChild(wrapper);
      });
    }

    function prettifyStatus(status) {
      return status.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
    }

    async function fetchSafetyWalks() {
      const pageSize = Number(document.getElementById("pageSizeSelect").value);
      const params = new URLSearchParams({
        page: currentPage,
        page_size: pageSize
      });
      if (selectedStatus !== "all") {
        params.set("status", selectedStatus);
      }
      const modeValue = document.getElementById("modeFilter").value;
      const statusValue = document.getElementById("statusFilter").value;
      const searchTerm = document.getElementById("searchInput").value.trim();
      if (searchTerm) {
        params.set("search", searchTerm);
      }
      try {
        const response = await requestViaProxy("safety-walks", {}, params);
        const payload = await response.json();
        renderSummary(payload.meta);
        renderTable(payload.data);
        renderPagination(payload.meta);
      } catch (error) {
        console.error(error);
        const tableBody = document.getElementById("safetyWalkTableBody");
        tableBody.innerHTML = `
          <tr>
            <td colspan="7" class="px-6 py-14 text-center text-rose-500">
              Unable to load safety walks.<br>${error.message}
            </td>
          </tr>
        `;
      }
    }

    function renderSummary(meta) {
      const total = meta.total ?? meta.filtered_total ?? 0;
      document.getElementById("summary-total").textContent = total;
      document.getElementById("summary-pending").textContent = meta.pending ?? 0;
      document.getElementById("summary-in-progress").textContent = meta.in_progress ?? 0;
      document.getElementById("summary-completed").textContent = meta.completed ?? 0;
      highlightActiveCard();
    }

    function renderTable(data) {
      const tableBody = document.getElementById("safetyWalkTableBody");
      tableBody.innerHTML = "";
      if (!data.length) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="7" class="px-6 py-14 text-center text-slate-400">
              No safety walks found.
            </td>
          </tr>
        `;
        return;
      }
      data.forEach((walk) => {
        const row = document.createElement("tr");
        row.className = "hover:bg-slate-50 transition cursor-pointer";
        row.innerHTML = `
          <td class="px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-[url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=80&q=80')] bg-cover bg-center"></div>
              <div>
                <p class="font-semibold text-slate-700">${walk.site}</p>
                <p class="text-xs text-slate-400">Site</p>
              </div>
            </div>
          </td>
          <td class="px-4 py-4">
            <p class="font-medium text-slate-700">${walk.area || "—"}</p>
            <p class="text-xs text-slate-400">Area</p>
          </td>
          <td class="px-4 py-4">${walk.mode || "—"}</td>
          <td class="px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-full bg-[url('https://i.pravatar.cc/80?img=12')] bg-cover bg-center"></div>
              <div>
                <p class="font-semibold text-slate-700">${walk.reported_by || "—"}</p>
                <p class="text-xs text-slate-400">${walk.reported_by_role || ""}</p>
              </div>
            </div>
          </td>
          <td class="px-4 py-4">
            <p class="font-medium text-slate-700">${formatDate(walk.walk_date)}</p>
            <p class="text-xs text-slate-400">${walk.walk_time || ""}</p>
          </td>
          <td class="px-4 py-4">
            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize ${statusClass(walk.status)}">${walk.status.replace(/_/g, " ")}</span>
          </td>
          <td class="px-4 py-4 text-right">
            <button data-id="${walk.id}" class="view-walk text-sm font-semibold text-brand hover:text-brand/80">View</button>
          </td>
        `;
        tableBody.appendChild(row);
      });
      tableBody.querySelectorAll(".view-walk").forEach((button) => {
        button.addEventListener("click", async (event) => {
          event.stopPropagation();
          const walkId = button.dataset.id;
          try {
            const response = await requestViaProxy(`safety-walks/${walkId}`);
            const walk = await response.json();
            formState = {
              walk_date: walk.walk_date,
              walk_time: walk.walk_time,
              site: walk.site,
              area: walk.area,
              mode: walk.mode,
              contact: walk.contact,
              is_virtual: walk.is_virtual,
              comments: walk.comments,
              status: walk.status,
              reported_by: walk.reported_by,
              reported_by_role: walk.reported_by_role,
              findings: walk.findings.map((finding) => ({
                finding_type: finding.finding_type,
                description: finding.description,
                photos: finding.photos || [],
                signature_url: finding.signature_url || ""
              })),
              responses: walk.responses.map((response, idx) => ({
                category: response.category,
                position: response.position || idx + 1,
                question: response.question,
                answer: response.answer,
                score: response.score
              }))
            };
            renderFindings();
            renderResponses();
            form.walk_date.value = walk.walk_date;
            form.walk_time.value = walk.walk_time || "";
            form.site.value = walk.site;
            form.area.value = walk.area || "";
            form.mode.value = walk.mode || "";
            form.contact.value = walk.contact || "";
            form.is_virtual.value = walk.is_virtual ? "true" : "false";
            form.status.value = walk.status || "pending";
            form.comments.value = walk.comments || "";
            form.reported_by.value = walk.reported_by || "";
            form.reported_by_role.value = walk.reported_by_role || "";
            document.getElementById("modalTitle").textContent = "Safety Walk Details";
            goToStep(2, true);
            modal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
            modalNext.classList.add("hidden");
            modalSubmit.classList.add("hidden");
          } catch (error) {
            console.error(error);
            alert("Failed to load safety walk details.");
          }
        });
      });
    }

    function renderPagination(meta) {
      const totalPages = meta.page_count || 1;
      currentPage = Math.max(1, Math.min(meta.page || 1, totalPages));
      document.getElementById("pageInfo").textContent = `${currentPage} / ${totalPages || 1}`;
      document.getElementById("resultsInfo").textContent = `${meta.results_on_page || 0} of ${meta.filtered_total || 0} safety walks`;
      document.getElementById("prevPage").disabled = currentPage <= 1;
      document.getElementById("nextPage").disabled = currentPage >= totalPages;
    }

    function formatDate(value) {
      if (!value) return "—";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return value;
      }
      return date.toLocaleDateString(undefined, {
        month: "short",
        day: "numeric",
        year: "numeric"
      });
    }

    function statusClass(status) {
      switch (status) {
        case "completed":
          return "bg-emerald-100 text-emerald-700";
        case "in_progress":
          return "bg-sky-100 text-sky-600";
        case "pending":
        default:
          return "bg-rose-100 text-rose-600";
      }
    }

    function highlightActiveCard() {
      statusCards.forEach((card) => {
        const isActive = selectedStatus === "all" ? card.dataset.status === "all" : card.dataset.status === selectedStatus;
        card.classList.toggle("ring-4", isActive);
        card.classList.toggle("shadow-lg", isActive);
        card.classList.toggle("opacity-100", isActive);
        card.classList.toggle("opacity-70", !isActive);
      });
    }

    document.getElementById("prevPage").addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage -= 1;
        fetchSafetyWalks();
      }
    });

    document.getElementById("nextPage").addEventListener("click", () => {
      currentPage += 1;
      fetchSafetyWalks();
    });

    document.getElementById("pageSizeSelect").addEventListener("change", () => {
      currentPage = 1;
      fetchSafetyWalks();
    });

    document.getElementById("searchInput").addEventListener("input", () => {
      currentPage = 1;
      fetchSafetyWalks();
    });

    statusCards.forEach((card) => {
      card.addEventListener("click", () => {
        const status = card.dataset.status || "all";
        if (status !== selectedStatus) {
          selectedStatus = status;
          currentPage = 1;
          fetchSafetyWalks();
        }
      });
    });

    addFindingBtn.addEventListener("click", () => {
      formState.findings.push(defaultFinding());
      renderFindings();
    });

    addResponseBtn.addEventListener("click", () => {
      formState.responses.push(defaultResponse());
      renderResponses();
    });

    addSafetyWalkBtn.addEventListener("click", openModal);
    modalClose.addEventListener("click", closeModal);
    modalCancel.addEventListener("click", closeModal);
    modalBack.addEventListener("click", () => goToStep(currentStepIndex - 1));
    modalNext.addEventListener("click", () => goToStep(currentStepIndex + 1));

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      if (!collectStepData(currentStepIndex)) {
        return;
      }
      const payload = buildPayload();
      try {
        const response = await requestViaProxy("safety-walks", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(payload)
        });
        await response.json();
        closeModal();
        fetchSafetyWalks();
        alert("Safety walk created successfully.");
      } catch (error) {
        console.error(error);
        alert("Failed to save safety walk.");
      }
    });

    function buildPayload() {
      return {
        walk_date: formState.walk_date,
        walk_time: formState.walk_time || null,
        site: formState.site,
        area: formState.area || null,
        mode: formState.mode || null,
        contact: formState.contact || null,
        is_virtual: formState.is_virtual,
        comments: formState.comments || null,
        status: formState.status,
        reported_by: formState.reported_by || null,
        reported_by_role: formState.reported_by_role || null,
        findings: formState.findings.map((finding) => ({
          finding_type: finding.finding_type,
          description: finding.description || null,
          photos: finding.photos || [],
          signature_url: finding.signature_url || null
        })),
        responses: formState.responses.map((response, idx) => ({
          category: response.category || null,
          position: response.position || idx + 1,
          question: response.question,
          answer: response.answer || null,
          score: response.score
        }))
      };
    }

    highlightActiveCard();
    fetchSafetyWalks();
  </script>
</body>

</html>
