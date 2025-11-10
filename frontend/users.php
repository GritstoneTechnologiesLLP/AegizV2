<?php
require_once __DIR__ . '/config.php';
$apiBase = getApiBaseUrl();

if (isset($_GET['proxy'])) {
    $resource = trim((string) $_GET['proxy'], '/');
    $allowedResources = ['users'];
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
            'detail' => 'Failed to reach users API.',
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
  <title>Aegiz Users</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <?php outputJsConfig(); ?>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: "#3b82f6",
            accent: "#ff4d4f",
          },
          fontFamily: {
            sans: ["Inter", "system-ui", "sans-serif"],
          },
          boxShadow: {
            card: "0 20px 60px rgba(31, 41, 55, 0.12)",
          },
        },
      },
    };
  </script>
  <script>
    const API_BASE = (window.API_CONFIG && window.API_CONFIG.baseUrl) || <?php echo json_encode($apiBase, JSON_UNESCAPED_SLASHES); ?>;
    const proxyBaseUrl = new URL(window.location.href);
    proxyBaseUrl.hash = "";

    const buildProxyUrl = (resource, params) => {
      const url = new URL(proxyBaseUrl);
      url.searchParams.set('proxy', resource.replace(/^\/+/g, ''));
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
          ...(options.headers || {}),
        },
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
        <a href="index.php" class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" title="Incidents">!</a>
        <a href="safetywalk.php" class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" title="Safety Walks">👟</a>
        <a href="audit.php" class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" title="Audits">📋</a>
        <a href="users.php" class="flex size-12 items-center justify-center rounded-2xl bg-brand/10" title="Users">👤</a>
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
              <p class="text-sm text-slate-500">Users</p>
            </div>
          </div>
          <div class="flex flex-1 items-center gap-2 sm:max-w-xl">
            <div class="relative flex-1">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">🔍</span>
              <input id="searchInput" type="search" placeholder="Search users" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-12 text-sm focus:border-brand focus:ring-brand/30">
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
            <div class="flex items-center gap-3 text-sm text-slate-500">
              <label class="flex items-center gap-2">
                <span class="font-semibold text-slate-600">Filter:</span>
                <select id="statusFilter" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium focus:border-brand focus:ring-brand/30">
                  <option value="">All Users</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </label>
              <label class="flex items-center gap-2">
                <span>Role</span>
                <input id="roleFilter" type="text" placeholder="Role" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-brand focus:ring-brand/30">
              </label>
            </div>
            <button id="addUserBtn" class="inline-flex items-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 focus:ring-2 focus:ring-accent/30 focus:outline-none">New User</button>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" id="statusCards">
            <article data-status="all" class="rounded-2xl border border-purple-200 bg-purple-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-purple-400/40 status-card">
              <div class="flex items-center justify-between text-purple-700 text-sm font-semibold">
                <span>Total</span>
                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold">Total</span>
              </div>
              <p id="summary-total" class="mt-3 text-3xl font-bold text-purple-900">0</p>
            </article>
            <article data-status="active" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-emerald-400/40 status-card">
              <div class="flex items-center justify-between text-emerald-600 text-sm font-semibold">
                <span>Active</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold">Active</span>
              </div>
              <p id="summary-active" class="mt-3 text-3xl font-bold text-emerald-700">0</p>
            </article>
            <article data-status="inactive" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm cursor-pointer transition ring-0 ring-slate-400/40 status-card">
              <div class="flex items-center justify-between text-slate-600 text-sm font-semibold">
                <span>Inactive</span>
                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold">Inactive</span>
              </div>
              <p id="summary-inactive" class="mt-3 text-3xl font-bold text-slate-700">0</p>
            </article>
          </div>

          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-card">
            <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-600">
              <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                  <th class="px-4 py-3 text-left">User</th>
                  <th class="px-4 py-3 text-left">Role</th>
                  <th class="px-4 py-3 text-left">Email Address</th>
                  <th class="px-4 py-3 text-left">Phone Number</th>
                  <th class="px-4 py-3 text-left">Added On</th>
                  <th class="px-4 py-3 text-left">Status</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody id="usersTableBody" class="divide-y divide-slate-100">
                <tr>
                  <td colspan="7" class="px-6 py-14 text-center text-slate-400">Loading users...</td>
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
      </section>
    </main>
  </div>

  <div id="userModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 backdrop-blur">
    <div class="relative flex w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-slate-200">
      <header class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
        <div>
          <h2 class="text-xl font-semibold text-slate-900" id="modalTitle">Create New User</h2>
          <p class="text-sm text-slate-500">Add user details, permissions, and branches</p>
        </div>
        <button id="modalClose" class="size-9 rounded-full bg-slate-100 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand/40" aria-label="Close modal">✕</button>
      </header>

      <ol id="modalStepper" class="flex gap-6 border-b border-slate-200 px-6 py-4 text-sm font-semibold text-slate-400">
        <li data-step="1" class="step-crumb text-brand after:hidden before:flex before:size-7 before:items-center before:justify-center before:rounded-full before:bg-brand before:text-xs before:font-bold before:leading-none before:text-white">Basic Details</li>
        <li data-step="2" class="step-crumb flex items:center gap-3 before:flex before:size-7 before:items:center before:justify-center before:rounded-full before:bg-slate-200 before:text-xs before:font-bold before:text-slate-600">Permissions</li>
        <li data-step="3" class="step-crumb flex items:center gap-3 before:flex before:size-7 before:items:center before:justify-center before:rounded-full before:bg-slate-200 before:text-xs before:font-bold before:text-slate-600">Preview</li>
      </ol>

      <form id="userForm" class="flex max-h-[70vh] flex-1 flex-col overflow-hidden">
        <div class="flex-1 overflow-y-auto px-6 py-6">
          <div class="form-step flex flex-col gap-6" data-step-index="0">
            <div class="grid gap-5 md:grid-cols-2">
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                First Name*
                <input type="text" id="first_name" name="first_name" required placeholder="First name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Last Name*
                <input type="text" id="last_name" name="last_name" required placeholder="Last name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Email*
                <input type="email" id="email" name="email" required placeholder="sample@gmail.com" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Phone
                <input type="tel" id="phone" name="phone" placeholder="+1 555 000 0000" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Address Line 1
                <input type="text" id="address_line1" name="address_line1" placeholder="Address line 1" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Address Line 2
                <input type="text" id="address_line2" name="address_line2" placeholder="Address line 2" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Country
                <input type="text" id="country" name="country" placeholder="Country" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                State
                <input type="text" id="state" name="state" placeholder="State" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                District
                <input type="text" id="district" name="district" placeholder="District" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Zipcode
                <input type="text" id="zipcode" name="zipcode" placeholder="Zipcode" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
              </label>
              <label class="flex flex-col gap-2 text-sm font-medium text-slate-600">
                Status
                <select id="status" name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-brand focus:ring-brand/30">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </label>
            </div>
            <div class="flex flex-col gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm">
              <span class="font-semibold text-slate-600">Profile Picture</span>
              <p class="text-xs text-slate-400">Upload a profile picture (optional, 5MB max).</p>
              <div class="flex items-center gap-3">
                <input type="file" accept="image/*" id="profileImageInput" class="hidden">
                <label for="profileImageInput" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">Browse</label>
                <button type="button" id="clearProfileImage" class="hidden text-xs font-semibold text-rose-500 hover:text-rose-700">Remove</button>
              </div>
              <img id="profileImagePreview" src="" alt="Profile preview" class="hidden h-24 w-24 rounded-xl object-cover border border-slate-200 bg-white">
            </div>
          </div>

          <div class="form-step hidden flex-col gap-6" data-step-index="1">
            <div class="grid gap-6 md:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
                <h3 class="text-sm font-semibold text-slate-700">Select Branches</h3>
                <input type="text" id="branchSearch" placeholder="Search branches" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-brand focus:ring-brand/30">
                <div id="branchList" class="space-y-2 max-h-56 overflow-y-auto">
                </div>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
                <h3 class="text-sm font-semibold text-slate-700">Select Roles</h3>
                <div id="roleList" class="space-y-2">
                </div>
              </div>
            </div>
          </div>

          <div class="form-step hidden flex-col gap-6" data-step-index="2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-4">
              <div class="flex items-center gap-4">
                <img id="summaryProfileImage" src="" alt="Profile" class="hidden h-20 w-20 rounded-full object-cover border border-slate-200 bg-white">
                <div>
                  <h3 class="text-lg font-semibold text-slate-800" data-summary="full_name">—</h3>
                  <p class="text-sm text-slate-500" data-summary="summary_email">—</p>
                  <p class="text-sm text-slate-500" data-summary="summary_phone">—</p>
                </div>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <h4 class="text-sm font-semibold text-slate-700">Address</h4>
                  <p class="text-sm text-slate-600" data-summary="summary_address">—</p>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-slate-700">Status</h4>
                  <p class="text-sm text-slate-600" data-summary="summary_status">—</p>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-slate-700">Branches</h4>
                <div class="mt-2 flex flex-wrap gap-2" data-summary="summary_branches"></div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-slate-700">Roles</h4>
                <div class="mt-2 flex flex-wrap gap-2" data-summary="summary_roles"></div>
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
    const form = document.getElementById("userForm");
    const modal = document.getElementById("userModal");
    const modalClose = document.getElementById("modalClose");
    const modalCancel = document.getElementById("modalCancel");
    const modalNext = document.getElementById("modalNext");
    const modalBack = document.getElementById("modalBack");
    const modalSubmit = document.getElementById("modalSubmit");
    const addUserBtn = document.getElementById("addUserBtn");
    const statusCards = Array.from(document.querySelectorAll(".status-card"));
    const stepElements = Array.from(document.querySelectorAll(".form-step"));
    const stepperItems = Array.from(document.querySelectorAll("#modalStepper li"));
    const usersTableBody = document.getElementById("usersTableBody");
    const pageInfo = document.getElementById("pageInfo");
    const resultsInfo = document.getElementById("resultsInfo");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");
    const pageSizeSelect = document.getElementById("pageSizeSelect");
    const roleFilterInput = document.getElementById("roleFilter");
    const statusFilterSelect = document.getElementById("statusFilter");
    const searchInput = document.getElementById("searchInput");
    const branchList = document.getElementById("branchList");
    const roleList = document.getElementById("roleList");
    const branchSearch = document.getElementById("branchSearch");
    const profileImageInput = document.getElementById("profileImageInput");
    const profileImagePreview = document.getElementById("profileImagePreview");
    const clearProfileImageBtn = document.getElementById("clearProfileImage");
    const summaryProfileImage = document.getElementById("summaryProfileImage");
    let currentStepIndex = 0;

    const availableBranches = [
      { name: "Atlanta Branch", location: "Texas, USA" },
      { name: "Kitchener Branch", location: "Winnipeg, Canada" },
      { name: "Scarborough Branch", location: "Winnipeg, Canada" },
      { name: "UL Cyber Park", location: "Seattle, USA" },
    ];

    const availableRoles = [
      "Safety Audit Manager",
      "COO",
      "Branch Manager",
      "Return Specialist",
      "Super user",
    ];

    function prettifyStatus(status) {
      return (status || "")
        .toString()
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function resetProfileImage() {
      profileImageInput.value = "";
      formState.profile_image_url = "";
      profileImagePreview.src = "";
      profileImagePreview.classList.add("hidden");
      clearProfileImageBtn.classList.add("hidden");
    }

    const formDefaults = () => ({
      first_name: "",
      last_name: "",
      email: "",
      phone: "",
      profile_image_url: "",
      address_line1: "",
      address_line2: "",
      country: "",
      state: "",
      district: "",
      zipcode: "",
      status: "active",
      roles: [],
      branches: [],
    });

    let formState = formDefaults();

    function renderBranchList(filter = "") {
      branchList.innerHTML = "";
      availableBranches
        .filter((branch) => branch.name.toLowerCase().includes(filter.toLowerCase()))
        .forEach((branch) => {
          const isSelected = formState.branches.some((entry) => entry.branch_name === branch.name);
          const option = document.createElement("button");
          option.type = "button";
          option.className = `w-full rounded-xl border px-3 py-2 text-left text-sm flex items-center justify-between ${isSelected ? 'border-brand text-brand bg-brand/10' : 'border-slate-200 bg-white text-slate-600'}`;
          option.innerHTML = `
            <div>
              <p class="font-medium">${branch.name}</p>
              <p class="text-xs text-slate-400">${branch.location || ""}</p>
            </div>
            <span class="text-xs font-semibold">${isSelected ? 'Selected' : 'Select'}</span>
          `;
          option.addEventListener("click", () => {
            if (isSelected) {
              formState.branches = formState.branches.filter((entry) => entry.branch_name !== branch.name);
            } else {
              formState.branches.push({ branch_name: branch.name, branch_location: branch.location });
            }
            renderBranchList(branchSearch.value.trim());
          });
          branchList.appendChild(option);
        });
    }

    function renderRoleList(filter = "") {
      roleList.innerHTML = "";
      availableRoles
        .filter((role) => role.toLowerCase().includes(filter.toLowerCase()))
        .forEach((role) => {
          const isSelected = formState.roles.some((entry) => entry.role_name === role);
          const option = document.createElement("button");
          option.type = "button";
          option.className = `w-full rounded-xl border px-3 py-2 text-left text-sm flex items-center justify-between ${isSelected ? 'border-brand text-brand bg-brand/10' : 'border-slate-200 bg-white text-slate-600'}`;
          option.innerHTML = `
            <span class="font-medium">${role}</span>
            <span class="text-xs font-semibold">${isSelected ? 'Selected' : 'Select'}</span>
          `;
          option.addEventListener("click", () => {
            if (isSelected) {
              formState.roles = formState.roles.filter((entry) => entry.role_name !== role);
            } else {
              formState.roles.push({ role_name: role });
            }
            renderRoleList(roleFilterInput.value.trim());
          });
          roleList.appendChild(option);
        });
    }

    branchSearch.addEventListener("input", (event) => renderBranchList(event.target.value.trim()));
    roleFilterInput.addEventListener("input", (event) => renderRoleList(event.target.value.trim()));

    profileImageInput.addEventListener("change", (event) => {
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        alert("Profile image must be 5 MB or smaller.");
        event.target.value = "";
        return;
      }
      const reader = new FileReader();
      reader.onload = () => {
        formState.profile_image_url = reader.result;
        profileImagePreview.src = reader.result;
        profileImagePreview.classList.remove("hidden");
        clearProfileImageBtn.classList.remove("hidden");
      };
      reader.readAsDataURL(file);
    });

    clearProfileImageBtn.addEventListener("click", resetProfileImage);

    function openModal(prefill) {
      form.reset();
      formState = formDefaults();
      if (prefill) {
        formState = prefill;
      }
      if (formState.profile_image_url) {
        profileImagePreview.src = formState.profile_image_url;
        profileImagePreview.classList.remove("hidden");
        clearProfileImageBtn.classList.remove("hidden");
      } else {
        profileImagePreview.classList.add("hidden");
        clearProfileImageBtn.classList.add("hidden");
      }
      renderBranchList(branchSearch.value.trim());
      renderRoleList(roleFilterInput.value.trim());
      modal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
      goToStep(0, true);
    }

    function closeModal() {
      modal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
      modalNext.classList.remove("hidden");
      modalSubmit.classList.add("hidden");
      modalBack.classList.add("hidden");
    }

    function goToStep(index, force = false) {
      if (!force && !collectStepData(currentStepIndex)) return;
      currentStepIndex = Math.max(0, Math.min(index, stepElements.length - 1));
      stepElements.forEach((step, idx) => step.classList.toggle("hidden", idx !== currentStepIndex));
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
      if (currentStepIndex === stepElements.length - 1) populateSummary();
    }

    function collectStepData(stepIndex) {
      if (stepIndex === 0) {
        const data = new FormData(form);
        formState.first_name = data.get("first_name") || "";
        formState.last_name = data.get("last_name") || "";
        formState.email = data.get("email") || "";
        formState.phone = data.get("phone") || "";
        formState.address_line1 = data.get("address_line1") || "";
        formState.address_line2 = data.get("address_line2") || "";
        formState.country = data.get("country") || "";
        formState.state = data.get("state") || "";
        formState.district = data.get("district") || "";
        formState.zipcode = data.get("zipcode") || "";
        formState.status = data.get("status") || "active";
        if (!formState.first_name || !formState.last_name || !formState.email) {
          alert("Please provide required fields (First Name, Last Name, Email).");
          return false;
        }
      }
      return true;
    }

    function populateSummary() {
      document.querySelector('[data-summary="full_name"]').textContent = `${formState.first_name || ''} ${formState.last_name || ''}`.trim() || "—";
      document.querySelector('[data-summary="summary_email"]').textContent = formState.email || "—";
      document.querySelector('[data-summary="summary_phone"]').textContent = formState.phone || "—";
      const addressParts = [formState.address_line1, formState.address_line2, formState.country, formState.state, formState.district, formState.zipcode].filter(Boolean);
      document.querySelector('[data-summary="summary_address"]').textContent = addressParts.join(', ') || "—";
      document.querySelector('[data-summary="summary_status"]').textContent = prettifyStatus(formState.status);
      const branchContainer = document.querySelector('[data-summary="summary_branches"]');
      branchContainer.innerHTML = "";
      formState.branches.forEach((branch) => {
        const pill = document.createElement("span");
        pill.className = "rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600";
        pill.textContent = branch.location ? `${branch.branch_name} (${branch.branch_location})` : branch.branch_name;
        branchContainer.appendChild(pill);
      });
      const rolesContainer = document.querySelector('[data-summary="summary_roles"]');
      rolesContainer.innerHTML = "";
      formState.roles.forEach((role) => {
        const pill = document.createElement("span");
        pill.className = "rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600";
        pill.textContent = role.role_name;
        rolesContainer.appendChild(pill);
      });
      if (formState.profile_image_url) {
        summaryProfileImage.src = formState.profile_image_url;
        summaryProfileImage.classList.remove("hidden");
      } else {
        summaryProfileImage.src = "";
        summaryProfileImage.classList.add("hidden");
      }
    }

    async function fetchUsers() {
      const pageSize = Number(pageSizeSelect.value);
      const params = new URLSearchParams({
        page: currentPage,
        page_size: pageSize,
      });
      if (selectedStatus !== "all") params.set("status", selectedStatus);
      const searchTerm = searchInput.value.trim();
      if (searchTerm) params.set("search", searchTerm);
      try {
        const response = await requestViaProxy("users", {}, params);
        const payload = await response.json();
        renderSummary(payload.meta);
        renderUsersTable(payload.data);
        renderPagination(payload.meta);
      } catch (error) {
        console.error(error);
        usersTableBody.innerHTML = `
          <tr>
            <td colspan="7" class="px-6 py-14 text-center text-rose-500">
              Unable to load users.<br>${error.message}
            </td>
          </tr>
        `;
      }
    }

    function renderSummary(meta) {
      const totalEl = document.getElementById("summary-total");
      const activeEl = document.getElementById("summary-active");
      const inactiveEl = document.getElementById("summary-inactive");
      if (totalEl) totalEl.textContent = meta.total ?? meta.filtered_total ?? 0;
      if (activeEl) activeEl.textContent = meta.active ?? 0;
      if (inactiveEl) inactiveEl.textContent = meta.inactive ?? 0;
      highlightActiveCard();
    }

    function renderUsersTable(users) {
      usersTableBody.innerHTML = "";
      if (!users.length) {
        usersTableBody.innerHTML = `
          <tr>
            <td colspan="7" class="px-6 py-14 text-center text-slate-400">No users found.</td>
          </tr>
        `;
        return;
      }
      users.forEach((user) => {
        const row = document.createElement("tr");
        row.className = "hover:bg-slate-50 transition cursor-pointer";
        const primaryRole = user.roles.length ? user.roles[0].role_name : '—';
        const statusPillClass = user.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500';
        row.innerHTML = `
          <td class="px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-full bg-${user.profile_image_url ? 'white' : '[url(https://i.pravatar.cc/80?img=12)]'} bg-cover bg-center">
                ${user.profile_image_url ? `<img src="${user.profile_image_url}" alt="${user.first_name}" class="h-10 w-10 rounded-full object-cover border border-slate-200">` : ''}
              </div>
              <div>
                <p class="font-semibold text-slate-700">${user.first_name} ${user.last_name}</p>
                <p class="text-xs text-slate-400">${user.id}</p>
              </div>
            </div>
          </td>
          <td class="px-4 py-4">${primaryRole}</td>
          <td class="px-4 py-4">${user.email}</td>
          <td class="px-4 py-4">${user.phone || '—'}</td>
          <td class="px-4 py-4">
            <p class="font-medium text-slate-700">${formatDate(user.added_on)}</p>
            <p class="text-xs text-slate-400">${formatTime(user.added_on)}</p>
          </td>
          <td class="px-4 py-4">
            <span class="rounded-full px-3 py-1 text-xs font-semibold ${statusPillClass}">${prettifyStatus(user.status)}</span>
          </td>
          <td class="px-4 py-4 text-right">
            <button data-id="${user.id}" class="view-user text-sm font-semibold text-brand hover:text-brand/80">View</button>
          </td>
        `;
        usersTableBody.appendChild(row);
      });

      usersTableBody.querySelectorAll(".view-user").forEach((button) => {
        button.addEventListener("click", async (event) => {
          event.stopPropagation();
          const userId = button.dataset.id;
          try {
            const response = await requestViaProxy(`users/${userId}`);
            const user = await response.json();
            const prefill = {
              first_name: user.first_name,
              last_name: user.last_name,
              email: user.email,
              phone: user.phone,
              profile_image_url: user.profile_image_url || "",
              address_line1: user.address_line1,
              address_line2: user.address_line2,
              country: user.country,
              state: user.state,
              district: user.district,
              zipcode: user.zipcode,
              status: user.status,
              roles: user.roles.map((role) => ({ role_name: role.role_name })),
              branches: user.branches.map((branch) => ({ branch_name: branch.branch_name, branch_location: branch.branch_location })),
            };
            document.getElementById("modalTitle").textContent = "User Details";
            openModal(prefill);
            modalNext.classList.add("hidden");
            modalBack.classList.add("hidden");
            modalSubmit.classList.add("hidden");
            goToStep(2, true);
          } catch (error) {
            console.error(error);
            alert("Failed to load user details.");
          }
        });
      });
    }

    function renderPagination(meta) {
      const totalPages = meta.page_count || 1;
      currentPage = Math.max(1, Math.min(meta.page || 1, totalPages));
      pageInfo.textContent = `${currentPage} / ${totalPages || 1}`;
      resultsInfo.textContent = `${meta.results_on_page || 0} of ${meta.filtered_total || 0} users`;
      prevPageBtn.disabled = currentPage <= 1;
      nextPageBtn.disabled = currentPage >= totalPages;
    }

    function formatDate(value) {
      if (!value) return "—";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return value;
      return date.toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
    }

    function formatTime(value) {
      if (!value) return "";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return "";
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function statusClass(status) {
      return status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500';
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

    prevPageBtn.addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage -= 1;
        fetchUsers();
      }
    });

    nextPageBtn.addEventListener("click", () => {
      currentPage += 1;
      fetchUsers();
    });

    pageSizeSelect.addEventListener("change", () => {
      currentPage = 1;
      fetchUsers();
    });

    searchInput.addEventListener("input", () => {
      currentPage = 1;
      fetchUsers();
    });

    statusFilterSelect.addEventListener("change", () => {
      const value = statusFilterSelect.value;
      selectedStatus = value ? value : 'all';
      currentPage = 1;
      fetchUsers();
    });

    statusCards.forEach((card) => {
      card.addEventListener("click", () => {
        const status = card.dataset.status || "all";
        if (status !== selectedStatus) {
          selectedStatus = status;
          currentPage = 1;
          fetchUsers();
        }
      });
    });

    addUserBtn.addEventListener("click", () => {
      document.getElementById("modalTitle").textContent = "Create New User";
      openModal();
    });

    modalClose.addEventListener("click", closeModal);
    modalCancel.addEventListener("click", closeModal);
    modalBack.addEventListener("click", () => goToStep(currentStepIndex - 1));
    modalNext.addEventListener("click", () => goToStep(currentStepIndex + 1));

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      if (!collectStepData(currentStepIndex)) return;
      const payload = buildPayload();
      try {
        const response = await requestViaProxy("users", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(payload),
        });
        await response.json();
        closeModal();
        fetchUsers();
        alert("User created successfully.");
      } catch (error) {
        console.error(error);
        alert("Failed to save user. Please ensure email is unique and required fields are filled.");
      }
    });

    function buildPayload() {
      return {
        first_name: formState.first_name,
        last_name: formState.last_name,
        email: formState.email,
        phone: formState.phone || null,
        profile_image_url: formState.profile_image_url || null,
        address_line1: formState.address_line1 || null,
        address_line2: formState.address_line2 || null,
        country: formState.country || null,
        state: formState.state || null,
        district: formState.district || null,
        zipcode: formState.zipcode || null,
        status: formState.status,
        roles: formState.roles,
        branches: formState.branches,
      };
    }

    highlightActiveCard();
    renderBranchList();
    renderRoleList();
    resetProfileImage();
    fetchUsers();
  </script>
</body>

</html>
