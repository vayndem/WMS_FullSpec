const puppeteer = require('puppeteer-core');

const CHROME_PATH = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = 'http://127.0.0.1:8123';
const EMAIL = 'superadmin@wms.local';
const PASSWORD = 'Wms12345!';

const PAGES = [
    'dashboard',
    'asetperusahaan', 'asetperusahaan/create',
    'asset-categories',
    'bahan',
    'chart-of-accounts', 'chart-of-accounts/create', 'chart-of-accounts/kas-bank',
    'debit', 'debit/create',
    'gudangs', 'gudangs/create',
    'invoice-lpb', 'invoice-lpb/create',
    'jurnal', 'jurnal/create',
    'kategori-bahan', 'kategori-bahan/create',
    'kredit', 'kredit/create',
    'lpb', 'lpb/create',
    'mutasi-stoks',
    'npk', 'npk/create',
    'pembagian-gudangs',
    'pembelian',
    'pemeriksaan-considers', 'pemeriksaan-considers/create',
    'pengaturan-bahan-gudangs',
    'period-lock',
    'reconciliation',
    'rekonsiliasi-gudangs',
    'request', 'request/create',
    'requestdetail', 'requestdetail/create',
    'retur-pembelian', 'retur-pembelian/create',
    'service-baps', 'service-baps/create',
    'service-categories',
    'service-purchases', 'service-purchases/create',
    'stock-opname', 'stock-opname/create',
    'stok-gudangs',
    'supplier', 'supplier/create',
    'tax-rate',
    'tipe-pembebanan', 'tipe-pembebanan/create',
    'transfer-gudangs', 'transfer-gudangs/create',
    'wms-control',
];

const CREATE_BUTTON_SELECTORS = [
    'button.btn-primary',
    'a.btn-primary',
];

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1400, height: 900 });

    const allIssues = [];

    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            allIssues.push({ page: page.__current || 'unknown', kind: 'console', text: msg.text() });
        }
    });
    page.on('pageerror', (err) => {
        allIssues.push({ page: page.__current || 'unknown', kind: 'pageerror', text: err.message });
    });
    page.on('requestfailed', (req) => {
        const url = req.url();
        if (url.includes('/build/') || url.includes('favicon')) return;
        allIssues.push({ page: page.__current || 'unknown', kind: 'requestfailed', text: `${req.method()} ${url} :: ${req.failure()?.errorText}` });
    });
    page.on('response', (res) => {
        const status = res.status();
        const url = res.url();
        if (status >= 500) {
            allIssues.push({ page: page.__current || 'unknown', kind: 'http500', text: `${status} ${url}` });
        }
    });

    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    await page.type('#email', EMAIL);
    await page.type('#password', PASSWORD);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]'),
    ]);

    for (const path of PAGES) {
        page.__current = path;
        const before = allIssues.length;
        try {
            const res = await page.goto(BASE + '/' + path, { waitUntil: 'networkidle0', timeout: 20000 });
            const status = res ? res.status() : 0;
            if (status === 403 || status === 404) {
                console.log(`[SKIP ${status}] ${path}`);
                continue;
            }
            await new Promise((r) => setTimeout(r, 400));

            for (const sel of CREATE_BUTTON_SELECTORS) {
                const btn = await page.$(sel);
                if (btn) {
                    const text = await page.evaluate((el) => el.textContent.trim(), btn);
                    if (/tambah|buat|baru|create/i.test(text)) {
                        page.__current = path + ' -> click "' + text + '"';
                        await btn.click().catch(() => {});
                        await new Promise((r) => setTimeout(r, 700));
                    }
                    break;
                }
            }
        } catch (e) {
            allIssues.push({ page: path, kind: 'nav-failed', text: e.message });
        }
        const after = allIssues.length;
        console.log(`[${after > before ? 'ISSUES' : 'OK'}] ${path}${after > before ? ' (' + (after - before) + ')' : ''}`);
    }

    await browser.close();

    console.log('\n\n=== ALL ISSUES ===');
    if (allIssues.length === 0) {
        console.log('None found.');
    } else {
        allIssues.forEach((i) => console.log(`[${i.page}] (${i.kind}) ${i.text}`));
    }
    console.log(`\nTOTAL: ${allIssues.length}`);
    process.exit(allIssues.length > 0 ? 1 : 0);
})();
