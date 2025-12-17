import { test, expect } from '../../fixtures.ts';
import { runSharedGeneralTests } from '../common/sharedGeneralTests.ts'

/**
 * Auth expires and doesn't autorenew, so start by checking we are logged in
 * 
 */
test('I\'m logged in', async ({ page }) => {
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');        
        await expect(page.getByRole('link', { name: 'Sign out' })).toContainText('Sign out');
    });

runSharedGeneralTests('user');