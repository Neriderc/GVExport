import { runSharedFormTests } from '../common/sharedFormTests'
import { runSharedTranslationTests } from '../common/sharedTranslationTests'
import { test, expect } from '../../fixtures.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedFormTests('user');
runSharedTranslationTests('user');