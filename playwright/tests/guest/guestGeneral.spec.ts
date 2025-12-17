import { test, expect } from '../../fixtures.ts';
import { runSharedGeneralTests } from '../common/sharedGeneralTests.ts'

/**
 * Better check we are actually a guest
 * 
 */
test('I\'m logged out', async ({ page }) => {
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');        
        await expect(page.getByRole('link', { name: 'Sign in' })).toContainText('Sign in');
    });

runSharedGeneralTests('guest');