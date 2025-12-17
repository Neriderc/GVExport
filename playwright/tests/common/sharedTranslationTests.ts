import { test, expect } from '../../fixtures.ts';
import { loadGVExport } from './utils.ts';
import { ENGLISH_TERMS } from '../resources/englishTerms';



export function runSharedTranslationTests(role: 'guest' | 'user') {

    test.describe('Test languages', () => {

        /**
         * Check for English terms first as precursor to checking they don't exist in other languages
         */
        test('all English form labels found', async ({ page }) => {
        await loadGVExport(page);

        await page.locator('li.menu-language > a.dropdown-toggle').click();
        await page.getByRole('menuitem', { name: 'British English' }).click();
        await expect(page.locator('#rendering svg')).toBeVisible();

        const locator = page.locator('#gvexport');
        for (const term of ENGLISH_TERMS) {
            await expect(locator).toContainText(term);
        }
    });


        const NON_ENGLISH_LANGUAGES = [
        'Deutsch',
        'català',
        'Nederlands',
        'norsk bokmål',
        'русский',
        'polski',
        'čeština',
        'français',
        'español',
        '简体中文',
        ];

        const NON_ENGLISH_LANGUAGES_FAIL_EXPECTED = [
        'norsk bokmål',
        'русский',
        'polski',
        'français',
        'español',
        '简体中文',
        ];

        const LANGUAGE_ALLOW_LIST: Record<string, string[]> = {
            Deutsch: ['Oval', 'Diagram'],
            Nederlands: ['Partners', 'Diagram', 'Download', 'Help'],
            čeština: ['Font', 'Diagram', 'Reset'],
            català: ['Font', 'Diagram', 'Rectangle', 'Oval'],
        };

        // Expect to pass these tests
        for (const language of NON_ENGLISH_LANGUAGES) {
            if (!NON_ENGLISH_LANGUAGES_FAIL_EXPECTED.includes(language))
            test(`Check ${language}`, async ({ page }) => {
                const allowed = LANGUAGE_ALLOW_LIST[language] ?? [];
                const terms = ENGLISH_TERMS.filter(term => !allowed.includes(term));

                await loadGVExport(page);

                await page.locator('li.menu-language > a.dropdown-toggle').click();
                await page.getByRole('menuitem', { name: language }).click();
                await expect(page.locator('#rendering svg')).toBeVisible();

                const text = await page.locator('#gvexport').textContent();
                for (const term of terms) {
                    if (text!.includes(term)) {
                        await expect.soft(false, `❌ English term still present: "${term}"`).toBe(term);
                    }
                }
            });
        }

        // We expect to fail these tests due to incomplete translations
        for (const language of NON_ENGLISH_LANGUAGES_FAIL_EXPECTED) {
            test.fail(`Check ${language} fails`, async ({ page }) => {
                const allowed = LANGUAGE_ALLOW_LIST[language] ?? [];
                const terms = ENGLISH_TERMS.filter(term => !allowed.includes(term));

                await loadGVExport(page);

                await page.locator('li.menu-language > a.dropdown-toggle').click();
                await page.getByRole('menuitem', { name: language }).click();
                await expect(page.locator('#rendering svg')).toBeVisible();

                const text = await page.locator('#gvexport').textContent();
                for (const term of terms) {
                    if (text!.includes(term)) {
                        await expect.soft(false, `❌ English term still present: "${term}"`).toBe(term);
                    }
                }
            });
        }
    
        /**
         * webtrees saves the set language to the database for the user account, so we need to change it back
         * to avoid affecting other tests that are looking for the English terms
         */
        test('set language back to English', async ({ page }) => {
            await loadGVExport(page);
            await page.locator('li.menu-language > a.dropdown-toggle').click();
            await page.getByRole('menuitem', { name: 'British English' }).click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.getByRole('button', { name: 'Language' })).toBeDefined();
        });

    });
}