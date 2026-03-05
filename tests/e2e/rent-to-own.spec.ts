import { test, expect } from '@playwright/test';

test.describe('Rent To Own Flow', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/login');
        await page.fill('input[name="email"]', 'admin@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL('/dashboard');
    });

    test('can generate early buyout invoice and transfer ownership', async ({ page }) => {
        // 1. Setup: Create a new Rent-to-Own contract via UI
        await page.goto('/contracts/contracts/create'); // Adjust path based on route

        // Fill contract form
        await page.selectOption('select[name="client_id"]', { index: 1 }); // Select first client
        await page.fill('input[name="name"]', 'Playwright RTO Test');
        await page.selectOption('select[name="contract_type"]', 'rent_to_own');
        
        // Wait for RTO fields to appear (if alpine/js toggles them)
        // await page.waitForSelector('input[name="purchase_price"]', { state: 'visible' });

        await page.fill('input[name="purchase_price"]', '1000');
        await page.fill('input[name="monthly_rental_fee"]', '100');
        await page.check('input[name="allow_early_buyout"]'); // Check Early Buyout
        
        await page.click('button[type="submit"]');
        
        // Should redirect to show page
        await expect(page).not.toHaveURL(/\/create/);
        
        // 2. Verify Initial State
        await expect(page.locator('body')).toContainText('Renting to Own');
        await expect(page.locator('body')).toContainText('Playwright RTO Test');

        // Verify Buyout Button exists
        // Using locator with dusk selector if available, or text
        const buyoutBtn = page.locator('button:has-text("Generate Buyout Invoice")');
        await expect(buyoutBtn).toBeVisible();

        // 3. Click Buyout
        page.on('dialog', dialog => dialog.accept()); // Accept confirm dialog
        await buyoutBtn.click();
        
        // 4. Verify Invoice Generated
        await expect(page.locator('body')).toContainText('Buyout invoice generated');
        
        // 5. Verify Ownership Pending Payment
        // Ownership should NOT change yet
        await expect(page.locator('body')).toContainText('Renting to Own');

        // 6. Simulate Payment (This is tricky in E2E without payment gateway)
        // We can navigate to the invoice page and mark as paid if there is a dev tool or admin override
        // Assuming there is an invoice link in the timeline or flash message, OR we just go to invoices list
        await page.goto('/billing/invoices'); // Adjust route
        await page.click('text=Playwright RTO Test'); // Click the latest invoice for this contract
        
        // Mark as paid (assuming admin has this button)
        await page.click('button:has-text("Mark as Paid")'); 

        // 7. Verify Ownership Transferred
        // Go back to contract
        await page.goBack(); // Or navigate explicitly
        // Depending on navigation validness, let's go via URL if we saved ID? 
        // We didn't save ID. Let's assume we can click back to contract from invoice.
        
        // Alternative: Search for contract
        await page.goto('/contracts/contracts/agreements');
        await page.click('text=Playwright RTO Test');

        await expect(page.locator('body')).toContainText('Ownership Transferred');
        await expect(buyoutBtn).not.toBeVisible();
    });
});
