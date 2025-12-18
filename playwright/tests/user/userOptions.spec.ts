import { runSharedOptionsTests } from '../common/sharedOptionTests.ts'
import { test, expect } from '../../fixtures.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedOptionsTests('user');