<?php
require_once __DIR__ . '/config.php';
$apiBase = getApiBaseUrl();

if (isset($_GET['proxy'])) {
    $resource = trim((string) $_GET['proxy'], '/');
    $allowedResources = ['incidents'];
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
            'detail' => 'Failed to reach incidents API.',
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
  <title>Aegiz Safety Incidents</title>
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
    proxyBaseUrl.search = '';
    proxyBaseUrl.hash = '';

    const buildProxyUrl = (resource, params) => {
      const url = new URL(proxyBaseUrl);
      url.searchParams.set('proxy', resource.replace(/^\/+/, ''));
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
          Accept: 'application/json',
          ...(options.headers || {})
        }
      });
      if (!response.ok) {
        const errorText = await response.text().catch(() => '');
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
        <button class="flex size-12 items-center justify-center rounded-2xl bg-brand/10">!</button>
        <button class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">⚙</button>
        <button class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">👥</button>
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
              <p class="text-sm text-slate-500">Safety incidents</p>
            </div>
          </div>
          <div class="flex flex-1 items-center gap-2 sm:max-w-xl">
            <div class="relative flex-1">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">🔍</span>
              <input type="search" placeholder="Search Audits, Incidents, Safety Walks" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-12 text-sm focus:border-brand focus:ring-brand/30">
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
          <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
              <h1 class="text-2xl font-bold tracking-tight text-slate-900">All Incidents</h1>
              <p class="text-sm text-slate-500">Monitor status and capture reports across branches</p>
            </div>
            <div class="flex items-center gap-3">
              <button id="viewList" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 md:inline-flex">☰</button>
              <button id="viewGrid" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 md:inline-flex">▦</button>
              <button id="filterDate" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500">📅 This Month</button>
              <button id="addIncidentBtn" class="inline-flex items-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 focus:ring-2 focus:ring-accent/30 focus:outline-none">Add Incident</button>
            </div>
          </div>

          <div class="flex items-center gap-6 border-b border-slate-200">
            <button class="relative -mb-px pb-4 text-sm font-semibold text-slate-900 after:absolute after:left-0 after:bottom-0 after:h-1 after:w-full after:rounded-full after:bg-brand">Overview</button>
            <button class="pb-4 text-sm font-semibold text-slate-400 hover:text-slate-600">All Incidents</button>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-purple-200 bg-purple-50 p-5 shadow-sm">
              <p class="text-sm font-semibold text-purple-700">Total</p>
              <p id="summary-total" class="text-3xl font-bold text-purple-900">0</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
              <p class="text-sm font-semibold text-rose-600">Pending</p>
              <p id="summary-pending" class="text-3xl font-bold text-rose-800">0</p>
            </article>
            <article class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
              <p class="text-sm font-semibold text-sky-600">In Progress</p>
              <p id="summary-in-progress" class="text-3xl font-bold text-sky-800">0</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
              <p class="text-sm font-semibold text-emerald-600">Completed</p>
              <p id="summary-completed" class="text-3xl font-bold text-emerald-700">0</p>
            </article>
          </div>

          <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="text-sm text-slate-500">Showing incidents based on selected filters.</div>
              <label class="flex items-center gap-2 text-sm text-slate-500">
                Rows per page
                <select id="pageSizeSelect" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium focus:border-brand focus:ring-brand/30">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="20">20</option>
                </select>
              </label>
            </div>

            <div id="incidentGrid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"></div>
            <div id="emptyState" class="hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-12 text-center text-slate-500">
              No incidents found. Try adjusting filters or add a new incident.
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
              <span id="resultsInfo" class="text-sm text-slate-500"></span>
              <div class="flex items-center gap-3 text-sm text-slate-500">
                <button id="prevPage" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">&lt;</button>
                <span id="pageInfo" class="min-w-[72px] text-center">1 / 1</span>
                <button id="nextPage" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">&gt;</button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div id="incidentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 backdrop-blur">
    <div class="relative flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-slate-200">
      <header class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
        <div>
          <h2 class="text-xl font-semibold text-slate-900" id="modalTitle">Report Incident</h2>
          <p class="text-sm text-slate-500">Capture incident details, investigation, and RCA</p>
        </div>
        <button id="modalClose" class="size-9 rounded-full bg-slate-100 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand/40" aria-label="Close modal">✕</button>
      </header>

      <ol id="modalStepper" class="flex gap-6 border-b border-slate-200 px-6 py-4 text-sm font-semibold text-slate-400">
        <li data-step="1" class="step-crumb text-brand after:hidden before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-brand before:text-xs before:font-bold before:leading-none before:text-white">Details</li>
        <li data-step="2" class="step-crumb flex items-center gap-3 before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-slate-200 before:text-xs before:font-bold before:text-slate-600">RCA</li>
        <li data-step="3" class="step-crumb flex items-center gap-3 before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-slate-200 before:text-xs before:font-bold before:text-slate-600">Submit</li>
      </ol>

      <form id="incidentForm" class="flex max-h-[70vh] flex-1 flex-col overflow-hidden">
        <div class="flex-1 overflow-y-auto px-6 py-6">
          <div class="form-step flex flex-col gap-6" data-step-index="0">
            <div class="grid gap-5 md:grid-cols-2">
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Incident Title*
                <input type="text" id="incident_title" name="incident_title" required placeholder="Fire Safety" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Status*
                <select id="status" name="status" required class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Area
                <input type="text" id="area" name="area" placeholder="Select area" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Plant
                <input type="text" id="plant" name="plant" placeholder="Plant name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Date*
                <input type="date" id="incident_date" name="incident_date" required class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Time
                <input type="time" id="incident_time" name="incident_time" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Shift
                <select id="shift" name="shift" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="">Select shift</option>
                  <option value="Day">Day</option>
                  <option value="Night">Night</option>
                  <option value="Swing">Swing</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Incident Type
                <select id="incident_type" name="incident_type" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="">Select type</option>
                  <option value="Injury">Injury</option>
                  <option value="Near Miss">Near Miss</option>
                  <option value="Property Damage">Property Damage</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Body Part Affected
                <select id="body_part_affected" name="body_part_affected" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="">Select body part</option>
                  <option value="Head">Head</option>
                  <option value="Arm">Arm</option>
                  <option value="Leg">Leg</option>
                  <option value="Hand">Hand</option>
                  <option value="Foot">Foot</option>
                </select>
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600 md:col-span-2">
                Comments
                <input type="text" id="comments" name="comments" placeholder="Basic details are satisfactory" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
            </div>
            <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
              Description
              <textarea id="description" name="description" rows="4" placeholder="What is this incident about?" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-brand focus:ring-brand/30"></textarea>
            </label>
          </div>

          <div class="form-step hidden flex-col gap-6" data-step-index="1">
            <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
              Immediate Actions Taken
              <input type="text" id="immediate_actions_taken" name="immediate_actions_taken" placeholder="Enter actions taken" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
            </label>

            <div class="grid gap-5 md:grid-cols-3">
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Chairman
                <input type="text" id="chairman" name="chairman" placeholder="Enter name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Investigator
                <input type="text" id="investigator" name="investigator" placeholder="Enter name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Safety Officer
                <input type="text" id="safety_officer" name="safety_officer" placeholder="Enter name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
            </div>

            <div class="space-y-4">
              <p class="text-sm font-semibold text-slate-700">Root Cause Analysis</p>
              <div class="grid gap-4">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <textarea data-rca-input data-position="<?= $i ?>" data-question="Why Did this Incident Occur ?" rows="3" placeholder="Type or dictate answer" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-brand focus:ring-brand/30"></textarea>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <div class="form-step hidden flex-col gap-6" data-step-index="2">
            <div class="grid gap-5 md:grid-cols-3">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-base font-semibold text-slate-800">Details</h3>
                <dl class="mt-4 grid gap-3 text-sm">
                  <?php
                  $fields = [
                    'incident_title' => 'Incident Title',
                    'status' => 'Status',
                    'incident_date' => 'Date',
                    'incident_time' => 'Time',
                    'area' => 'Area',
                    'plant' => 'Plant',
                    'shift' => 'Shift',
                    'incident_type' => 'Incident Type',
                    'body_part_affected' => 'Body Part',
                    'comments' => 'Comments',
                    'description' => 'Description',
                    'immediate_actions_taken' => 'Immediate Actions'
                  ];
                  foreach ($fields as $field => $label): ?>
                    <div>
                      <dt class="text-xs uppercase tracking-wide text-slate-400"><?= htmlspecialchars($label) ?></dt>
                      <dd class="font-semibold text-slate-700" data-summary="<?= htmlspecialchars($field) ?>">—</dd>
                    </div>
                  <?php endforeach; ?>
                </dl>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-base font-semibold text-slate-800">Investigation Team</h3>
                <dl class="mt-4 space-y-3 text-sm">
                  <?php
                  $teamFields = [
                    'chairman' => 'Chairman',
                    'investigator' => 'Investigator',
                    'safety_officer' => 'Safety Officer'
                  ];
                  foreach ($teamFields as $field => $label): ?>
                    <div>
                      <dt class="text-xs uppercase tracking-wide text-slate-400"><?= htmlspecialchars($label) ?></dt>
                      <dd class="font-semibold text-slate-700" data-summary="<?= htmlspecialchars($field) ?>">—</dd>
                    </div>
                  <?php endforeach; ?>
                </dl>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-base font-semibold text-slate-800">Root Cause Analysis</h3>
                <div class="mt-4 space-y-3 text-sm" data-summary="rca">
                  <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Why Did this Incident Occur?</p>
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
            <button type="button" id="modalBack" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand/30 hidden">Back</button>
            <button type="button" id="modalNext" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand/90 focus:outline-none focus:ring-2 focus:ring-brand/30">Next</button>
            <button type="submit" id="modalSubmit" class="hidden rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 focus:outline-none focus:ring-2 focus:ring-accent/30">Save</button>
          </div>
        </footer>
      </form>
    </div>
  </div>

  <script>
    let currentPage = 1;
    const modal = document.getElementById("incidentModal");
    const form = document.getElementById("incidentForm");
    const stepElements = Array.from(document.querySelectorAll(".form-step"));
    const stepperItems = Array.from(document.querySelectorAll("#modalStepper li"));
    const nextBtn = document.getElementById("modalNext");
    const backBtn = document.getElementById("modalBack");
    const submitBtn = document.getElementById("modalSubmit");
    const cancelBtn = document.getElementById("modalCancel");
    const closeBtn = document.getElementById("modalClose");
    const addBtn = document.getElementById("addIncidentBtn");
    let currentStepIndex = 0;

    const defaultRcaQuestions = Array.from(document.querySelectorAll("[data-rca-input]")).map((textarea) => textarea.dataset.question);

    const formDefaults = () => ({
      incident_title: "",
      status: "pending",
      area: "",
      plant: "",
      incident_date: "",
      incident_time: "",
      shift: "",
      incident_type: "",
      body_part_affected: "",
      description: "",
      comments: "",
      immediate_actions_taken: "",
      investigation_team: {
        chairman: "",
        investigator: "",
        safety_officer: ""
      },
      rca_answers: defaultRcaQuestions.map((question, index) => ({
        position: index + 1,
        question,
        answer: ""
      }))
    });

    let formState = formDefaults();

    async function fetchIncidents() {
      const pageSize = Number(document.getElementById("pageSizeSelect").value);
      const params = new URLSearchParams({
        page: currentPage,
        page_size: pageSize
      });
      try {
        const response = await requestViaProxy('incidents', {}, params);
        const payload = await response.json();
        renderSummary(payload.meta);
        renderIncidents(payload.data);
        renderPagination(payload.meta);
      } catch (error) {
        console.error(error);
        showErrorState(error.message);
      }
    }

    function renderSummary(meta) {
      document.getElementById("summary-total").textContent = meta.total ?? 0;
      document.getElementById("summary-pending").textContent = meta.pending ?? 0;
      document.getElementById("summary-in-progress").textContent = meta.in_progress ?? 0;
      document.getElementById("summary-completed").textContent = meta.completed ?? 0;
    }

    function renderIncidents(incidents) {
      const container = document.getElementById("incidentGrid");
      container.innerHTML = "";
      if (!incidents.length) {
        document.getElementById("emptyState").classList.remove("hidden");
        return;
      }
      document.getElementById("emptyState").classList.add("hidden");

      incidents.forEach((incident) => {
        const card = document.createElement("article");
        card.className = "flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg";
        card.innerHTML = `
          <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-full bg-sky-100 text-sky-600 font-semibold">i</div>
            <div>
              <h3 class="text-lg font-semibold text-slate-900">${incident.incident_title}</h3>
              <p class="text-xs uppercase tracking-wide text-slate-400">Created Date</p>
              <p class="text-sm text-slate-600">${formatDate(incident.incident_date)}</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-full bg-[url('https://i.pravatar.cc/80?img=32')] bg-cover bg-center"></div>
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Investigator</p>
                <p class="text-sm font-semibold text-slate-700">${incident.investigation_team?.investigator || "Unassigned"}</p>
              </div>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize ${statusClass(incident.status)}">${incident.status.replace(/_/g, " ")}</span>
          </div>
        `;
        card.addEventListener("click", () => openIncidentDetail(incident.id));
        container.appendChild(card);
      });
    }

    function renderPagination(meta) {
      const totalPages = meta.page_count || 1;
      currentPage = Math.max(1, Math.min(meta.page || 1, totalPages));
      document.getElementById("pageInfo").textContent = `${currentPage} / ${totalPages || 1}`;
      document.getElementById("resultsInfo").textContent = `${meta.results_on_page || 0} of ${meta.filtered_total || 0} incidents`;
      document.getElementById("prevPage").disabled = currentPage <= 1;
      document.getElementById("nextPage").disabled = currentPage >= totalPages;
    }

    function showErrorState(message) {
      const container = document.getElementById("incidentGrid");
      container.innerHTML = `
        <div class="col-span-full rounded-2xl border border-dashed border-rose-200 bg-rose-50 p-8 text-center text-rose-600">
          Unable to load incidents.<br>${message}
        </div>
      `;
      document.getElementById("emptyState").classList.add("hidden");
    }

    async function openIncidentDetail(incidentId) {
      try {
        const response = await requestViaProxy(`incidents/${incidentId}`);
        const incident = await response.json();
        const answers = incident.rca_answers.map((item, index) => `${index + 1}. ${item.question}\n${item.answer}`).join("\n\n");
        alert(`Incident: ${incident.incident_title}\nDate: ${incident.incident_date}\nStatus: ${incident.status}\n\nRoot Cause Analysis:\n${answers}`);
      } catch (error) {
        console.error(error);
        alert("Failed to load incident details.");
      }
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

    document.getElementById("prevPage").addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage -= 1;
        fetchIncidents();
      }
    });

    document.getElementById("nextPage").addEventListener("click", () => {
      currentPage += 1;
      fetchIncidents();
    });

    document.getElementById("pageSizeSelect").addEventListener("change", () => {
      currentPage = 1;
      fetchIncidents();
    });

    addBtn.addEventListener("click", openModal);
    cancelBtn.addEventListener("click", closeModal);
    closeBtn.addEventListener("click", closeModal);
    nextBtn.addEventListener("click", () => handleStep(currentStepIndex + 1));
    backBtn.addEventListener("click", () => handleStep(currentStepIndex - 1));
    form.addEventListener("submit", handleSubmit);
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !modal.classList.contains("hidden")) {
        closeModal();
      }
    });

    function openModal() {
      form.reset();
      formState = formDefaults();
      document.querySelectorAll("[data-rca-input]").forEach((textarea, index) => {
        textarea.value = "";
        textarea.dataset.question = defaultRcaQuestions[index];
      });
      modal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
      handleStep(0, true);
    }

    function closeModal() {
      modal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }

    function handleStep(index, force = false) {
      if (!force && !collectStepData(currentStepIndex)) {
        return;
      }
      currentStepIndex = Math.max(0, Math.min(index, stepElements.length - 1));
      stepElements.forEach((step, idx) => {
        step.classList.toggle("hidden", idx !== currentStepIndex);
      });
      updateStepper();
      backBtn.classList.toggle("hidden", currentStepIndex === 0);
      nextBtn.classList.toggle("hidden", currentStepIndex === stepElements.length - 1);
      submitBtn.classList.toggle("hidden", currentStepIndex !== stepElements.length - 1);
      if (currentStepIndex === stepElements.length - 1) {
        populateSummary();
      }
    }

    function updateStepper() {
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
    }

    function collectStepData(stepIndex) {
      switch (stepIndex) {
        case 0:
          return collectStepOne();
        case 1:
          return collectStepTwo();
        default:
          return true;
      }
    }

    function collectStepOne() {
      const inputs = stepElements[0].querySelectorAll("input, select, textarea");
      for (const input of inputs) {
        if (!input.checkValidity()) {
          input.reportValidity();
          return false;
        }
      }
      formState.incident_title = form.incident_title.value.trim();
      formState.status = form.status.value;
      formState.area = form.area.value.trim();
      formState.plant = form.plant.value.trim();
      formState.incident_date = form.incident_date.value;
      formState.incident_time = form.incident_time.value;
      formState.shift = form.shift.value;
      formState.incident_type = form.incident_type.value;
      formState.body_part_affected = form.body_part_affected.value;
      formState.comments = form.comments.value.trim();
      formState.description = form.description.value.trim();
      return true;
    }

    function collectStepTwo() {
      const inputs = stepElements[1].querySelectorAll("input, textarea");
      for (const input of inputs) {
        if (!input.checkValidity()) {
          input.reportValidity();
          return false;
        }
      }
      formState.immediate_actions_taken = form.immediate_actions_taken.value.trim();
      const team = {
        chairman: form.chairman.value.trim(),
        investigator: form.investigator.value.trim(),
        safety_officer: form.safety_officer.value.trim()
      };
      formState.investigation_team = team.chairman || team.investigator || team.safety_officer ? team : null;

      const answers = [];
      document.querySelectorAll("[data-rca-input]").forEach((textarea, index) => {
        const answer = textarea.value.trim();
        if (answer) {
          answers.push({
            position: Number(textarea.dataset.position) || index + 1,
            question: textarea.dataset.question,
            answer
          });
        }
      });
      if (!answers.length) {
        alert("Add at least one root cause analysis answer.");
        return false;
      }
      formState.rca_answers = answers;
      return true;
    }

    function populateSummary() {
      const summaryMap = {
        incident_title: formState.incident_title || "—",
        status: prettifyStatus(formState.status),
        incident_date: formatDate(formState.incident_date),
        incident_time: formState.incident_time || "—",
        area: formState.area || "—",
        plant: formState.plant || "—",
        shift: formState.shift || "—",
        incident_type: formState.incident_type || "—",
        body_part_affected: formState.body_part_affected || "—",
        comments: formState.comments || "—",
        description: formState.description || "—",
        immediate_actions_taken: formState.immediate_actions_taken || "—",
        chairman: formState.investigation_team?.chairman || "—",
        investigator: formState.investigation_team?.investigator || "—",
        safety_officer: formState.investigation_team?.safety_officer || "—"
      };
      Object.entries(summaryMap).forEach(([key, value]) => {
        const target = document.querySelector(`[data-summary="${key}"]`);
        if (target) {
          target.textContent = value;
        }
      });
      const rcaContainer = document.querySelector('[data-summary="rca"]');
      rcaContainer.innerHTML = "";
      formState.rca_answers.forEach((entry, index) => {
        const wrapper = document.createElement("div");
        wrapper.className = "rounded-xl border border-slate-200 bg-white p-3";
        wrapper.innerHTML = `
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">${index + 1}. ${entry.question}</p>
          <p class="text-sm text-slate-700">${entry.answer}</p>
        `;
        rcaContainer.appendChild(wrapper);
      });
    }

    function prettifyStatus(status) {
      return status.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
    }

    async function handleSubmit(event) {
      event.preventDefault();
      if (!collectStepData(currentStepIndex)) {
        return;
      }
      setSubmitting(true);
      const payload = buildPayload();
      try {
        const response = await requestViaProxy('incidents', {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(payload)
        });
        closeModal();
        fetchIncidents();
        alert("Incident created successfully.");
      } catch (error) {
        console.error(error);
        alert(error.message);
      } finally {
        setSubmitting(false);
      }
    }

    function setSubmitting(isSubmitting) {
      const buttons = [nextBtn, backBtn, submitBtn, cancelBtn, closeBtn];
      buttons.forEach((button) => {
        if (button) {
          button.disabled = isSubmitting;
          button.classList.toggle("opacity-60", isSubmitting);
        }
      });
    }

    function buildPayload() {
      const payload = {
        incident_title: formState.incident_title,
        status: formState.status,
        incident_date: formState.incident_date,
        incident_time: formState.incident_time || null,
        area: formState.area || null,
        plant: formState.plant || null,
        shift: formState.shift || null,
        incident_type: formState.incident_type || null,
        body_part_affected: formState.body_part_affected || null,
        description: formState.description || null,
        comments: formState.comments || null,
        immediate_actions_taken: formState.immediate_actions_taken || null,
        rca_answers: formState.rca_answers
      };
      if (formState.investigation_team) {
        payload.investigation_team = formState.investigation_team;
      }
      return payload;
    }

    fetchIncidents();
  </script>
</body>

</html>

