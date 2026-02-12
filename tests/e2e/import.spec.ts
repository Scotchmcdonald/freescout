import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

test.describe('Data Import', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        // Assuming there is a seed or way to login
        // For E2E, we often mock or use a known user. 
        // We'll assume standard login flow
        await page.goto('/login');
        await page.fill('input[name="email"]', 'admin@example.com');
        await page.fill('input[name="password"]', 'password'); // Adjust credentials as needed
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL('/dashboard');
    });

    test('can import clients via CSV', async ({ page }) => {
        // Create dummy CSV
        const csvContent = 'name,email,status\nTest Corp Playwright,test@playwright.com,active';
        const csvPath = path.join(__dirname, 'clients_temp.csv');
        fs.writeFileSync(csvPath, csvContent);

        await page.goto('/settings/data-import');
        
        // Find the client import form
        const fileInput = page.locator('#import-clients-card input[type="file"]');
        await fileInput.setInputFiles(csvPath);
        
        await page.click('#import-clients-card button:has-text("Import Clients")');
        
        await expect(page).toHaveURL(/\/crm\/clients/);
        await expect(page.locator('body')).toContainText('1 clients imported successfully');
        await expect(page.locator('body')).toContainText('Test Corp Playwright');

        // Cleanup
        fs.unlinkSync(csvPath);
    });

    test('can import products via CSV', async ({ page }) => {
        // Create dummy CSV
        const csvContent = 'name,vendor,vendor_cost,default_price\nPlaywright Product,Test Vendor,10,20';
        const csvPath = path.join(__dirname, 'products_temp.csv');
        fs.writeFileSync(csvPath, csvContent);

        await page.goto('/settings/data-import');
        
        // Find the product import form
        const fileInput = page.locator('#import-products-card input[type="file"]');
        await fileInput.setInputFiles(csvPath);
        
        await page.click('#import-products-card button:has-text("Import Products")');
        
        await expect(page).toHaveURL(/\/software-subscriptions\/products/);
        await expect(page.locator('body')).toContainText('1 products imported successfully');
        await expect(page.locator('body')).toContainText('Playwright Product');

        // Cleanup
        fs.unlinkSync(csvPath);
    });
});
