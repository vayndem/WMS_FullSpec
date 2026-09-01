const puppeteer = require('puppeteer-core');

const CHROME_PATH = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = 'http://127.0.0.1:8123';

const CREDS = {
    purchasing: 'purchasing@wms.local',
    warehouse: 'warehouse@wms.local',
    finance: 'finance@wms.local',
    accounting: 'accounting@wms.local',
};
const PASSWORD = 'Wms12345!';

async function login(page, email) {
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    await page.type('#email', email);
    await page.type('#password', PASSWORD);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]'),
    ]);
}

async function collectErrors(page, label, actions) {
    const errors = [];
    const onConsole = (msg) => {
        if (msg.type() === 'error') errors.push(`[console] ${msg.text()}`);
    };
    const onPageError = (err) => errors.push(`[pageerror] ${err.message}`);
    page.on('console', onConsole);
    page.on('pageerror', onPageError);

    try {
        await actions();
    } catch (e) {
        errors.push(`[action-failed] ${e.message}`);
    }

    page.off('console', onConsole);
    page.off('pageerror', onPageError);

    console.log(`\n=== ${label} ===`);
    if (errors.length === 0) {
        console.log('OK - no console/page errors');
    } else {
        errors.forEach((e) => console.log(e));
    }
    return errors;
}

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    let totalErrors = 0;

    try {
        let ctx = await browser.createBrowserContext();
        let page = await ctx.newPage();
        await page.setViewport({ width: 1400, height: 900 });

        await login(page, CREDS.purchasing);
        totalErrors += (await collectErrors(page, 'LOGIN (purchasing)', async () => {})).length;

        totalErrors += (await collectErrors(page, 'Dashboard', async () => {
            await page.goto(BASE + '/dashboard', { waitUntil: 'networkidle0' });
        })).length;

        totalErrors += (await collectErrors(page, 'Pembelian index + open Tambah Pembelian modal', async () => {
            await page.goto(BASE + '/pembelian', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 500));
            const title = await page.$eval('#pembelianDialog h3', el => el.textContent.trim());
            console.log('  dialog title:', title);
        })).length;

        totalErrors += (await collectErrors(page, 'Request index', async () => {
            await page.goto(BASE + '/request', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
        })).length;

        totalErrors += (await collectErrors(page, 'Request create modal (AJAX)', async () => {
            await page.goto(BASE + '/request', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 800));
        })).length;

        ctx = await browser.createBrowserContext();
        page = await ctx.newPage();
        await page.setViewport({ width: 1400, height: 900 });
        await login(page, CREDS.warehouse);

        totalErrors += (await collectErrors(page, 'LPB index', async () => {
            await page.goto(BASE + '/lpb', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
        })).length;

        totalErrors += (await collectErrors(page, 'LPB index - expand first row detail', async () => {
            await page.goto(BASE + '/lpb', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 700));
            const btn = await page.$('tbody button.btn-circle');
            if (btn) { await btn.click(); await new Promise(r => setTimeout(r, 300)); }
            else { console.log('  (no rows to expand)'); }
        })).length;

        totalErrors += (await collectErrors(page, 'LPB create modal (AJAX)', async () => {
            await page.goto(BASE + '/lpb', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 800));
        })).length;

        totalErrors += (await collectErrors(page, 'NPK index', async () => {
            await page.goto(BASE + '/npk', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
        })).length;

        totalErrors += (await collectErrors(page, 'NPK create modal (AJAX)', async () => {
            await page.goto(BASE + '/npk', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 800));
        })).length;

        totalErrors += (await collectErrors(page, 'Retur Pembelian create modal (AJAX)', async () => {
            await page.goto(BASE + '/retur-pembelian', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 800));
        })).length;

        totalErrors += (await collectErrors(page, 'Stock Opname index + create modal (AJAX)', async () => {
            await page.goto(BASE + '/stock-opname', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 800));
        })).length;

        ctx = await browser.createBrowserContext();
        page = await ctx.newPage();
        await page.setViewport({ width: 1400, height: 900 });
        await login(page, CREDS.accounting);

        totalErrors += (await collectErrors(page, 'Invoice LPB index', async () => {
            await page.goto(BASE + '/invoice-lpb', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
        })).length;

        totalErrors += (await collectErrors(page, 'Invoice LPB create modal (AJAX)', async () => {
            await page.goto(BASE + '/invoice-lpb', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
            await page.click('button.btn-primary');
            await new Promise(r => setTimeout(r, 800));
        })).length;

        totalErrors += (await collectErrors(page, 'Jurnal index', async () => {
            await page.goto(BASE + '/jurnal', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
        })).length;

        totalErrors += (await collectErrors(page, 'Chart of Accounts index', async () => {
            await page.goto(BASE + '/chart-of-accounts', { waitUntil: 'networkidle0' });
            await new Promise(r => setTimeout(r, 500));
        })).length;

    } finally {
        await browser.close();
    }

    console.log(`\n\nTOTAL ERRORS: ${totalErrors}`);
    process.exit(totalErrors > 0 ? 1 : 0);
})();
