import { runSharedDiagramTests } from '../common/sharedDiagramTests.ts'
import { test, expect } from '../../fixtures.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedDiagramTests('guest');