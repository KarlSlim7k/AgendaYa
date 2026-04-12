const { chromium, firefox, webkit } = require('playwright');

const baseUrl = process.env.BASE_URL || 'http://127.0.0.1:8088';
const browserName = process.env.BROWSER || 'chromium';

const launchers = { chromium, firefox, webkit };
const selected = launchers[browserName];

if (!selected) {
  throw new Error(`Unsupported browser: ${browserName}`);
}

const pathsToCheck = [
  '/',
  '/contacto.html',
  '/centro-de-ayuda.html',
];

async function run() {
  const browser = await selected.launch({ headless: true });

  try {
    for (const path of pathsToCheck) {
      const page = await browser.newPage();
      const url = `${baseUrl}${path}`;

      const response = await page.goto(url, {
        waitUntil: 'networkidle',
        timeout: 45000,
      });

      if (!response || response.status() >= 400) {
        throw new Error(`URL failed: ${url}`);
      }

      const title = await page.title();
      if (!title || !title.trim()) {
        throw new Error(`Missing page title: ${url}`);
      }

      const h1Count = await page.locator('h1').count();
      if (h1Count === 0) {
        throw new Error(`Missing <h1> in: ${url}`);
      }

      await page.close();
    }
  } finally {
    await browser.close();
  }

  console.log(`Browser smoke passed on ${browserName}`);
}

run().catch((error) => {
  console.error(error);
  process.exit(1);
});
