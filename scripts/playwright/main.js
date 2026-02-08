const { chromium } = require('playwright');

(async () => {
  // 1. Launch browser (headless: false let's you see what's happening)
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  console.log("Navigating to Fwango...");
  await page.goto('https://fwango.io/hapakgaw2025', { waitUntil: 'networkidle' });

  try {
    // 2. Click the 'Teams' tab 
    // Fwango uses specific buttons for tabs; we find the one containing "Teams"
    const teamsTab = page.locator('button, a').filter({ hasText: /^Teams$/ });
    await teamsTab.click();

    // 3. Wait for the list to load
    // We wait for a common element in the teams list (adjust selector if needed)
    await page.waitForSelector('.team-name, [data-testid*="team"]', { timeout: 10000 });

    // 4. Extract data
    const teamData = await page.evaluate(() => {
      // This runs inside the browser context
      const rows = Array.from(document.querySelectorAll('.team-card, .team-row')); // Update selectors based on actual UI
      return rows.map(row => ({
        name: row.querySelector('.team-name')?.innerText.trim() || "N/A",
        division: row.querySelector('.division-label')?.innerText.trim() || "N/A"
      }));
    });

    console.log(`Found ${teamData.length} teams:`);
    console.table(teamData);

  } catch (err) {
    console.error("Error during scraping:", err.message);
  }

  // page.on('response', async response => {
  //   if (response.url().includes('/api/tournaments/') && response.url().includes('/teams')) {
  //     const data = await response.json();
  //     console.log("Captured API Data:", data);
  //   }
  // });

  await browser.close();
})();

