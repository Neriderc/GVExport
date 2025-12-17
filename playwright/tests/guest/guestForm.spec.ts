import { runSharedFormTests } from '../common/sharedFormTests.ts'
import { test, expect } from '../../fixtures.ts';
import { runSharedTranslationTests } from '../common/sharedTranslationTests'

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedFormTests('guest');
runSharedTranslationTests('user');