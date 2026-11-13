import Chart from "chart.js/auto";

const wadah = document.getElementById("monitoring");
const url = wadah.dataset.url;

const JEDA_MS = 5000;
const MAKS_TITIK = 30;

const GAYA_STATUS = {
    normal: "border-neutral-300 text-neutral-700",
    peringatan: "border-amber-500 bg-amber-50 text-amber-700",
    kritis: "border-red-600 bg-red-50 text-red-700",
    na: "border-neutral-300 text-neutral-400",
};
const TEKS_STATUS = {
    normal: "Normal",
    peringatan: "Peringatan",
    kritis: "Kritis",
    na: "Tidak tersedia",
};

const kartu = {}; // kunci metrik -> { el, chart }

function buatKartu(kunci, m) {
    const el = document.createElement("div");
    el.className = "card p-5";
    el.innerHTML = `
        <div class="flex items-start justify-between">
            <div>
                <div class="text-sm text-neutral-500">${m.label}</div>
                <div class="mt-1 text-2xl font-semibold" data-nilai></div>
                <div class="text-xs text-neutral-500" data-batas></div>
            </div>
            <span class="badge" data-status></span>
        </div>
        <div class="mt-4 h-40"><canvas></canvas></div>`;
    wadah.appendChild(el);

    const chart = new Chart(el.querySelector("canvas"), {
        type: "line",
        data: {
            labels: [],
            datasets: [
                {
                    label: m.label,
                    data: [],
                    borderColor: "#000",
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.3,
                },
                {
                    label: "Batas atas",
                    data: [],
                    borderColor: "#dc2626",
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: { y: { min: 0, suggestedMax: m.maks } },
            plugins: { legend: { display: false } },
        },
    });

    kartu[kunci] = { el, chart };
}

function perbarui(hasil) {
    Object.entries(hasil.metrik).forEach(([kunci, m]) => {
        if (!kartu[kunci]) buatKartu(kunci, m);
        const { el, chart } = kartu[kunci];

        el.querySelector("[data-nilai]").textContent =
            m.nilai === null ? "N/A" : `${m.nilai} ${m.satuan}`;
        el.querySelector("[data-batas]").textContent =
            `Batas atas: ${m.batas} ${m.satuan}`;

        const status = el.querySelector("[data-status]");
        status.className = `badge ${GAYA_STATUS[m.status]}`;
        status.textContent = TEKS_STATUS[m.status];

        if (m.nilai === null) return;

        chart.data.labels.push(hasil.waktu);
        chart.data.datasets[0].data.push(m.nilai);
        chart.data.datasets[1].data.push(m.batas);

        if (chart.data.labels.length > MAKS_TITIK) {
            chart.data.labels.shift();
            chart.data.datasets.forEach((d) => d.data.shift());
        }
        chart.update();
    });
}

async function ambil() {
    try {
        const res = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        perbarui(await res.json());
    } catch (e) {
        console.error("Gagal memuat data monitoring", e);
    }
}

ambil();
setInterval(ambil, JEDA_MS);
