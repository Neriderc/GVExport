import { test as baseTest, expect, Browser } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { getLogin } from './tests/common/utils';

export * from '@playwright/test';

// Overwrite test with our fixture
export const test = baseTest.extend<
{},
{ workerStorageState: string | undefined }
>({
    storageState: ({ workerStorageState }, use) => use(workerStorageState),

    workerStorageState: [
    async ({ browser }, use, testInfo) => {
        const id = testInfo.parallelIndex;
        const role = testInfo.project.name;

        // Don't log in if we are running as guest
        if (role === 'guest') {
            await use(undefined);
            return;
        }

        // Check for existing authentication
        const fileName = path.resolve(
            process.cwd(),
            `.auth/${role}-${id}.json`
        );
        const baseURL = testInfo.project.use.baseURL!;

        if (fs.existsSync(fileName)) {
            const valid = await isStorageStateValid(browser, baseURL, fileName);
            if (valid) {
                await use(fileName);
                return;
            }
            // Authentication existed, but we still aren't logged in for some reason, so
            // delete auth and allow login
            fs.unlinkSync(fileName);
        }

        await loginAndSaveState(browser, baseURL, role, fileName, id);
        await use(fileName);
    },
    { scope: 'worker' },
    ],
});

/**
 * Log in and save the authentication state to a file
 * 
 * @param browser 
 * @param baseURL 
 * @param role 
 * @param fileName 
 */
async function loginAndSaveState(
    browser: Browser,
    baseURL: string,
    role: string,
    fileName: string,
    id: number
) {
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();
    const account = await getLogin(role, id);

    await page.goto('/login');
    await page.fill('#username', account.username);
    await page.fill('#password', account.password);
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL('/tree/familytree/my-page');

    await context.storageState({ path: fileName });
    await context.close();
}

/**
 * Check if the saved authentication state is still valid
 * 
 * @param browser 
 * @param baseURL 
 * @param fileName 
 * @returns 
 */
async function isStorageStateValid(
    browser: Browser,
    baseURL: string,
    fileName: string
) {
    const context = await browser.newContext({
        baseURL,
        storageState: fileName,
    });

    const page = await context.newPage();
    await page.goto('/');

    const signInVisible = await page
    .getByRole('link', { name: /Sign in/i })
    .isVisible()
    .catch(() => false);
    await context.close();
    return !signInVisible;
}
