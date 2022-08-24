import { test, expect } from '@playwright/test';

test('test', async ({ page }) => {

  // Go to http://localhost:8889/
  await page.goto('http://localhost:8889/');

  // Go to http://localhost:8889/wp-login.php?redirect_to=http%3A%2F%2Flocalhost%3A8889%2Fwp-admin%2F&reauth=1
  await page.goto('http://localhost:8889/wp-login.php?redirect_to=http%3A%2F%2Flocalhost%3A8889%2Fwp-admin%2F&reauth=1');

  // Fill input[name="log"]
  await page.locator('input[name="log"]').fill('admin');

  // Press Tab
  await page.locator('input[name="log"]').press('Tab');

  // Fill input[name="pwd"]
  await page.locator('input[name="pwd"]').fill('password');

  // Press Enter
  await page.locator('input[name="pwd"]').press('Enter');
  await expect(page).toHaveURL('http://localhost:8889/wp-admin/');

  // Click #menu-pages div:has-text("Pages")
  await page.locator('#menu-pages div:has-text("Pages")').click();
  await expect(page).toHaveURL('http://localhost:8889/wp-admin/edit.php?post_type=page');

  // Click div[role="main"] >> text=Add New
  await page.locator('div[role="main"] >> text=Add New').click();
  await expect(page).toHaveURL('http://localhost:8889/wp-admin/post-new.php?post_type=page');

  // Click [aria-label="Close dialog"]
  await page.locator('[aria-label="Close dialog"]').click();

  // Click [aria-label="Add block"]
  await page.locator('[aria-label="Add block"]').click();

  // Fill [placeholder="Search"]
  await page.locator('[placeholder="Search"]').fill('course lis');

  // Click button[role="option"]:has-text("Course List")
  await page.locator('button[role="option"]:has-text("Course List")').click();

  // Click button:has-text("Choose")
  await page.locator('button:has-text("Choose")').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();
  await expect(page).toHaveURL('http://localhost:8889/wp-admin/post.php?post=10&action=edit');

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Next pattern"]
  await page.locator('[aria-label="Next pattern"]').click();

  // Click [aria-label="Grid of courses"] div >> nth=0
  await page.locator('[aria-label="Grid of courses"] div').first().click();

  // Click [aria-label="Grid of courses"] div >> nth=0
  await page.locator('[aria-label="Grid of courses"] div').first().click();

  // Click [aria-label="Grid of courses"] div >> nth=0
  await page.locator('[aria-label="Grid of courses"] div').first().click();

  // Click [aria-label="Grid of courses"] div >> nth=0
  await page.locator('[aria-label="Grid of courses"] div').first().click();

});