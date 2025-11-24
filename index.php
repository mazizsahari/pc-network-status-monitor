<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Network Status Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1F2937; /* Dark background */
            color: #f8f9fa;
        }

        /* Custom square box logic */
        .status-box {
            position: relative;
            width: 100%;
            padding-top: 50%; /* Creates a perfect square aspect ratio */
            transition: all 0.3s ease-in-out;
            cursor: pointer;
            border: none;
        }

        .status-box-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-box:hover {
            transform: scale(1.05);
            z-index: 10;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        /* Animations */
        .pulse-animation {
            animation: pulse 1.5s infinite;
        }

        .spin-animation {
            animation: spin 1s linear infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Custom Dark Panel background to match original Tailwind gray-800 */
        .bg-dark-panel {
            background-color: #1f2937;
        }
        
        /* Default box color (Waiting) */
        .bg-waiting {
            background-color: #374151; /* Gray 700 */
            color: white;
        }
    </style>
</head>
<body class="p-3 p-md-5">

    <div class="container-xxl">
        <header class="mb-5">
            <h1 class="display-5 fw-bold mb-2">PC Network Status Monitor</h1>
            <p id="status-message" class="text-secondary">Scanning for host status... Please wait.</p>
        </header>

        <div class="card bg-dark border-secondary shadow mb-4">
            <div class="card-body d-flex flex-column flex-sm-row align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3 mb-3 mb-sm-0">
                    <div>
                        <span class="text-secondary fw-semibold">Total Hosts:</span>
                        <span id="host-count" class="fs-4 fw-bold text-info ms-2">100</span>
                    </div>
                </div>
                
                <button id="refresh-button" class="btn btn-primary shadow-sm d-flex align-items-center">
                    <svg id="refresh-icon" class="me-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m15.356-2H15m-2-2v5h2.582m-3.56-1.57A8.001 8.001 0 0019.418 15m-15.356 2H9"></path>
                    </svg>
                    <span id="refresh-text">Refresh Status</span>
                </button>
            </div>
        </div>

        <div id="ip-grid" class="row row-cols-3 row-cols-md-6 g-2 p-3 bg-dark border border-secondary rounded shadow">
            </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- Configuration ---
        const BASE_IP = '192.168.100.';
        const NUM_HOSTS = 20;
        const TIMEOUT_MS = 2000; 
        const GRID_CONTAINER = document.getElementById('ip-grid');
        const REFRESH_BUTTON = document.getElementById('refresh-button');
        const REFRESH_TEXT = document.getElementById('refresh-text');
        const REFRESH_ICON = document.getElementById('refresh-icon');
        const STATUS_MESSAGE = document.getElementById('status-message');

        // --- Helper Function to Simulate Check ---
        async function checkHostStatus(ip) {

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);
            try {
                const url = `./api/ping.php?ip=${ip}`; // Assuming proxy is on the same server
                const response = await fetch(url, { signal: controller.signal });
                clearTimeout(timeoutId);
                // Proxy server logic would return '{"status": "UP"}' or '{"status": "DOWN"}'
                const data = await response.json();
                return data.status === 'UP';
            } 
            catch (error) {
                clearTimeout(timeoutId);
                return false; // Error (timeout, network error, or ping failure)
            }

            
        }

        // --- Main Logic ---

        function generateIPs() {
            const ips = ['10.82.3.13'];
            for (let i = 1; i <= NUM_HOSTS; i++) {
                ips.push(BASE_IP + i);
            }
            return ips;
        }

        function renderGrid(ips) {
            GRID_CONTAINER.innerHTML = ''; 
            ips.forEach(ip => {
                // Create column wrapper
                const col = document.createElement('div');
                col.className = 'col';

                const box = document.createElement('div');
                const safeId = ip.replace(/\./g, '-');
                
                // Bootstrap + Custom Classes
                box.id = `ip-${safeId}`;
                box.className = 'status-box bg-waiting rounded shadow-sm'; 
                box.setAttribute('data-ip', ip);
                
                box.innerHTML = `
                    <div class="status-box-content">
                        <span>${ip}</span>
                        <span id="status-${safeId}" class="mt-1 small text-secondary">Pinging...</span>
                    </div>
                `;
                
                col.appendChild(box);
                GRID_CONTAINER.appendChild(col);
            });
        }

        function updateBoxStatus(ip, isOnline) {
            const safeId = ip.replace(/\./g, '-');
            const box = document.getElementById(`ip-${safeId}`);
            const statusText = document.getElementById(`status-${safeId}`);

            if (!box || !statusText) return;

            // Remove old status classes
            box.classList.remove('bg-success', 'bg-danger', 'bg-waiting', 'pulse-animation');
            statusText.classList.remove('text-white', 'text-secondary');

            if (isOnline) {
                box.classList.add('bg-success'); // Bootstrap green
                statusText.textContent = 'ON';
                statusText.classList.add('text-white');
            } else {
                box.classList.add('bg-danger'); // Bootstrap red
                statusText.textContent = 'OFF';
                statusText.classList.add('text-white');
            }
        }

        async function runLimited(tasks, limit = 5) {
            const results = [];
            const running = [];

            for (const task of tasks) {
                const p = task().then(result => {
                    running.splice(running.indexOf(p), 1);
                    return result;
                });
                running.push(p);
                if (running.length >= limit) {
                    await Promise.race(running);
                }
                results.push(p);
            }

            return Promise.all(results);
        }

        async function startScan(ips) {
            setLoading(true);
            showMessage(`Starting high-concurrency scan of ${ips.length} hosts...`);

            // 1. Reset all boxes
            ips.forEach(ip => {
                const safeId = ip.replace(/\./g, '-');
                const box = document.getElementById(`ip-${safeId}`);
                const statusText = document.getElementById(`status-${safeId}`);
                if (box) {
                    box.classList.remove('bg-success', 'bg-danger');
                    box.classList.add('bg-waiting', 'pulse-animation');
                }
                if (statusText) {
                    statusText.textContent = 'Pinging...';
                    statusText.classList.remove('text-white');
                    statusText.classList.add('text-secondary');
                }
            });

            // 2. Execute Checks
            const checkTasks = ips.map(ip => () => 
                checkHostStatus(ip).then(isOnline => {
                    updateBoxStatus(ip, isOnline);
                    return { ip, isOnline };
                })
            );

            const results = await runLimited(checkTasks, 5); // Only 5 at a time


            // 3. Finalize
            const onlineCount = results.filter(r => r.isOnline).length;
            const offlineCount = results.length - onlineCount;
            showMessage(`Scan complete! ${onlineCount} PCs Online, ${offlineCount} PCs Offline.`);
            setLoading(false);
        }

        // --- UI State Management ---

        function setLoading(isLoading) {
            REFRESH_BUTTON.disabled = isLoading;
            
            // Toggle spin animation on the icon
            if (isLoading) {
                REFRESH_ICON.classList.add('spin-animation');
            } else {
                REFRESH_ICON.classList.remove('spin-animation');
            }

            REFRESH_TEXT.textContent = isLoading ? 'Scanning...' : 'Refresh Status';
            
            // Add pulse effect to grid if loading
            document.querySelectorAll('.status-box').forEach(box => {
                if (isLoading) {
                    box.classList.add('pulse-animation');
                } else {
                    box.classList.remove('pulse-animation');
                }
            });
        }

        function showMessage(message) {
            STATUS_MESSAGE.textContent = message;
        }

        // --- Initialization ---

        const IP_LIST = generateIPs();
        renderGrid(IP_LIST);
        startScan(IP_LIST);

        REFRESH_BUTTON.addEventListener('click', () => {
            if (!REFRESH_BUTTON.disabled) {
                startScan(IP_LIST);
            }
        });

        // Refresh scan every 60s
        setInterval(() => {
            if (!REFRESH_BUTTON.disabled) {
                startScan(IP_LIST);
            }
        }, 60000);

    </script>
</body>
</html>